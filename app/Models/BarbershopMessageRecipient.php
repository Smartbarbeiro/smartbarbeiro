<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopMessageRecipient extends Model
{
    protected $fillable = [
        'barbershop_message_id',
        'recipient_user_id',
        'read_at',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(BarbershopMessage::class, 'barbershop_message_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->isRead()) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }
}
