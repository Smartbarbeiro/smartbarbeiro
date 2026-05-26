<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSubscription extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'creator_user_id',
        'subscriber_user_id',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_AUTHORIZED];
    }

    public static function cancellableStatuses(): array
    {
        return [
            self::STATUS_AUTHORIZED,
            self::STATUS_PAUSED,
            self::STATUS_PENDING,
        ];
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, self::cancellableStatuses(), true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AUTHORIZED => 'Active',
            self::STATUS_PENDING => 'Pending payment',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_active' => $this->isActive(),
            'is_cancellable' => $this->isCancellable(),
            'payer_email' => $this->payer_email,
            'next_payment_date' => $this->next_payment_date?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
