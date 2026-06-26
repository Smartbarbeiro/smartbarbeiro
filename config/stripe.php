<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe credentials (client → barbershop service plan subscriptions)
    |--------------------------------------------------------------------------
    |
    | Portal ↔ barbershop billing continues to use Mercado Pago.
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'brl'),

    'merchant_display_name' => env('STRIPE_MERCHANT_DISPLAY_NAME', env('APP_NAME', 'Smart Barbeiro')),

    'apple_pay_merchant_id' => env('STRIPE_APPLE_PAY_MERCHANT_ID'),

    'google_pay_test_env' => env('STRIPE_GOOGLE_PAY_TEST_ENV', true),

    'api_version' => env('STRIPE_API_VERSION'),

];
