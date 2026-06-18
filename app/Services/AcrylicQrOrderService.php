<?php

namespace App\Services;

use App\Models\AcrylicQrOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AcrylicQrOrderService
{
    public function activeOrderFor(User $user): ?AcrylicQrOrder
    {
        if (! $user->isBarbershop()) {
            return null;
        }

        return $user->acrylicQrOrders()
            ->whereIn('status', AcrylicQrOrder::activeStatuses())
            ->latest()
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeOrderPayloadFor(User $user): ?array
    {
        return $this->activeOrderFor($user)?->toSummaryArray();
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function create(User $barbershop, array $data): AcrylicQrOrder
    {
        abort_unless($barbershop->isBarbershop(), 403);

        if ($this->activeOrderFor($barbershop)) {
            throw new InvalidArgumentException('Você já possui um pedido de QR acrílico em andamento.');
        }

        return AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$data,
        ]);
    }

    public function markPrinted(AcrylicQrOrder $order): AcrylicQrOrder
    {
        if ($order->status !== AcrylicQrOrder::STATUS_PENDING) {
            throw new InvalidArgumentException('Este pedido não pode ser marcado como impresso.');
        }

        $order->forceFill([
            'status' => AcrylicQrOrder::STATUS_PRINTED,
            'printed_at' => now(),
        ])->save();

        return $order->fresh(['user']);
    }

    public function markShipped(AcrylicQrOrder $order): AcrylicQrOrder
    {
        if ($order->status !== AcrylicQrOrder::STATUS_PRINTED) {
            throw new InvalidArgumentException('Este pedido não pode ser marcado como enviado.');
        }

        $order->forceFill([
            'status' => AcrylicQrOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
        ])->save();

        return $order->fresh(['user']);
    }

    /**
     * @return Collection<int, AcrylicQrOrder>
     */
    public function adminList(?string $status = null): Collection
    {
        return AcrylicQrOrder::query()
            ->with('user:id,name,username,email')
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();
    }
}
