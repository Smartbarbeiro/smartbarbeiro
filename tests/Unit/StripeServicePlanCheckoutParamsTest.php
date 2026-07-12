<?php

namespace Tests\Unit;

use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\StripeServicePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeServicePlanCheckoutParamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_checkout_session_includes_card_pix_and_mandate_options(): void
    {
        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
            'stripe.currency' => 'brl',
            'stripe.pix_enabled' => true,
            'stripe.connect_enabled' => false,
        ]);

        $barbershop = User::factory()->create(['is_barbershop' => true]);
        $subscriber = User::factory()->create(['is_barbershop' => false]);

        $subscription = ServicePlanSubscription::query()->create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $subscriber->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 99.9,
            'currency_id' => 'BRL',
            'payer_email' => $subscriber->email,
            'external_reference' => 'service-plan-test-ref',
            'status' => ServicePlanSubscription::STATUS_PENDING,
        ]);

        $params = app(StripeServicePlanService::class)->webCheckoutSessionParams(
            $subscription,
            'cus_test',
            [
                'reason' => 'Corte - Barbearia Teste',
                'monthly_total' => 99.9,
            ],
            'https://example.test/success',
            'https://example.test/cancel',
            $barbershop,
        );

        $this->assertSame('subscription', $params['mode']);
        $this->assertSame('pt-BR', $params['locale']);
        $this->assertSame(['card', 'pix'], $params['payment_method_types']);
        $this->assertSame(
            11988,
            $params['payment_method_options']['pix']['mandate_options']['amount'],
        );
        $this->assertSame(
            'maximum',
            $params['payment_method_options']['pix']['mandate_options']['amount_type'],
        );
        $this->assertSame(
            'monthly',
            $params['payment_method_options']['pix']['mandate_options']['payment_schedule'],
        );
        $this->assertSame(
            'brl',
            $params['payment_method_options']['pix']['mandate_options']['currency'],
        );
    }

    public function test_web_checkout_session_omits_pix_when_disabled(): void
    {
        config([
            'stripe.key' => 'pk_test_fake',
            'stripe.secret' => 'sk_test_fake',
            'stripe.currency' => 'brl',
            'stripe.pix_enabled' => false,
            'stripe.connect_enabled' => false,
        ]);

        $barbershop = User::factory()->create(['is_barbershop' => true]);
        $subscriber = User::factory()->create(['is_barbershop' => false]);

        $subscription = ServicePlanSubscription::query()->create([
            'creator_user_id' => $barbershop->id,
            'subscriber_user_id' => $subscriber->id,
            'package_type' => 'cut',
            'selected_addon_ids' => [],
            'monthly_total' => 50,
            'currency_id' => 'BRL',
            'payer_email' => $subscriber->email,
            'external_reference' => 'service-plan-test-ref-2',
            'status' => ServicePlanSubscription::STATUS_PENDING,
        ]);

        $params = app(StripeServicePlanService::class)->webCheckoutSessionParams(
            $subscription,
            'cus_test',
            [
                'reason' => 'Corte',
                'monthly_total' => 50,
            ],
            'https://example.test/success',
            'https://example.test/cancel',
            $barbershop,
        );

        $this->assertSame(['card'], $params['payment_method_types']);
        $this->assertArrayNotHasKey('payment_method_options', $params);
    }
}
