<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->boolean('send_email')->default(true);
            $table->string('audience');
            $table->timestamps();
        });

        Schema::create('admin_broadcast_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_broadcast_message_id')
                ->constrained(null, null, 'admin_broadcast_msg_rec_fk')
                ->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_broadcast_message_id', 'recipient_user_id'], 'admin_broadcast_message_recipient_unique');
            $table->index(['recipient_user_id', 'dismissed_at'], 'admin_broadcast_recipient_dismissed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_broadcast_message_recipients');
        Schema::dropIfExists('admin_broadcast_messages');
    }
};
