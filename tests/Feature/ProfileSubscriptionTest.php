<?php

namespace Tests\Feature;

use App\Models\BarbershopServicePackage;
use App\Models\ProfileSubscription;
use App\Models\ProfileSubscriptionPlan;
use App\Models\User;
use App\Services\ProfileAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_is_open_when_plan_disabled(): void
    {
        $creator = User::factory()->create();

        $this->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Public')
                ->where('canView', true));
    }

    public function test_public_profile_is_locked_for_guests_when_plan_enabled(): void
    {
        $creator = User::factory()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        $creator->servicePackages()
            ->where('type', BarbershopServicePackage::TYPE_CUT)
            ->update(['monthly_price' => 89.9, 'is_enabled' => true]);

        $this->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canView', false)
                ->where('subscriptionPlan.is_enabled', true)
                ->has('servicePlans.packages', 1)
                ->where('servicePlans.packages.0.formatted_price', 'R$ 89,90'));
    }

    public function test_subscriber_can_view_paid_profile(): void
    {
        $creator = User::factory()->create();
        $subscriber = User::factory()->customer()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'test-ref-1',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->assertTrue(
            app(ProfileAccessService::class)->canViewProfile($creator, $subscriber),
        );

        $this->actingAs($subscriber)
            ->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canView', true)
                ->where('activeSubscription.is_active', true));
    }

    public function test_pending_subscriber_sees_subscription_for_payment_update(): void
    {
        $creator = User::factory()->create();
        $subscriber = User::factory()->customer()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'test-ref-pending',
            'status' => ProfileSubscription::STATUS_PENDING,
        ]);

        $this->actingAs($subscriber)
            ->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canView', false)
                ->where('activeSubscription.status', ProfileSubscription::STATUS_PENDING)
                ->where('activeSubscription.is_active', false));
    }

    public function test_owner_can_update_subscription_plan_without_mercadopago_token(): void
    {
        $user = User::factory()->create();

        config(['mercadopago.access_token' => null]);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.subscription-plan.update'), [
                'is_enabled' => false,
                'title' => 'Monthly access',
                'description' => 'Test',
                'monthly_amount' => 25,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('mercadopago');

        config(['mercadopago.access_token' => 'TEST_TOKEN']);

        $this->actingAs($user)
            ->put(route('profile.subscription-plan.update'), [
                'is_enabled' => false,
                'title' => 'Monthly access',
                'description' => 'Test',
                'monthly_amount' => 25,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profile_subscription_plans', [
            'user_id' => $user->id,
            'title' => 'Monthly access',
            'is_enabled' => false,
        ]);
    }

    public function test_subscriber_can_cancel_subscription(): void
    {
        $creator = User::factory()->create();
        $subscriber = User::factory()->customer()->create();

        $subscription = ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'cancel-test-ref',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($subscriber)
            ->delete(route('subscriptions.destroy', $subscription))
            ->assertRedirect()
            ->assertSessionHas('status', 'subscription-cancelled');

        $this->assertDatabaseHas('profile_subscriptions', [
            'id' => $subscription->id,
            'status' => ProfileSubscription::STATUS_CANCELLED,
        ]);

        $this->assertNotNull($subscription->fresh()->cancelled_at);
    }

    public function test_subscriber_cannot_cancel_another_users_subscription(): void
    {
        $creator = User::factory()->create();
        $subscriber = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $subscription = ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'cancel-test-ref-2',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($other)
            ->delete(route('subscriptions.destroy', $subscription))
            ->assertForbidden();
    }

    public function test_cancelled_subscription_revokes_profile_access(): void
    {
        $creator = User::factory()->create();
        $subscriber = User::factory()->customer()->create();

        ProfileSubscriptionPlan::create([
            'user_id' => $creator->id,
            'is_enabled' => true,
            'title' => 'VIP access',
            'monthly_amount' => 19.90,
            'currency_id' => 'BRL',
        ]);

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'cancel-test-ref-3',
            'status' => ProfileSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $this->actingAs($subscriber)
            ->get(route('profile.public', $creator->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canView', false));
    }

    public function test_subscriptions_index_lists_user_subscriptions(): void
    {
        $creator = User::factory()->create(['name' => 'Creator User']);
        $subscriber = User::factory()->customer()->create();

        ProfileSubscription::create([
            'creator_user_id' => $creator->id,
            'subscriber_user_id' => $subscriber->id,
            'payer_email' => $subscriber->email,
            'external_reference' => 'index-test-ref',
            'status' => ProfileSubscription::STATUS_AUTHORIZED,
        ]);

        $this->actingAs($subscriber)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Subscriptions/Index')
                ->has('subscriptions', 1)
                ->where('subscriptions.0.creator.name', 'Creator User'));
    }
}
