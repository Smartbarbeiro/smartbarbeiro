<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscriber_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('package_type', 32);
            $table->json('selected_addon_ids')->nullable();
            $table->decimal('monthly_total', 10, 2);
            $table->string('currency_id', 8)->default('BRL');
            $table->string('payer_email');
            $table->string('mercadopago_preapproval_id')->nullable();
            $table->string('external_reference')->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('next_payment_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['creator_user_id', 'subscriber_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_plan_subscriptions');
    }
};
