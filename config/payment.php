<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateway integrations
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'duitku'),

    /*
    |--------------------------------------------------------------------------
    | Duitku Configuration
    |--------------------------------------------------------------------------
    */
    'duitku' => [
        'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),
        'api_key' => env('DUITKU_API_KEY', ''),
        'base_url' => env('DUITKU_BASE_URL', 'https://sandbox.duitku.com'),
        'callback_url' => env('DUITKU_CALLBACK_URL', env('APP_URL') . '/api/payment/duitku/callback'),
        'return_url' => env('DUITKU_RETURN_URL', env('APP_URL') . '/payment/success'),

        // Available payment methods
        'payment_methods' => [
            'VA' => [
                'BC' => 'BCA Virtual Account',
                'M2' => 'Mandiri Virtual Account',
                'BN' => 'BNI Virtual Account',
                'BR' => 'BRI Virtual Account',
                'AG' => 'Bank Artha Graha',
                'NC' => 'Bank CIMB Niaga',
                'BT' => 'Permata Bank',
                'VA' => 'Maybank Virtual Account',
                'I1' => 'Bank Danamon',
                'S1' => 'Bank Sahabat Sampoerna',
            ],
            'EWALLET' => [
                'OV' => 'OVO',
                'SA' => 'ShopeePay',
                'LF' => 'LinkAja',
                'DA' => 'DANA',
            ],
            'RETAIL' => [
                'IR' => 'Indomaret',
                'AL' => 'Alfamart',
            ],
            'QRIS' => [
                'NQ' => 'QRIS (Nobu)',
                'SQ' => 'ShopeePay QRIS',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | QRIS Configuration
    |--------------------------------------------------------------------------
    */
    'qris' => [
        'merchant_id' => env('QRIS_MERCHANT_ID', ''),
        'api_key' => env('QRIS_API_KEY', ''),
        'base_url' => env('QRIS_BASE_URL', 'https://api.qris-provider.com'),
        'callback_url' => env('QRIS_CALLBACK_URL', env('APP_URL') . '/api/payment/qris/callback'),

        // QRIS expiry time in hours
        'expiry_hours' => env('QRIS_EXPIRY_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        // Auto-expire pending payments after X hours
        'auto_expire_hours' => env('PAYMENT_AUTO_EXPIRE_HOURS', 24),

        // Enable/disable payment methods
        'enable_va' => env('PAYMENT_ENABLE_VA', true),
        'enable_ewallet' => env('PAYMENT_ENABLE_EWALLET', true),
        'enable_retail' => env('PAYMENT_ENABLE_RETAIL', true),
        'enable_qris' => env('PAYMENT_ENABLE_QRIS', true),

        // Admin fee settings (in percentage or fixed amount)
        'admin_fee' => [
            'type' => env('PAYMENT_ADMIN_FEE_TYPE', 'percentage'), // percentage or fixed
            'value' => env('PAYMENT_ADMIN_FEE_VALUE', 0),
        ],

        // Minimum and maximum payment amount
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 10000),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 50000000),
    ],

];
