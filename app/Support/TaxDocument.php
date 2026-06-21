<?php

namespace App\Support;

class TaxDocument
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : null;
    }
}
