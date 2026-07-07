<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminBroadcastMessageRecipient extends Model
{
    protected $fillable = [
        'admin_broadcast_message_id',
        'recipient_user_id',
        'read_at',
        'dismissed_at',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AdminBroadcastMessage::class, 'admin_broadcast_message_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    public function dismiss(): void
    {
        $this->markAsRead();

        if ($this->dismissed_at !== null) {
            return;
        }

        $this->forceFill(['dismissed_at' => now()])->save();
    }
}
