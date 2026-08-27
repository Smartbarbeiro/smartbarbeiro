<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershop_platform_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('monthly_amount', 10, 2);
            $table->string('currency_id', 3)->default('BRL');
            $table->boolean('is_active')->default(true);
            $table->string('mercadopago_preapproval_plan_id')->nullable();
            $table->timestamps();
        });

        DB::table('barbershop_platform_plans')->insert([
            'title' => 'Plano Único',
            'description' => 'Assinatura mensal da plataforma Tesora para barbearias.',
            'monthly_amount' => 49.90,
            'currency_id' => 'BRL',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_platform_plans');
    }
};
