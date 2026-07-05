<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePlanSubscription extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'creator_user_id',
        'subscriber_user_id',
        'package_type',
        'selected_addon_ids',
        'monthly_total',
        'currency_id',
        'payer_email',
        'mercadopago_preapproval_id',
        'stripe_subscription_id',
        'external_reference',
        'status',
        'next_payment_date',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_addon_ids' => 'array',
            'monthly_total' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePlanSubscriptionPayment::class);
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

    public function packageLabel(): string
    {
        return match ($this->package_type) {
            BarbershopServicePackage::TYPE_CUT => 'Corte Cabelo',
            BarbershopServicePackage::TYPE_CUT_BEARD => 'Corte Cabelo + Barba',
            default => $this->package_type,
        };
    }

    /**
     * @return list<string>
     */
    public function selectedAddonLabels(): array
    {
        $ids = $this->selected_addon_ids ?? [];

        if ($ids === []) {
            return [];
        }

        return BarbershopServiceAddon::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    public function formattedTotal(): string
    {
        return match ($this->currency_id) {
            'BRL' => 'R$ '.number_format((float) $this->monthly_total, 2, ',', '.'),
            default => $this->currency_id.' '.number_format((float) $this->monthly_total, 2),
        };
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

    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => 'service_plan',
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_active' => $this->isActive(),
            'is_cancellable' => $this->isCancellable(),
            'payer_email' => $this->payer_email,
            'package_type' => $this->package_type,
            'package_label' => $this->packageLabel(),
            'selected_addon_ids' => $this->selected_addon_ids ?? [],
            'monthly_total' => (float) $this->monthly_total,
            'formatted_total' => $this->formattedTotal(),
            'next_payment_date' => $this->next_payment_date?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
