<?php

namespace App\Services;

use App\Models\BarbershopPlatformPlan;
use App\Models\BarbershopPlatformSubscription;
use App\Models\User;
use Illuminate\Support\Str;
use MercadoPago\Exceptions\MPApiException;

class BarbershopPlatformCheckoutService
{
    public const MODE_CARD = 'card';

    public const MODE_PLAN = 'plan';

    public function __construct(
        private MercadoPagoService $mercadoPago,
        private BarbershopPlatformPlanService $planService,
    ) {}

    /**
     * @return array{subscription: BarbershopPlatformSubscription, checkout_url: ?string}
     *
     * @throws MPApiException
     */
    public function startCheckout(User $barbershop, string $mode = self::MODE_CARD): array
    {
        if (! $barbershop->isBarbershopAccount()) {
            throw new \InvalidArgumentException(__('messages.platform_subscription_barbershop_only'));
        }

        $mode = in_array($mode, [self::MODE_CARD, self::MODE_PLAN], true)
            ? $mode
            : self::MODE_CARD;

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

        $this->planService->ensureSynced($plan);
        $plan->refresh();

        if ($mode === self::MODE_PLAN) {
            return $this->startPlanHostedCheckout($plan, $subscription);
        }

        $payerEmail = app(PaymentEmailService::class)->preferredPayerEmail($barbershop);

        return $this->startCardCheckout($plan, $subscription, $backUrl, $payerEmail);
    }

    /**
     * Plan-hosted checkout (`preapproval_plan_id`) is where Mercado Pago shows
     * Pix Automático / boleto when enabled on the subscription plan.
     *
     * @return array{subscription: BarbershopPlatformSubscription, checkout_url: ?string}
     */
    private function startPlanHostedCheckout(
        BarbershopPlatformPlan $plan,
        BarbershopPlatformSubscription $subscription,
    ): array {
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

    /**
     * Subscription pending checkout (`preapproval_id`) keeps reliable card entry.
     *
     * @return array{subscription: BarbershopPlatformSubscription, checkout_url: ?string}
     *
     * @throws MPApiException
     */
    private function startCardCheckout(
        BarbershopPlatformPlan $plan,
        BarbershopPlatformSubscription $subscription,
        string $backUrl,
        string $payerEmail,
    ): array {
        $planId = filled($plan->mercadopago_preapproval_plan_id)
            ? (string) $plan->mercadopago_preapproval_plan_id
            : null;

        try {
            $preapproval = $this->mercadoPago->createSubscriptionCheckout(
                reason: $plan->title,
                payerEmail: $payerEmail,
                externalReference: $subscription->external_reference,
                backUrl: $backUrl,
                preapprovalPlanId: $planId,
                amount: $planId ? null : (float) $plan->monthly_amount,
                currencyId: $planId ? null : $plan->currency_id,
            );
        } catch (MPApiException $exception) {
            if (! $planId) {
                throw $exception;
            }

            $preapproval = $this->mercadoPago->createSubscriptionCheckout(
                reason: $plan->title,
                payerEmail: $payerEmail,
                externalReference: $subscription->external_reference,
                backUrl: $backUrl,
                amount: (float) $plan->monthly_amount,
                currencyId: $plan->currency_id,
            );
        }

        $subscription->update([
            'mercadopago_preapproval_id' => $preapproval->id,
            'status' => $this->mercadoPago->mapPreApprovalStatus($preapproval->status),
        ]);

        return [
            'subscription' => $subscription->fresh(),
            'checkout_url' => $this->mercadoPago->checkoutUrl($preapproval),
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
