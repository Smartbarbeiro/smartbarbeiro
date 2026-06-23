<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershop_platform_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('payer_email');
            $table->string('mercadopago_preapproval_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('next_payment_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        $barbershopUsers = DB::table('users')
            ->where('is_barbershop', true)
            ->where('is_admin', false)
            ->get(['id', 'email']);

        foreach ($barbershopUsers as $user) {
            DB::table('barbershop_platform_subscriptions')->insert([
                'barbershop_user_id' => $user->id,
                'payer_email' => $user->email,
                'status' => 'authorized',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_platform_subscriptions');
    }
};
