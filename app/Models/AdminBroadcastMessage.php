<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminBroadcastMessage extends Model
{
    protected $fillable = [
        'admin_user_id',
        'subject',
        'body',
        'send_email',
        'audience',
    ];

    protected function casts(): array
    {
        return [
            'send_email' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AdminBroadcastMessageRecipient::class);
    }
}
