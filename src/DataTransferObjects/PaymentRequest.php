<?php

namespace Inqord\PaymentHelper\DataTransferObjects;

class PaymentRequest
{
    public string $transactionId;
    public float $amount;
    public string $customerName;
    public string $customerEmail;
    public string $customerPhone;
    public string $successUrl;
    public string $failUrl;
    public string $cancelUrl;
    
    // Custom metadata payload for the gateway (often passed back on callback)
    public array $metadata = [];

    public function __construct(array $data)
    {
        $this->transactionId = $data['transaction_id'];
        $this->amount        = $data['amount'];
        $this->customerName  = $data['customer_name'] ?? 'Guest User';
        $this->customerEmail = $data['customer_email'] ?? 'guest@example.com';
        $this->customerPhone = $data['customer_phone'] ?? '00000000000';
        $this->successUrl    = $data['success_url'];
        $this->failUrl       = $data['fail_url'];
        $this->cancelUrl     = $data['cancel_url'] ?? $data['fail_url'];
        $this->metadata      = $data['metadata'] ?? [];
    }
}
