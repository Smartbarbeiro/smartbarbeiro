<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopMembership extends Model
{
    protected $fillable = [
        'barbershop_user_id',
        'member_user_id',
        'preferred_haircut_day',
    ];

    protected function casts(): array
    {
        return [
            'preferred_haircut_day' => 'integer',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }
}
