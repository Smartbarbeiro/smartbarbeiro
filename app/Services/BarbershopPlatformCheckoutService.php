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

        if ($subscription->isPaidActive()) {
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

        // Hosted plan init_point breaks the card-entry checkout UX for this
        // account. Pending auto_recurring preapproval restores add-card flow.
        // Pix is not offered on this redirect model in BR subscriptions.
        $payerEmail = app(PaymentEmailService::class)->preferredPayerEmail($barbershop);

        $preapproval = $this->mercadoPago->createSubscriptionCheckout(
            reason: $plan->title,
            payerEmail: $payerEmail,
            externalReference: $subscription->external_reference,
            backUrl: $backUrl,
            amount: (float) $plan->monthly_amount,
            currencyId: $plan->currency_id,
        );

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
        $existing = BarbershopPlatformSubscription::query()
            ->where('barbershop_user_id', $barbershop->id)
            ->first();

        if ($existing) {
            $existing->fill([
                'payer_email' => app(PaymentEmailService::class)->preferredPayerEmail($barbershop),
            ]);

            if (! filled($existing->external_reference)) {
                $existing->external_reference = $this->newExternalReference($barbershop);
            }

            $existing->save();

            return $existing;
        }

        $trialDays = max(0, (int) config('platform.trial_days', 30));

        return BarbershopPlatformSubscription::create([
            'barbershop_user_id' => $barbershop->id,
            'payer_email' => app(PaymentEmailService::class)->preferredPayerEmail($barbershop),
            'external_reference' => $this->newExternalReference($barbershop),
            'status' => BarbershopPlatformSubscription::STATUS_PENDING,
            'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
        ]);
    }

    private function newExternalReference(User $barbershop): string
    {
        return 'platform-'.$barbershop->id.'-'.Str::lower(Str::random(10));
    }
}
