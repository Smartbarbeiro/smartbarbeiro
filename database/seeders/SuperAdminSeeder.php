<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL', '')));
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn('Skipping super admin seed: set ADMIN_EMAIL and ADMIN_PASSWORD in .env');

            return;
        }

        $localPart = Str::before($email, '@');
        $username = Str::slug((string) env('ADMIN_USERNAME', $localPart));

        if ($username === '') {
            $username = 'admin';
        }

        if (strlen($username) > 30) {
            $username = substr($username, 0, 30);
        }

        $baseUsername = $username;
        $counter = 1;

        while (
            User::where('username', $username)
                ->where('email', '!=', $email)
                ->exists()
        ) {
            $suffix = '-'.$counter;
            $username = substr($baseUsername, 0, 30 - strlen($suffix)).$suffix;
            $counter++;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Super Admin'),
                'username' => $username,
                'password' => $password,
                'is_admin' => true,
                'is_barbershop' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("Super admin ready: {$email} (profile: /barbearias/{$username})");
    }
}
