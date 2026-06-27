<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plan_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_plan_subscription_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('billing_year');
            $table->unsignedTinyInteger('billing_month');
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency_id', 3)->default('BRL');
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['service_plan_subscription_id', 'billing_year', 'billing_month'],
                'service_plan_subscription_payments_period_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_plan_subscription_payments');
    }
};
