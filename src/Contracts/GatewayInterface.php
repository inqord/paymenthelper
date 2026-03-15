<?php

namespace Inqord\PaymentHelper\Contracts;

use Illuminate\Http\Request;
use Inqord\PaymentHelper\DataTransferObjects\PaymentRequest;
use Inqord\PaymentHelper\DataTransferObjects\VerificationResponse;

interface GatewayInterface
{
    /**
     * Initiate a payment and return the redirect URL.
     *
     * @param PaymentRequest $paymentRequest
     * @return string Redirect URL to the payment gateway checkout page
     */
    public function initiate(PaymentRequest $paymentRequest): string;

    /**
     * Verify a callback or webhook from the payment gateway.
     *
     * @param Request $request The incoming callback request
     * @return VerificationResponse
     */
    public function verify(Request $request): VerificationResponse;
}
