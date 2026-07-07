<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershop_memberships', function (Blueprint $table) {
            $table->unsignedTinyInteger('preferred_haircut_day')->nullable()->after('member_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('barbershop_memberships', function (Blueprint $table) {
            $table->dropColumn('preferred_haircut_day');
        });
    }
};
