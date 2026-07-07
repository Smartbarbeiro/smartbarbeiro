<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopAppointment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'barbershop_user_id',
        'client_user_id',
        'barbershop_employee_id',
        'scheduled_at',
        'duration_minutes',
        'service_label',
        'package_type',
        'service_amount',
        'commission_percent',
        'commission_amount',
        'status',
        'client_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'service_amount' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barbershop_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(BarbershopEmployee::class, 'barbershop_employee_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CONFIRMED => 'Confirmado',
            self::STATUS_COMPLETED => 'Concluído',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_REJECTED => 'Recusado',
            default => $this->status,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'scheduled_time' => $this->scheduled_at->format('H:i'),
            'scheduled_date' => $this->scheduled_at->toDateString(),
            'duration_minutes' => $this->duration_minutes,
            'service_label' => $this->service_label,
            'package_type' => $this->package_type,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'client_notes' => $this->client_notes,
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ],
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'commission_percent' => (float) $this->employee->commission_percent,
            ] : null,
            'service_amount' => $this->service_amount !== null ? (float) $this->service_amount : null,
            'commission_amount' => $this->commission_amount !== null ? (float) $this->commission_amount : null,
        ];
    }
}
