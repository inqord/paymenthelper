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

    public string $customerAddress;
    public string $customerCity;
    public string $customerPostcode;
    public string $customerCountry;

    public string $currency;
    public string $intent;
    public string $shippingMethod;
    public int $numOfItem;
    public string $productCategory;
    public string $productProfile;
    
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
        
        $this->customerAddress  = $data['customer_address'] ?? 'N/A';
        $this->customerCity     = $data['customer_city'] ?? 'N/A';
        $this->customerPostcode = $data['customer_postcode'] ?? '1000';
        $this->customerCountry  = $data['customer_country'] ?? 'BD';
        
        $this->currency         = $data['currency'] ?? 'BDT';
        $this->intent           = $data['intent'] ?? 'sale';
        $this->shippingMethod   = $data['shipping_method'] ?? 'NO';
        $this->numOfItem        = $data['num_of_item'] ?? 1;
        $this->productCategory  = $data['product_category'] ?? 'Payment';
        $this->productProfile   = $data['product_profile'] ?? 'general';
        
        $this->metadata      = $data['metadata'] ?? [];
    }
}
