<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where('is_admin', true)
            ->update([
                'is_barbershop' => false,
                'username' => null,
            ]);
    }

    public function down(): void
    {
        // Admins are not restored as barbershop accounts on rollback.
    }
};
