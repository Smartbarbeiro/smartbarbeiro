<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopServiceAddon extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'monthly_price',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedPrice(): string
    {
        return 'R$ '.number_format((float) $this->monthly_price, 2, ',', '.');
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'monthly_price' => (float) $this->monthly_price,
            'formatted_price' => $this->formattedPrice(),
            'is_enabled' => $this->is_enabled,
            'sort_order' => $this->sort_order,
        ];
    }
}
