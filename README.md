# Inqord Payment Helper

A simple, multi-gateway abstract payment integration package for Laravel. 

Currently supports:
- **EPS** (Easy Payment System)
- **SSLCommerz** (v4 API / IPN Verification)
- **bKash** (Tokenized Checkout API v1.2.0-beta)

## Installation

You can install the package via composer:

```bash
composer require inqord/paymenthelper
```

After installing, run the install command to publish the configuration and append the necessary environment variables to your `.env` file:

```bash
php artisan paymenthelper:install
```

## Configuration

In your `.env` file, choose your default gateway and fill in your credentials. For example, to use EPS:

```env
PAYMENT_GATEWAY=eps

EPS_ENABLED=true
EPS_VERIFY_SSL=true
EPS_MERCHANT_ID=your_merchant_id
EPS_STORE_ID=your_store_id
EPS_USER_NAME=your_username
EPS_PASSWORD=your_password
EPS_HASH_KEY=your_hash_key
EPS_API_URL=https://sandboxpgapi.eps.com.bd 
```

## Usage

Use the provided `PaymentHelper` facade to initiate payments and verify callbacks effortlessly.

### 1. Initiate a Payment

```php
use Inqord\PaymentHelper\Facades\PaymentHelper;
use Inqord\PaymentHelper\DataTransferObjects\PaymentRequest;

$checkoutUrl = PaymentHelper::initiate(new PaymentRequest([
    'transaction_id' => 'INV-12345',
    'amount'         => 500.00,
    'customer_name'  => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '01700000000',
    'success_url'    => route('payment.success'),
    'fail_url'       => route('payment.fail'),
    'cancel_url'     => route('payment.cancel'),
    
    // Optional Dynamic Shipping/Product Fields
    'currency'          => 'BDT',
    'customer_address'  => 'Dhanmondi, Dhaka',
    'customer_city'     => 'Dhaka',
    'customer_postcode' => '1205',
    'customer_country'  => 'Bangladesh',
    'shipping_method'   => 'NO',
    'num_of_item'       => 1,
    'product_category'  => 'Tuition Fee',
    'product_profile'   => 'general',
    'intent'            => 'sale', // Primarily used for bKash

    // Secure Gateway Metadata Passthrough
    'metadata'       => [
        'user_id' => 5,
        'item'    => 'premium_subscription'
    ]
]));

return redirect($checkoutUrl);
```

### 2. Verify a Payment (Success Callback/Webhook)

```php
use Illuminate\Http\Request;
use Inqord\PaymentHelper\Facades\PaymentHelper;

public function paymentSuccess(Request $request)
{
    $verification = PaymentHelper::verify($request);

    if ($verification->isSuccessful()) {
        $transactionId = $verification->transactionId;
        $amount = $verification->amount;
        $metadata = $verification->metadata; // Contains 'user_id' and 'item' from above

        // Your database logic to mark the order as paid...
        return redirect('/dashboard')->with('success', 'Payment successful!');
    }

    return redirect('/dashboard')->with('error', 'Payment failed or declined.');
}
```

## Creating Custom Drivers

This package uses Laravel's `Manager` pattern. You can easily extend it by calling `PaymentHelper::extend('custom_gateway', function($app) { ... })` in your AppServiceProvider.

## License

The MIT License (MIT).
