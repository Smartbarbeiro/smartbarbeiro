<?php

namespace Tests\Feature;

use App\Models\ProfileSubscription;
use App\Models\ProfileSubscriptionPlan;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopClientsSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_barbershop_clients_page_shows_expected_monthly_revenue(): void
    {
        $barbershop = User::factory()->create();
        $customerOne = User::factory()->customer()->create();
        $customerTwo = User::factory()->customer()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $barbershop->id,
            'is_enabled' => true,
            'title' => 'Perfil VIP',
            'monthly_amount' => 20,
            'currency_id' => 'BRL',
        ]);

        ProfileSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customerOne->id,
            'payer_email' => $customerOne->email,
            'external_reference' => 'profile-sub-revenue-test',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $customerTwo->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99,
            'currency_id' => 'BRL',
            'payer_email' => $customerTwo->email,
            'external_reference' => 'service-plan-revenue-test',
            'status' => ServicePlanSubscription::STATUS_AUTHORIZED,
        ]);

        ServicePlanSubscription::create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => User::factory()->customer()->create()->id,
            'package_type' => 'cut_beard',
            'selected_addon_ids' => [],
            'monthly_total' => 50,
            'currency_id' => 'BRL',
            'payer_email' => 'cancelled@example.com',
            'external_reference' => 'service-plan-cancelled-test',
            'status' => ServicePlanSubscription::STATUS_CANCELLED,
        ]);

        $this->actingAs($barbershop)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subscriptions/Index')
                ->where('isBarbershopClientsView', true)
                ->where('expectedMonthlyRevenue.amount', 119.0)
                ->where('expectedMonthlyRevenue.formatted_amount', 'R$ 119,00')
                ->where('expectedMonthlyRevenue.active_plans_count', 2)
                ->has('subscriptions', 3));
    }
}
