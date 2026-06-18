<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershop_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->boolean('send_email')->default(true);
            $table->timestamps();
        });

        Schema::create('barbershop_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['barbershop_message_id', 'recipient_user_id']);
            $table->index(['recipient_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_message_recipients');
        Schema::dropIfExists('barbershop_messages');
    }
};
