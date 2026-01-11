<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Server Key
    |--------------------------------------------------------------------------
    |
    | Your Midtrans server key from dashboard.
    |
    */
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Client Key
    |--------------------------------------------------------------------------
    |
    | Your Midtrans client key for frontend integration.
    |
    */
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Merchant ID
    |--------------------------------------------------------------------------
    |
    | Your Midtrans merchant ID.
    |
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Production Mode
    |--------------------------------------------------------------------------
    |
    | Set to true for production environment.
    |
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Sanitized Mode
    |--------------------------------------------------------------------------
    |
    | Set to true to sanitize data before sending to Midtrans.
    |
    */
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),

    /*
    |--------------------------------------------------------------------------
    | 3DS Mode
    |--------------------------------------------------------------------------
    |
    | Set to true to enable 3D Secure.
    |
    */
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Notification URL
    |--------------------------------------------------------------------------
    |
    | URL for payment notification callback.
    |
    */
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL', '/api/midtrans/webhook'),

    /*
    |--------------------------------------------------------------------------
    | Payment Expiry Duration
    |--------------------------------------------------------------------------
    |
    | Payment expiry in minutes.
    |
    */
    'expiry_duration' => env('MIDTRANS_EXPIRY_DURATION', 1440), // 24 hours
];
