<?php

namespace Tests\Feature\Admin;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlatformSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_subscriptions_page_lists_paying_barbershops_and_total_revenue(): void
    {
        $plan = BarbershopPlatformPlan::current();
        $plan->update(['monthly_amount' => 49.90]);

        $payingA = User::factory()->create([
            'name' => 'Barbearia Alpha',
            'username' => 'alpha',
            'email' => 'alpha@example.com',
        ]);
        $payingA->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
        ]);

        $payingB = User::factory()->create([
            'name' => 'Barbearia Beta',
            'username' => 'beta',
            'email' => 'beta@example.com',
        ]);
        $payingB->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
        ]);

        $pending = User::factory()->create([
            'name' => 'Barbearia Pending',
            'username' => 'pending',
        ]);
        $pending->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $exempt = User::factory()->create([
            'name' => 'Barbearia Exempt',
            'username' => 'exempt',
            'platform_subscription_exempt' => true,
        ]);
        $exempt->platformSubscription()->update([
            'status' => BarbershopPlatformSubscription::STATUS_AUTHORIZED,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subscriptions/Index')
                ->where('isAdminPlatformSubscriptionsView', true)
                ->where('platformRevenueSummary.paying_barbershops_count', 2)
                ->where('platformRevenueSummary.amount', 99.8)
                ->where('platformRevenueSummary.formatted_amount', 'R$ 99,80')
                ->has('platformPayingBarbershops', 2)
                ->where('platformPayingBarbershops.0.name', 'Barbearia Beta')
                ->where('platformPayingBarbershops.1.name', 'Barbearia Alpha'));
    }

    public function test_non_admin_still_sees_regular_subscriptions_page(): void
    {
        $subscriber = User::factory()->customer()->create();

        $this->actingAs($subscriber)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isAdminPlatformSubscriptionsView', false)
                ->has('subscriptions'));
    }
}
