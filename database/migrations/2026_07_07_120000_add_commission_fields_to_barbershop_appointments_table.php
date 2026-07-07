<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->decimal('service_amount', 10, 2)->nullable()->after('package_type');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('service_amount');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_percent');
        });
    }

    public function down(): void
    {
        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->dropColumn([
                'service_amount',
                'commission_percent',
                'commission_amount',
            ]);
        });
    }
};
