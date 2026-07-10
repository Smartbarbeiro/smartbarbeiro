<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_connect_account_id')->nullable()->after('stripe_customer_id');
            $table->boolean('stripe_connect_charges_enabled')->default(false)->after('stripe_connect_account_id');
            $table->boolean('stripe_connect_payouts_enabled')->default(false)->after('stripe_connect_charges_enabled');
            $table->boolean('stripe_connect_details_submitted')->default(false)->after('stripe_connect_payouts_enabled');

            $table->index('stripe_connect_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_connect_account_id']);
            $table->dropColumn([
                'stripe_connect_account_id',
                'stripe_connect_charges_enabled',
                'stripe_connect_payouts_enabled',
                'stripe_connect_details_submitted',
            ]);
        });
    }
};
