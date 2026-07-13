<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarbershopPlatformPlan extends Model
{
    protected $fillable = [
        'title',
        'description',
        'monthly_amount',
        'currency_id',
        'is_active',
        'mercadopago_preapproval_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->orderBy('id')->firstOrCreate([], [
            'title' => 'Plano Único',
            'description' => 'Assinatura mensal da plataforma Smart Barbeiro para barbearias.',
            'monthly_amount' => 49.90,
            'currency_id' => config('mercadopago.currency_id', 'BRL'),
            'is_active' => true,
        ]);
    }

    public function formattedPrice(): string
    {
        return match ($this->currency_id) {
            'BRL' => 'R$ '.number_format((float) $this->monthly_amount, 2, ',', '.'),
            default => $this->currency_id.' '.number_format((float) $this->monthly_amount, 2),
        };
    }

    public function toPublicArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'monthly_amount' => (float) $this->monthly_amount,
            'formatted_price' => $this->formattedPrice(),
            'currency_id' => $this->currency_id,
            'is_active' => $this->is_active,
            'trial_days' => max(0, (int) config('platform.trial_days', 30)),
        ];
    }
}
