<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcrylicQrOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_SHIPPED = 'shipped';

    protected $fillable = [
        'user_id',
        'status',
        'recipient_name',
        'phone',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'printed_at',
        'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PRINTED,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::activeStatuses(), true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusLabel(): string
    {
        return __('messages.acrylic_qr_status.'.$this->status);
    }

    public function formattedAddress(): string
    {
        $address = "{$this->street}, {$this->number}";

        if (filled($this->complement)) {
            $address .= " - {$this->complement}";
        }

        return "{$address}, {$this->neighborhood}, {$this->city}/{$this->state}, CEP {$this->postal_code}";
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'postal_code' => $this->postal_code,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'formatted_address' => $this->formattedAddress(),
            'printed_at' => $this->printed_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
