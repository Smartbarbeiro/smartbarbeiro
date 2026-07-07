<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->dropForeign(['client_user_id']);
        });

        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->foreignId('client_user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('client_user_id');
            $table->string('guest_phone', 30)->nullable()->after('guest_name');
            $table->foreign('client_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->dropForeign(['client_user_id']);
        });

        Schema::table('barbershop_appointments', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_phone']);
            $table->foreignId('client_user_id')->nullable(false)->change();
            $table->foreign('client_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
