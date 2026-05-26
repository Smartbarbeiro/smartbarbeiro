<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site owner emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated list in ADMIN_EMAIL. Matching users are treated as
    | administrators and can access the user control panel.
    |
    */

    'owner_emails' => array_values(array_filter(array_map(
        static fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAIL', '')),
    ))),

];
