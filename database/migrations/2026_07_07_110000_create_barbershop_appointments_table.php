<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershop_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('barbershop_employee_id')->nullable()->constrained('barbershop_employees')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('service_label');
            $table->string('package_type')->nullable();
            $table->string('status')->default('pending');
            $table->text('client_notes')->nullable();
            $table->timestamps();

            $table->index(['barbershop_user_id', 'scheduled_at']);
            $table->index(['barbershop_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_appointments');
    }
};
