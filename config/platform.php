<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform plan trial
    |--------------------------------------------------------------------------
    |
    | New barbershop signups get free platform access for this many days before
    | Mercado Pago payment is required. Existing rows stay unpaid/pending with
    | a null trial_ends_at and are unchanged.
    |
    */

    'trial_days' => (int) env('PLATFORM_TRIAL_DAYS', 30),

];
