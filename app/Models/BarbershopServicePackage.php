<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopServicePackage extends Model
{
    public const TYPE_CUT = 'cut';

    public const TYPE_CUT_BEARD = 'cut_beard';

    public const STANDARD_TYPES = [
        self::TYPE_CUT,
        self::TYPE_CUT_BEARD,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'monthly_price',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_CUT => 'Corte Cabelo',
            self::TYPE_CUT_BEARD => 'Corte Cabelo + Barba',
            default => $this->type,
        };
    }

    public function formattedPrice(): string
    {
        return 'R$ '.number_format((float) $this->monthly_price, 2, ',', '.');
    }

    public function toPayload(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label(),
            'monthly_price' => (float) $this->monthly_price,
            'formatted_price' => $this->formattedPrice(),
            'is_enabled' => $this->is_enabled,
        ];
    }
}
