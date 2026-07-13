<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopPlatformSubscription extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'barbershop_user_id',
        'payer_email',
        'mercadopago_preapproval_id',
        'external_reference',
        'status',
        'next_payment_date',
        'cancelled_at',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'next_payment_date' => 'datetime',
            'cancelled_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function isPaidActive(): bool
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    public function isOnTrial(): bool
    {
        if ($this->isPaidActive() || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->isPaidActive() || $this->isOnTrial();
    }

    public function trialDaysRemaining(): ?int
    {
        if (! $this->isOnTrial() || $this->trial_ends_at === null) {
            return null;
        }

        $days = (int) ceil(now()->floatDiffInDays($this->trial_ends_at, absolute: false));

        return max(0, $days);
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_AUTHORIZED];
    }

    public function statusLabel(): string
    {
        if ($this->isOnTrial()) {
            return __('messages.subscription_status.trial');
        }

        return match ($this->status) {
            self::STATUS_AUTHORIZED => __('messages.subscription_status.authorized'),
            self::STATUS_PENDING => __('messages.subscription_status.pending'),
            self::STATUS_PAUSED => __('messages.subscription_status.paused'),
            self::STATUS_CANCELLED => __('messages.subscription_status.cancelled'),
            default => ucfirst($this->status),
        };
    }
}
