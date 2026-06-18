<?php

namespace Tests\Feature;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\BarbershopScheduleForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BarbershopScheduleForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_counts_active_subscriptions_on_preferred_day(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');

        $barbershop = User::factory()->create();
        $cutClient = User::factory()->customer()->create();
        $comboClient = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $cutClient->id,
            'preferred_haircut_day' => 10,
        ]);

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $comboClient->id,
            'preferred_haircut_day' => 10,
        ]);

        ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $cutClient->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [1],
            'monthly_total' => 89.90,
            'currency_id' => 'BRL',
            'payer_email' => $cutClient->email,
            'external_reference' => 'schedule-cut-1',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $comboClient->id,
            'package_type' => 'cut_beard',
            'selected_addon_ids' => [],
            'monthly_total' => 129.90,
            'currency_id' => 'BRL',
            'payer_email' => $comboClient->email,
            'external_reference' => 'schedule-combo-1',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        $forecast = app(BarbershopScheduleForecastService::class)
            ->forecastForDate($barbershop, now());

        $this->assertSame(2, $forecast['expected_cuts']);
        $this->assertSame(1, $forecast['expected_beards']);
        $this->assertSame(4, $forecast['total_services']);
    }

    public function test_dashboard_includes_schedule_payload_for_barbershop(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');

        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('schedule.greeting', 'Bom dia!')
                ->where('schedule.today.expected_cuts', 0)
                ->has('schedule.upcoming_days', 5));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
