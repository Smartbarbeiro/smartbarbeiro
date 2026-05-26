<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfileSubscriptionPlan extends Model
{
    protected $fillable = [
        'user_id',
        'is_enabled',
        'title',
        'description',
        'monthly_amount',
        'currency_id',
        'mercadopago_preapproval_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'monthly_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProfileSubscription::class, 'creator_user_id', 'user_id');
    }

    public function formattedPrice(): string
    {
        return match ($this->currency_id) {
            'BRL' => 'R$ '.number_format((float) $this->monthly_amount, 2, ',', '.'),
            default => $this->currency_id.' '.number_format((float) $this->monthly_amount, 2),
        };
    }
}
