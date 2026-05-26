<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscriber_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('payer_email');
            $table->string('mercadopago_preapproval_id')->nullable()->unique();
            $table->string('external_reference')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('next_payment_date')->nullable();
            $table->timestamps();

            $table->unique(['creator_user_id', 'subscriber_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_subscriptions');
    }
};
