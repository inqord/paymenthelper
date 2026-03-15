<?php

namespace Inqord\PaymentHelper\DataTransferObjects;

class VerificationResponse
{
    public bool $isSuccessful;
    public string $transactionId;
    public ?string $gatewayTransactionId;
    public float $amount;
    public ?string $statusMessage;
    public array $metadata;
    public array $rawPayload;

    public function __construct(array $data)
    {
        $this->isSuccessful         = $data['is_successful'] ?? false;
        $this->transactionId        = $data['transaction_id'];
        $this->gatewayTransactionId = $data['gateway_transaction_id'] ?? null;
        $this->amount               = (float) ($data['amount'] ?? 0);
        $this->statusMessage        = $data['status_message'] ?? null;
        $this->metadata             = $data['metadata'] ?? [];
        $this->rawPayload           = $data['raw_payload'] ?? [];
    }
    
    public function isSuccessful(): bool 
    {
        return $this->isSuccessful;
    }
}
