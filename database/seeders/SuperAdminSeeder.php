<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Super Admin'),
                'username' => null,
                'password' => $password,
                'is_admin' => true,
                'is_barbershop' => false,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("Super admin ready: {$email}");
    }
}
