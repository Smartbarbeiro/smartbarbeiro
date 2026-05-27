<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershop_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['barbershop_user_id', 'member_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_memberships');
    }
};
