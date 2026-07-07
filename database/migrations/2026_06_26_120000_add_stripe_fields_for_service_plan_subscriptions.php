<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('oauth_id');
            $table->index('stripe_customer_id');
        });

        Schema::table('service_plan_subscriptions', function (Blueprint $table) {
            $table->string('stripe_subscription_id')->nullable()->after('mercadopago_preapproval_id');
            $table->index('stripe_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_plan_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropColumn('stripe_subscription_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn('stripe_customer_id');
        });
    }
};
