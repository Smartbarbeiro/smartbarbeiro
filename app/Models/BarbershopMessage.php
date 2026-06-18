<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarbershopMessage extends Model
{
    protected $fillable = [
        'barbershop_user_id',
        'subject',
        'body',
        'send_email',
    ];

    protected function casts(): array
    {
        return [
            'send_email' => 'boolean',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BarbershopMessageRecipient::class);
    }
}
