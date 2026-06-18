<?php

namespace App\Services;

use App\Models\BarbershopServiceAddon;
use App\Models\BarbershopServicePackage;
use App\Models\User;
use Illuminate\Support\Collection;

class BarbershopServicePlanService
{
    public function ensureDefaultPackages(User $user): void
    {
        if (! $user->isBarbershop()) {
            return;
        }

        foreach (BarbershopServicePackage::STANDARD_TYPES as $type) {
            $user->servicePackages()->firstOrCreate(
                ['type' => $type],
                [
                    'monthly_price' => 0,
                    'is_enabled' => true,
                ],
            );
        }
    }

    public function packagesPayload(User $user): array
    {
        $this->ensureDefaultPackages($user);

        return $user->servicePackages()
            ->orderByRaw("CASE type WHEN 'cut' THEN 0 WHEN 'cut_beard' THEN 1 ELSE 2 END")
            ->get()
            ->map(fn (BarbershopServicePackage $package) => $package->toPayload())
            ->values()
            ->all();
    }

    public function addonsPayload(User $user): array
    {
        return $user->serviceAddons()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (BarbershopServiceAddon $addon) => $addon->toPayload())
            ->values()
            ->all();
    }

    public function publicPlansPayload(User $user): array
    {
        $this->ensureDefaultPackages($user);

        return [
            'packages' => $user->servicePackages()
                ->where('is_enabled', true)
                ->where('monthly_price', '>', 0)
                ->orderByRaw("CASE type WHEN 'cut' THEN 0 WHEN 'cut_beard' THEN 1 ELSE 2 END")
                ->get()
                ->map(fn (BarbershopServicePackage $package) => $package->toPayload())
                ->values()
                ->all(),
            'addons' => $user->serviceAddons()
                ->where('is_enabled', true)
                ->where('monthly_price', '>', 0)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (BarbershopServiceAddon $addon) => $addon->toPayload())
                ->values()
                ->all(),
        ];
    }

    public function updateForUser(User $user, array $data): void
    {
        $this->ensureDefaultPackages($user);

        $packages = collect($data['packages'] ?? []);

        foreach (BarbershopServicePackage::STANDARD_TYPES as $type) {
            $packageData = $packages->get($type);

            if (! is_array($packageData)) {
                continue;
            }

            $user->servicePackages()
                ->where('type', $type)
                ->update([
                    'monthly_price' => $packageData['monthly_price'],
                    'is_enabled' => (bool) ($packageData['is_enabled'] ?? true),
                ]);
        }

        $this->syncAddons($user, $data['addons'] ?? [], $data['deleted_addon_ids'] ?? []);
    }

    private function syncAddons(User $user, array $addons, array $deletedIds): void
    {
        if ($deletedIds !== []) {
            $user->serviceAddons()
                ->whereIn('id', $deletedIds)
                ->delete();
        }

        foreach ($addons as $index => $addonData) {
            if (! is_array($addonData) || blank($addonData['name'] ?? null)) {
                continue;
            }

            $attributes = [
                'name' => $addonData['name'],
                'monthly_price' => $addonData['monthly_price'],
                'is_enabled' => (bool) ($addonData['is_enabled'] ?? true),
                'sort_order' => $addonData['sort_order'] ?? $index,
            ];

            if (! empty($addonData['id'])) {
                $user->serviceAddons()
                    ->whereKey($addonData['id'])
                    ->update($attributes);

                continue;
            }

            $user->serviceAddons()->create($attributes);
        }
    }

    public function calculateTotal(
        Collection $packages,
        Collection $addons,
        string $selectedPackageType,
        array $selectedAddonIds,
    ): float {
        $package = $packages->firstWhere('type', $selectedPackageType);

        if (! $package) {
            return 0;
        }

        $addonTotal = $addons
            ->whereIn('id', $selectedAddonIds)
            ->sum(fn (array $addon) => (float) $addon['monthly_price']);

        return (float) $package['monthly_price'] + $addonTotal;
    }

    /**
     * @return array{
     *     package: BarbershopServicePackage,
     *     addons: \Illuminate\Support\Collection<int, BarbershopServiceAddon>,
     *     monthly_total: float,
     *     reason: string
     * }
     */
    public function validateCheckoutSelection(User $barbershop, string $packageType, array $addonIds): array
    {
        $this->ensureDefaultPackages($barbershop);

        $package = $barbershop->servicePackages()
            ->where('type', $packageType)
            ->where('is_enabled', true)
            ->where('monthly_price', '>', 0)
            ->first();

        if (! $package) {
            throw new \InvalidArgumentException(__('messages.service_plan_invalid_package'));
        }

        $addonIds = array_values(array_unique(array_map('intval', $addonIds)));

        $addons = $barbershop->serviceAddons()
            ->whereIn('id', $addonIds)
            ->where('is_enabled', true)
            ->where('monthly_price', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($addons->count() !== count($addonIds)) {
            throw new \InvalidArgumentException(__('messages.service_plan_invalid_addons'));
        }

        $monthlyTotal = (float) $package->monthly_price + (float) $addons->sum('monthly_price');

        $addonNames = $addons->pluck('name')->filter()->all();
        $reason = $package->label();

        if ($addonNames !== []) {
            $reason .= ' + '.implode(', ', $addonNames);
        }

        $reason .= ' — '.$barbershop->name;

        return [
            'package' => $package,
            'addons' => $addons,
            'monthly_total' => $monthlyTotal,
            'reason' => $reason,
        ];
    }
}
