<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe credentials (client → barbershop service plan subscriptions)
    |--------------------------------------------------------------------------
    |
    | Portal ↔ barbershop billing continues to use Mercado Pago.
    | With Connect enabled, client payments use destination charges so each
    | barbershop receives funds on their Express account minus the platform fee.
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'brl'),

    'merchant_display_name' => env('STRIPE_MERCHANT_DISPLAY_NAME', env('APP_NAME', 'Tesora')),

    'apple_pay_merchant_id' => env('STRIPE_APPLE_PAY_MERCHANT_ID'),

    'google_pay_test_env' => env('STRIPE_GOOGLE_PAY_TEST_ENV', true),

    'api_version' => env('STRIPE_API_VERSION'),

    /*
    | Enable Pix Automático on subscription Checkout (BRL). Requires Pix to be
    | turned on in the Stripe Dashboard (Test and Live).
    */
    'pix_enabled' => env('STRIPE_PIX_ENABLED', true),

    'connect_enabled' => env('STRIPE_CONNECT_ENABLED', true),

    'connect_country' => env('STRIPE_CONNECT_COUNTRY', 'BR'),

    'application_fee_percent' => (float) env('STRIPE_APPLICATION_FEE_PERCENT', 10),

];
