<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\BarbershopServicePackage;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BarbershopScheduleForecastService
{
    public function greetingFor(Carbon $moment): string
    {
        $hour = (int) $moment->format('G');

        if ($hour < 12) {
            return 'Bom dia!';
        }

        if ($hour < 18) {
            return 'Boa tarde!';
        }

        return 'Boa noite!';
    }

    /**
     * @return array{
     *     greeting: string,
     *     formatted_date: string,
     *     today: array<string, mixed>,
     *     upcoming_days: list<array<string, mixed>>
     * }
     */
    public function dashboardPayload(User $barbershop, ?Carbon $reference = null): array
    {
        $reference ??= now();

        $todayForecast = $this->forecastForDate($barbershop, $reference->copy()->startOfDay());

        $upcomingDays = collect(range(0, 4))
            ->map(fn (int $offset) => $this->forecastForDate(
                $barbershop,
                $reference->copy()->startOfDay()->addDays($offset),
            ))
            ->values()
            ->all();

        return [
            'greeting' => $this->greetingFor($reference),
            'formatted_date' => $reference
                ->locale('pt_BR')
                ->translatedFormat('j \d\e F \d\e Y'),
            'today' => $todayForecast,
            'upcoming_days' => $upcomingDays,
        ];
    }

    /**
     * @return array{
     *     date: string,
     *     day: int,
     *     month_label: string,
     *     weekday_label: string,
     *     is_today: bool,
     *     expected_cuts: int,
     *     expected_beards: int,
     *     total_services: int
     * }
     */
    public function forecastForDate(User $barbershop, Carbon $date): array
    {
        $subscriptions = $this->subscriptionsForPreferredDay($barbershop, $date->day);

        $expectedCuts = 0;
        $expectedBeards = 0;
        $addonServices = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->package_type === BarbershopServicePackage::TYPE_CUT) {
                $expectedCuts++;
            }

            if ($subscription->package_type === BarbershopServicePackage::TYPE_CUT_BEARD) {
                $expectedCuts++;
                $expectedBeards++;
            }

            $addonServices += count($subscription->selected_addon_ids ?? []);
        }

        return [
            'date' => $date->toDateString(),
            'day' => $date->day,
            'month_label' => mb_strtoupper($date->locale('pt_BR')->translatedFormat('M')),
            'weekday_label' => mb_strtoupper($date->locale('pt_BR')->translatedFormat('ddd')),
            'is_today' => $date->isToday(),
            'expected_cuts' => $expectedCuts,
            'expected_beards' => $expectedBeards,
            'total_services' => $expectedCuts + $expectedBeards + $addonServices,
        ];
    }

    /**
     * @return Collection<int, ServicePlanSubscription>
     */
    private function subscriptionsForPreferredDay(User $barbershop, int $dayOfMonth): Collection
    {
        $memberIds = BarbershopMembership::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->where('preferred_haircut_day', $dayOfMonth)
            ->pluck('member_user_id');

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->whereIn('subscriber_user_id', $memberIds)
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->get();
    }
}
