<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarbershopEmployee extends Model
{
    protected $fillable = [
        'barbershop_user_id',
        'user_id',
        'name',
        'commission_percent',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BarbershopAppointment::class, 'barbershop_employee_id');
    }

    public function formattedCommissionPercent(): string
    {
        return number_format((float) $this->commission_percent, 2, ',', '.').'%';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'commission_percent' => (float) $this->commission_percent,
            'formatted_commission_percent' => $this->formattedCommissionPercent(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
