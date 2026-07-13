<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('barbershop_platform_subscriptions', 'trial_ends_at')) {
            return;
        }

        Schema::table('barbershop_platform_subscriptions', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('barbershop_platform_subscriptions', 'trial_ends_at')) {
            return;
        }

        Schema::table('barbershop_platform_subscriptions', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('cancelled_at');
        });
    }
};
