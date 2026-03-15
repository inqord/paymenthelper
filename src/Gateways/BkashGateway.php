<?php

namespace Inqord\PaymentHelper\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inqord\PaymentHelper\Contracts\GatewayInterface;
use Inqord\PaymentHelper\DataTransferObjects\PaymentRequest;
use Inqord\PaymentHelper\DataTransferObjects\VerificationResponse;

class BkashGateway implements GatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function getToken(): ?string
    {
        try {
            $http = Http::withHeaders([
                'username' => $this->config['username'],
                'password' => $this->config['password']
            ]);

            if (!($this->config['verify_ssl'] ?? true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post(rtrim($this->config['api_url'], '/') . '/checkout/token/grant', [
                'app_key' => $this->config['app_key'],
                'app_secret' => $this->config['app_secret']
            ]);

            $result = $response->json();
            return $result['id_token'] ?? null;

        } catch (\Exception $e) {
            Log::error('bKash Gateway GetToken Error: ' . $e->getMessage());
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
            throw new \Exception('bKash Initiation Failed: Could not get auth token.');
        }

        $payload = [
            'mode' => '0011',
            'payerReference' => $paymentRequest->customerPhone ?: '01700000000',
            'callbackURL' => $paymentRequest->successUrl,
            'amount' => $paymentRequest->amount,
            'currency' => $paymentRequest->currency,
            'intent' => $paymentRequest->intent,
            'merchantInvoiceNumber' => $paymentRequest->transactionId,
        ];

        try {
            $http = Http::withHeaders([
                'Authorization' => $token,
                'x-app-key' => $this->config['app_key']
            ]);
            
            if (!($this->config['verify_ssl'] ?? true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post(rtrim($this->config['api_url'], '/') . '/checkout/create', $payload);
            $result = $response->json();

            if (isset($result['bkashURL'])) {
                return $result['bkashURL'];
            }
            
            Log::error('bKash Initiation Parse Failed: ', ['response' => $result, 'payload' => $payload]);
            throw new \Exception('bKash Initiation Failed: ' . ($result['statusMessage'] ?? 'Gateway did not return a valid checkout URL.'));

        } catch (\Exception $e) {
            throw new \Exception('bKash Gateway Integration Error: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function verify(Request $request): VerificationResponse
    {
        $paymentID = $request->input('paymentID');
        $status = $request->input('status');
        $transactionId = $request->input('merchantInvoiceNumber', ''); // Fallback, not always back from bKash query strings
            
        if (!$paymentID) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => 'Missing payment ID',
                'raw_payload' => $request->all(),
            ]);
        }

        if ($status !== 'success') {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => 'bKash status is not success via callback: ' . $status,
                'raw_payload' => $request->all(),
            ]);
        }

        $token = $this->getToken();
        if (!$token) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => 'Could not get bKash token for verification',
                'raw_payload' => $request->all(),
            ]);
        }

        // Execute payment
        $http = Http::withHeaders([
            'Authorization' => $token,
            'x-app-key' => $this->config['app_key']
        ]);
        
        if (!($this->config['verify_ssl'] ?? true)) {
            $http = $http->withoutVerifying();
        }

        $apiUrl = rtrim($this->config['api_url'], '/') . '/checkout/execute';
        $response = $http->post($apiUrl, [
            'paymentID' => $paymentID
        ]);

        $verification = $response->json();

        if (!$verification || isset($verification['errorCode'])) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => $verification['errorMessage'] ?? 'API Verification Call Failed',
                'raw_payload' => array_merge($request->all(), ['ExecuteResponse' => $verification]),
            ]);
        }

        $isSuccess = isset($verification['transactionStatus']) && $verification['transactionStatus'] === 'Completed';
        $trxId = $verification['merchantInvoiceNumber'] ?? $transactionId;

        // Note: For bKash, metadata must be carefully stored or derived from transaction_id because bKash doesn't return custom value loops like SSL or EPS.
        // It relies on merchantInvoiceNumber.
        
        return new VerificationResponse([
            'is_successful' => $isSuccess,
            'transaction_id' => $trxId,
            'gateway_transaction_id' => $verification['trxID'] ?? null,
            'amount' => (float) ($verification['amount'] ?? 0),
            'status_message' => $verification['transactionStatus'] ?? 'Unknown',
            'metadata' => [], // bKash doesn't support valueA, valueB out of box easily unless appended to callback URL
            'raw_payload' => array_merge($request->all(), ['bKash_Verify_Object' => $verification]),
        ]);
    }
}
