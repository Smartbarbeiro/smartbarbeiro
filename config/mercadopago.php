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

    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),

    'currency_id' => env('MERCADOPAGO_CURRENCY', 'BRL'),

    'runtime_environment' => env('MERCADOPAGO_RUNTIME', 'server'),

    /*
    | Mercado Pago requires an HTTPS back_url for subscription checkout.
    | For local testing, use an ngrok URL: MERCADOPAGO_BACK_URL=https://xxxx.ngrok-free.app
    */
    'back_url' => env('MERCADOPAGO_BACK_URL', env('APP_URL')),

];
