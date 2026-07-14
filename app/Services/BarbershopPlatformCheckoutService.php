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

        // Pix / boleto toggles in the Mercado Pago plan panel only apply when
        // the pending subscription is linked to that preapproval_plan_id.
        // Use the subscription init_point (not the plan-hosted URL) so card
        // entry keeps working.
        $this->planService->ensureSynced($plan);
        $plan->refresh();

        $payerEmail = app(PaymentEmailService::class)->preferredPayerEmail($barbershop);
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
            // Some accounts reject pending+plan without a card token; fall back
            // to standalone auto_recurring so checkout still works (cards).
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
