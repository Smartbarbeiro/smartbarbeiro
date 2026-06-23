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
    ];

    protected function casts(): array
    {
        return [
            'next_payment_date' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_AUTHORIZED];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AUTHORIZED => __('messages.subscription_status.authorized'),
            self::STATUS_PENDING => __('messages.subscription_status.pending'),
            self::STATUS_PAUSED => __('messages.subscription_status.paused'),
            self::STATUS_CANCELLED => __('messages.subscription_status.cancelled'),
            default => ucfirst($this->status),
        };
    }
}
