<?php

namespace App\Services;

use App\Models\BarbershopMembership;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use Illuminate\Support\Str;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Resources\PreApproval;

class ServicePlanCheckoutService
{
    public function __construct(
        private BarbershopServicePlanService $servicePlanService,
        private MercadoPagoService $mercadoPago,
    ) {}

    /**
     * @return array{subscription: ServicePlanSubscription, checkout_url: string}
     *
     * @throws MPApiException
     */
    public function startCheckout(
        User $barbershop,
        User $subscriber,
        string $packageType,
        array $addonIds,
    ): array {
        if (! $this->mercadoPago->isConfigured()) {
            throw new \InvalidArgumentException(__('messages.payments_not_configured'));
        }

        $selection = $this->servicePlanService->validateCheckoutSelection(
            $barbershop,
            $packageType,
            $addonIds,
        );

        $subscription = $this->savePendingSelection(
            $barbershop,
            $subscriber,
            $packageType,
            $addonIds,
        );

        if (! $subscription->external_reference) {
            $subscription->update([
                'external_reference' => $this->externalReference($barbershop, $subscriber),
            ]);
        }

        $this->mercadoPago->assertSandboxCheckoutUsers($subscriber->email);

        $backUrl = route('service-plan.subscribe.return', $barbershop->username);

        $preapproval = $this->mercadoPago->createSubscriptionCheckout(
            reason: $selection['reason'],
            payerEmail: $subscriber->email,
            externalReference: $subscription->external_reference,
            backUrl: $backUrl,
            amount: (float) $selection['monthly_total'],
            currencyId: $subscription->currency_id,
        );

        $subscription->update([
            'mercadopago_preapproval_id' => $preapproval->id,
            'status' => $this->mercadoPago->mapPreApprovalStatus($preapproval->status),
        ]);

        if (! $this->mercadoPago->checkoutUrl($preapproval)) {
            throw new \RuntimeException(__('messages.mercadopago_no_checkout_url'));
        }

        return [
            'subscription' => $subscription,
            'checkout_url' => $this->mercadoPago->checkoutUrl($preapproval),
        ];
    }

    public function savePendingSelection(
        User $barbershop,
        User $subscriber,
        string $packageType,
        array $addonIds,
    ): ServicePlanSubscription {
        if ($subscriber->id === $barbershop->id) {
            throw new \InvalidArgumentException(__('messages.service_plan_owner_cannot_subscribe'));
        }

        $selection = $this->servicePlanService->validateCheckoutSelection(
            $barbershop,
            $packageType,
            $addonIds,
        );

        $existing = ServicePlanSubscription::query()
            ->where('creator_user_id', $barbershop->id)
            ->where('subscriber_user_id', $subscriber->id)
            ->whereIn('status', ServicePlanSubscription::activeStatuses())
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException(__('messages.service_plan_already_subscribed'));
        }

        return ServicePlanSubscription::updateOrCreate(
            [
                'creator_user_id' => $barbershop->id,
                'subscriber_user_id' => $subscriber->id,
            ],
            [
                'package_type' => $selection['package']->type,
                'selected_addon_ids' => $selection['addons']->pluck('id')->all(),
                'monthly_total' => $selection['monthly_total'],
                'currency_id' => config('mercadopago.currency_id', 'BRL'),
                'payer_email' => $subscriber->email,
                'external_reference' => $this->externalReference($barbershop, $subscriber),
                'status' => ServicePlanSubscription::STATUS_PENDING,
            ],
        );
    }

    private function externalReference(User $barbershop, User $subscriber): string
    {
        return 'service-plan-'.$barbershop->id.'-'.$subscriber->id.'-'.Str::lower(Str::random(8));
    }

    public function ensureMembership(ServicePlanSubscription $subscription): void
    {
        if (! $subscription->isActive()) {
            return;
        }

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $subscription->creator_user_id,
            'member_user_id' => $subscription->subscriber_user_id,
        ]);
    }
}
