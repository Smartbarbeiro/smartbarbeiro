<?php

namespace App\Services;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use Illuminate\Support\Str;
use MercadoPago\Exceptions\MPApiException;

class BarbershopPlatformCheckoutService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
        private BarbershopPlatformPlanService $planService,
    ) {}

    /**
     * @return array{subscription: BarbershopPlatformSubscription, checkout_url: ?string}
     *
     * @throws MPApiException
     */
    public function startCheckout(User $barbershop): array
    {
        if (! $barbershop->isBarbershopAccount()) {
            throw new \InvalidArgumentException(__('messages.platform_subscription_barbershop_only'));
        }

        $plan = BarbershopPlatformPlan::current();

        if (! $plan->is_active) {
            throw new \InvalidArgumentException(__('messages.platform_plan_not_active'));
        }

        $subscription = $this->ensurePendingSubscription($barbershop);

        if ($subscription->isActive()) {
            throw new \InvalidArgumentException(__('messages.platform_subscription_already_active'));
        }

        if (! $this->mercadoPago->isConfigured()) {
            return [
                'subscription' => $subscription,
                'checkout_url' => null,
            ];
        }

        $this->mercadoPago->assertSandboxCheckoutUsers(
            app(PaymentEmailService::class)->preferredPayerEmail($barbershop),
        );

        $backUrl = rtrim((string) config('mercadopago.back_url', config('app.url')), '/')
            .'/assinatura/plataforma/retorno';

        if (! str_starts_with($backUrl, 'https://')) {
            throw new \InvalidArgumentException(__('messages.mercadopago_https_back_url_required'));
        }

        // Plan-hosted checkout can offer Pix / account money / cards in Brazil.
        // Creating a bare auto_recurring preapproval tends to show card-only.
        $this->planService->ensureSynced($plan);
        $plan->refresh();

        if (! filled($plan->mercadopago_preapproval_plan_id)) {
            throw new \InvalidArgumentException(__('messages.mercadopago_no_checkout_url'));
        }

        $mpPlan = $this->mercadoPago->getPreApprovalPlan(
            (string) $plan->mercadopago_preapproval_plan_id,
        );

        $checkoutUrl = $this->mercadoPago->planCheckoutUrl($mpPlan);

        return [
            'subscription' => $subscription->fresh(),
            'checkout_url' => $checkoutUrl,
        ];
    }

    public function ensurePendingSubscription(User $barbershop): BarbershopPlatformSubscription
    {
        return BarbershopPlatformSubscription::updateOrCreate(
            ['barbershop_user_id' => $barbershop->id],
            [
                'payer_email' => app(PaymentEmailService::class)->preferredPayerEmail($barbershop),
                'external_reference' => $this->externalReference($barbershop),
                'status' => BarbershopPlatformSubscription::STATUS_PENDING,
            ],
        );
    }

    private function externalReference(User $barbershop): string
    {
        $existing = BarbershopPlatformSubscription::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->value('external_reference');

        if (filled($existing)) {
            return (string) $existing;
        }

        return 'platform-'.$barbershop->id.'-'.Str::lower(Str::random(10));
    }
}
