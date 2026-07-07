<?php

namespace App\Services;

use App\Models\BarbershopEmployee;
use App\Models\User;
use Illuminate\Support\Collection;

class BarbershopEmployeeService
{
    /**
     * @return Collection<int, BarbershopEmployee>
     */
    public function employeesFor(User $barbershop): Collection
    {
        return $barbershop->employees()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function payloadFor(User $barbershop): array
    {
        return $this->employeesFor($barbershop)
            ->map(fn (BarbershopEmployee $employee) => $employee->toPayload())
            ->values()
            ->all();
    }

    /**
     * @param  array{name: string, commission_percent: float|int|string, color?: string|null, is_active?: bool}  $data
     */
    public function createFor(User $barbershop, array $data): BarbershopEmployee
    {
        $nextSortOrder = (int) $barbershop->employees()->max('sort_order') + 1;

        return $barbershop->employees()->create([
            'name' => trim($data['name']),
            'commission_percent' => $data['commission_percent'],
            'color' => $data['color'] ?? $this->defaultColorFor($barbershop),
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $nextSortOrder,
        ]);
    }

    /**
     * @param  array{name?: string, commission_percent?: float|int|string, color?: string|null, is_active?: bool}  $data
     */
    public function update(BarbershopEmployee $employee, array $data): BarbershopEmployee
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('commission_percent', $data)) {
            $attributes['commission_percent'] = $data['commission_percent'];
        }

        if (array_key_exists('color', $data)) {
            $attributes['color'] = $data['color'];
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = (bool) $data['is_active'];
        }

        if ($attributes !== []) {
            $employee->update($attributes);
        }

        return $employee->fresh();
    }

    public function defaultColorFor(User $barbershop): string
    {
        $count = $barbershop->employees()->count();
        $colors = BarbershopEmployee::DEFAULT_COLORS;

        return $colors[$count % count($colors)];
    }

    public function delete(BarbershopEmployee $employee): void
    {
        $employee->delete();
    }
}
