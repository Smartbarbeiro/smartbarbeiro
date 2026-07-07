<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mercado Pago credentials
    |--------------------------------------------------------------------------
    |
    | Use test credentials from:
    | https://www.mercadopago.com.br/developers/panel/app
    |
    */

    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),

    'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),

    'client_id' => env('MERCADOPAGO_CLIENT_ID'),

    'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),

    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),

    'currency_id' => env('MERCADOPAGO_CURRENCY', 'BRL'),

    'runtime_environment' => env('MERCADOPAGO_RUNTIME', 'server'),

    /*
    | Mercado Pago requires an HTTPS back_url for subscription checkout.
    | For local testing, use an ngrok URL: MERCADOPAGO_BACK_URL=https://xxxx.ngrok-free.app
    */
    'back_url' => env('MERCADOPAGO_BACK_URL', env('APP_URL')),

    'merchant_name' => env('MERCADOPAGO_MERCHANT_NAME', env('APP_NAME', 'Smart Barbeiro')),

    /*
    | Native wallet payments (mobile app via @capgo/capacitor-pay).
    | Apple Pay: create a Merchant ID in Apple Developer and register with Mercado Pago.
    | Google Pay: configure your business profile in Google Pay Business Console.
    */
    'apple_pay_merchant_id' => env('MERCADOPAGO_APPLE_PAY_MERCHANT_ID'),

    'google_pay_merchant_id' => env('MERCADOPAGO_GOOGLE_PAY_MERCHANT_ID'),

    'google_pay_gateway' => env('MERCADOPAGO_GOOGLE_PAY_GATEWAY', 'example'),

    'google_pay_gateway_merchant_id' => env('MERCADOPAGO_GOOGLE_PAY_GATEWAY_MERCHANT_ID'),

    'google_pay_environment' => env('MERCADOPAGO_GOOGLE_PAY_ENVIRONMENT', 'test'),

];
