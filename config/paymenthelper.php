<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the payment gateways below you wish
    | to use as your default gateway for all payments.
    |
    | Supported: "eps", "sslcommerz", "bkash"
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'eps'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways configurations
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        'eps' => [
            'enabled'     => env('EPS_ENABLED', true),
            'merchant_id' => env('EPS_MERCHANT_ID', ''),
            'store_id'    => env('EPS_STORE_ID', ''),
            'user_name'   => env('EPS_USER_NAME', ''),
            'password'    => env('EPS_PASSWORD', ''),
            'hash_key'    => env('EPS_HASH_KEY', ''),
            'api_url'     => env('EPS_API_URL', 'https://sandboxpgapi.eps.com.bd'),
            'env'         => env('APP_ENV', 'production'),
            'verify_ssl'  => env('EPS_VERIFY_SSL', true),
        ],

        'sslcommerz' => [
            'enabled'        => env('SSLC_ENABLED', false),
            'store_id'       => env('SSLC_STORE_ID', ''),
            'store_password' => env('SSLC_STORE_PASSWORD', ''),
            'api_url'      => env('SSLC_API_URL', 'https://sandbox.sslcommerz.com'),
            'verify_ssl'   => env('SSLC_VERIFY_SSL', true),
        ],

        // More drivers can be added here
        'bkash' => [
            'enabled'        => env('BKASH_ENABLED', false),
            'app_key'        => env('BKASH_APP_KEY', ''),
            'app_secret'     => env('BKASH_APP_SECRET', ''),
            'username'       => env('BKASH_USERNAME', ''),
            'password'       => env('BKASH_PASSWORD', ''),
            'api_url'        => env('BKASH_API_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'),
            'env'            => env('APP_ENV', 'production'),
            'verify_ssl'     => env('BKASH_VERIFY_SSL', true),
        ],
        
    ],

];
