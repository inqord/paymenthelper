<?php

namespace Inqord\PaymentHelper\Gateways;

use Illuminate\Http\Request;
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
        // Implementation for SSLCommerz initiation
        throw new \Exception("SSLCommerz implementation pending.");
    }

    /**
     * @inheritDoc
     */
    public function verify(Request $request): VerificationResponse
    {
        // Implementation for SSLCommerz verification
        throw new \Exception("SSLCommerz verification pending.");
    }
}
