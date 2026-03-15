<?php

namespace Inqord\PaymentHelper\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inqord\PaymentHelper\Contracts\GatewayInterface;
use Inqord\PaymentHelper\DataTransferObjects\PaymentRequest;
use Inqord\PaymentHelper\DataTransferObjects\VerificationResponse;

class SslCommerzGateway implements GatewayInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function initiate(PaymentRequest $paymentRequest): string
    {
        $payload = [
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'total_amount' => $paymentRequest->amount,
            'currency' => $paymentRequest->currency,
            'tran_id' => $paymentRequest->transactionId,
            'success_url' => $paymentRequest->successUrl,
            'fail_url' => $paymentRequest->failUrl,
            'cancel_url' => $paymentRequest->cancelUrl,
            'cus_name' => $paymentRequest->customerName ?: 'Customer Name',
            'cus_email' => $paymentRequest->customerEmail ?: 'customer@example.com',
            'cus_add1' => $paymentRequest->customerAddress,
            'cus_city' => $paymentRequest->customerCity,
            'cus_country' => $paymentRequest->customerCountry,
            'cus_phone' => $paymentRequest->customerPhone ?: '01700000000',
            'shipping_method' => $paymentRequest->shippingMethod,
            'num_of_item' => $paymentRequest->numOfItem,
            'product_name' => 'Payment for ' . $paymentRequest->transactionId,
            'product_category' => $paymentRequest->productCategory,
            'product_profile' => $paymentRequest->productProfile,

            // Pass metadata through value_a to value_d
            'value_a' => (string) ($paymentRequest->metadata['type'] ?? ''),
            'value_b' => (string) ($paymentRequest->metadata['id'] ?? ''),
            'value_c' => (string) ($paymentRequest->metadata['student_id'] ?? ''),
        ];

        try {
            $http = Http::asForm();
            
            if (!($this->config['verify_ssl'] ?? true)) {
                $http = $http->withoutVerifying();
            }

            $apiUrl = rtrim($this->config['api_url'], '/') . '/gwprocess/v4/api.php';
            $response = $http->post($apiUrl, $payload);
            $result = $response->json();

            if (isset($result['status']) && $result['status'] === 'SUCCESS') {
                return $result['GatewayPageURL'] ?? $result['redirectGatewayURL'];
            }
            
            Log::error('SSLCommerz Initiation Failed: ', ['response' => $result, 'payload' => $payload]);
            throw new \Exception('SSLCommerz Initiation Failed: ' . ($result['failedreason'] ?? 'Gateway did not return a valid checkout URL.'));

        } catch (\Exception $e) {
            throw new \Exception('SSLCommerz Gateway Integration Error: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function verify(Request $request): VerificationResponse
    {
        $transactionId = $request->input('tran_id');
        $valId = $request->input('val_id');
        $status = $request->input('status');
            
        if (!$transactionId || !$valId) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId ?: '',
                'status_message' => 'Missing transaction ID or validation ID',
                'raw_payload' => $request->all(),
            ]);
        }

        if ($status !== 'VALID' && $status !== 'VALIDATED') {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => 'Status is not VALID/VALIDATED via callback: ' . $status,
                'raw_payload' => $request->all(),
            ]);
        }

        // Now verify with IPN API
        $http = Http::asForm();
        if (!($this->config['verify_ssl'] ?? true)) {
            $http = $http->withoutVerifying();
        }

        $apiUrl = rtrim($this->config['api_url'], '/') . '/validator/api/validationserverAPI.php';
        $response = $http->get($apiUrl, [
            'val_id' => $valId,
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'v' => 1,
            'format' => 'json'
        ]);

        $verification = $response->json();

        if (!$verification) {
            return new VerificationResponse([
                'is_successful' => false,
                'transaction_id' => $transactionId,
                'status_message' => 'API Verification Call Failed or invalid JSON',
                'raw_payload' => $request->all(),
            ]);
        }

        $isSuccess = isset($verification['status']) && ($verification['status'] === 'VALID' || $verification['status'] === 'VALIDATED');
        
        return new VerificationResponse([
            'is_successful' => $isSuccess,
            'transaction_id' => $transactionId,
            'gateway_transaction_id' => $verification['bank_tran_id'] ?? null,
            'amount' => (float) ($verification['amount'] ?? 0),
            'status_message' => $verification['status'] ?? 'Unknown',
            'metadata' => [
                'type' => $verification['value_a'] ?? null,
                'id' => $verification['value_b'] ?: null,
                'student_id' => $verification['value_c'] ?? null,
            ],
            'raw_payload' => array_merge($request->all(), ['SSLC_Verify_Object' => $verification]),
        ]);
    }
}
