<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class UsernameGenerator
{
    public function uniqueFrom(string $source, ?string $preferred = null): string
    {
        $base = Str::slug($preferred ?? $source);

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.'-'.$counter;
            $counter++;
        }

        return $username;
    }
}
