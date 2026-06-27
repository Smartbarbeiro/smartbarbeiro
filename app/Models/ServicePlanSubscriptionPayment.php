<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePlanSubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'service_plan_subscription_id',
        'billing_year',
        'billing_month',
        'status',
        'amount',
        'currency_id',
        'stripe_invoice_id',
        'paid_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_year' => 'integer',
            'billing_month' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ServicePlanSubscription::class, 'service_plan_subscription_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function periodLabel(): string
    {
        $date = now()
            ->setDate($this->billing_year, $this->billing_month, 1)
            ->locale(app()->getLocale());

        return mb_convert_case($date->translatedFormat('F/Y'), MB_CASE_TITLE, 'UTF-8');
    }

    public function formattedAmount(): string
    {
        return match (strtoupper($this->currency_id)) {
            'BRL' => 'R$ '.number_format((float) $this->amount, 2, ',', '.'),
            default => $this->currency_id.' '.number_format((float) $this->amount, 2),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => __('messages.service_plan_payment_status.paid'),
            self::STATUS_FAILED => __('messages.service_plan_payment_status.failed'),
            self::STATUS_OVERDUE => __('messages.service_plan_payment_status.overdue'),
            default => __('messages.service_plan_payment_status.pending'),
        };
    }

    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'billing_year' => $this->billing_year,
            'billing_month' => $this->billing_month,
            'period_label' => $this->periodLabel(),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_paid' => $this->isPaid(),
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formattedAmount(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'download_url' => $this->isPaid()
                ? route('service-plan-payments.nota-fiscal', $this)
                : null,
        ];
    }
}
