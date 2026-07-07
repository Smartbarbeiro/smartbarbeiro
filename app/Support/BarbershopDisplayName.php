<?php

namespace App\Support;

class BarbershopDisplayName
{
    public static function from(?string $username, ?string $fallback = ''): string
    {
        $fromUsername = trim(str_replace('_', ' ', (string) $username));

        if ($fromUsername !== '') {
            return $fromUsername;
        }

        return trim((string) $fallback);
    }
}
