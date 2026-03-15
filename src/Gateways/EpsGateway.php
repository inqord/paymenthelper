<?php

namespace Inqord\PaymentHelper\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inqord\PaymentHelper\Contracts\GatewayInterface;
use Inqord\PaymentHelper\DataTransferObjects\PaymentRequest;
use Inqord\PaymentHelper\DataTransferObjects\VerificationResponse;

class EpsGateway implements GatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function generateHash(string $data): string
    {
        $hash = hash_hmac('sha512', $data, $this->config['hash_key'], true);
        return base64_encode($hash);
    }

    public function getToken(): ?string
    {
        try {
            $xHash = $this->generateHash($this->config['user_name']);
            
            $http = Http::withHeaders(['x-hash' => $xHash]);
            if (!($this->config['verify_ssl'] ?? true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post(rtrim($this->config['api_url'], '/') . '/v1/Auth/GetToken', [
                'userName' => $this->config['user_name'],
                'password' => $this->config['password']
            ]);

            $result = $response->json();
            return $result['token'] ?? null;

        } catch (\Exception $e) {
            Log::error('EPS Gateway GetToken Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function initiate(PaymentRequest $paymentRequest): string
    {
        $token = $this->getToken();
        if (!$token) {
            throw new \Exception('EPS Initiation Failed: Could not get auth token.');
        }

        $xHash = $this->generateHash($paymentRequest->transactionId);

        $payload = [
            'storeId' => $this->config['store_id'],
            'merchantId' => $this->config['merchant_id'],
            'transactionTypeId' => 10,
            'financialEntityId' => 0,
            'transitionStatusId' => 0,
            'merchantTransactionId' => $paymentRequest->transactionId,
            'CustomerOrderId' => 'ORD-' . time(), // Optional, generating fallback
            'totalAmount' => $paymentRequest->amount,
            
            'successUrl' => $paymentRequest->successUrl,
            'failUrl' => $paymentRequest->failUrl,
            'cancelUrl' => $paymentRequest->cancelUrl,
            
            'customerName' => $paymentRequest->customerName,
            'customerEmail' => $paymentRequest->customerEmail,
            'customerPhone' => $paymentRequest->customerPhone,
            'customerAddress' => 'N/A', // defaults required by EPS
            'customerCity' => 'N/A',
            'customerState' => 'N/A',
            'customerPostcode' => '1000',
            'customerCountry' => 'BD',
            'productName' => 'Payment for ' . $paymentRequest->transactionId,

            // Meta payload parsing
            'valueA' => (string) ($paymentRequest->metadata['type'] ?? ''),
            'valueB' => (string) ($paymentRequest->metadata['id'] ?? ''),
            'valueC' => (string) ($paymentRequest->metadata['student_id'] ?? ''),
            
            'ipAddress' => request()->ip() ?? '127.0.0.1',
            'version' => '1',
        ];

        try {
            $http = Http::withHeaders([
                'x-hash' => $xHash,
                'Authorization' => 'Bearer ' . $token
            ]);
            
            if (!($this->config['verify_ssl'] ?? true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post(rtrim($this->config['api_url'], '/') . '/v1/EPSEngine/InitializeEPS', $payload);
            $result = $response->json();

            $redirectUrl = $result['Url'] ?? $result['RedirectURL'] ?? $result['RedirectUrl'] ?? null;
            if ($redirectUrl) {
                return $redirectUrl;
            }
            
            Log::error('EPS Initiation Parse Failed: ', ['response' => $result]);
            throw new \Exception('EPS Initiation Failed: Gateway did not return a valid checkout URL.');

        } catch (\Exception $e) {
            throw new \Exception('EPS Gateway Integration Error: ' . $e->getMessage());
        }
    }

    /**
     * Verify Transaction status with external API 
     */
    protected function callCheckMerchantTransactionStatus(string $merchantTransactionId): ?array
    {
        $token = $this->getToken();
        if (!$token) return null;

        $xHash = $this->generateHash($merchantTransactionId);

        $http = Http::withHeaders([
            'x-hash' => $xHash,
            'Authorization' => 'Bearer ' . $token
        ]);
        
        if (!($this->config['verify_ssl'] ?? true)) {
            $http = $http->withoutVerifying();
        }

        $response = $http->get(rtrim($this->config['api_url'], '/') . '/v1/EPSEngine/CheckMerchantTransactionStatus', [
            'merchantTransactionId' => $merchantTransactionId
        ]);

        return $response->json();
    }

    /**
     * @inheritDoc
     */
    public function verify(Request $request): VerificationResponse
    {
        $merchantTransactionId = $request->MerchantTransactionId
            ?? $request->merchantTransactionId
            ?? null;
            
        if (!$merchantTransactionId) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => '',
                'status_message' => 'Missing transaction ID',
                'raw_payload' => $request->all(),
            ]);
        }

        $verification = $this->callCheckMerchantTransactionStatus($merchantTransactionId);
        
        if (!$verification) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $merchantTransactionId,
                'status_message' => 'API Verification Call Failed',
                'raw_payload' => $request->all(),
            ]);
        }

        $isSuccess = isset($verification['Status']) && ($verification['Status'] === 'Success' || $verification['Status'] === 'Successful');
        
        return new VerificationResponse([
            'is_successful' => $isSuccess,
            'transaction_id' => $merchantTransactionId,
            'gateway_transaction_id' => $verification['EPSTransactionId'] ?? null,
            'amount' => (float) ($verification['TotalAmount'] ?? 0),
            'status_message' => $verification['Status'] ?? 'Unknown',
            'metadata' => [
                'type' => $verification['ValueA'] ?? null,
                'id' => $verification['ValueB'] ?: null,
                'student_id' => $verification['ValueC'] ?? null,
            ],
            'raw_payload' => array_merge($request->all(), ['EPS_Verify_Object' => $verification]),
        ]);
    }
}
