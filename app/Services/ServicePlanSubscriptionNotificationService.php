<?php

namespace App\Services;

use App\Mail\ClientServicePlanConfirmedMail;
use App\Models\ServicePlanSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ServicePlanSubscriptionNotificationService
{
    public function notifyAfterSync(
        ServicePlanSubscription $subscription,
        ?string $previousStatus = null,
    ): void {
        if (! $subscription->isActive()) {
            return;
        }

        $previousStatus ??= $subscription->getOriginal('status');

        $wasAuthorized = $previousStatus === ServicePlanSubscription::STATUS_AUTHORIZED;
        $newlyAuthorized = ! $wasAuthorized;
        $planChanged = $subscription->wasChanged([
            'package_type',
            'selected_addon_ids',
            'monthly_total',
        ]);

        if (! $newlyAuthorized && ! ($wasAuthorized && $planChanged)) {
            return;
        }

        if (! $this->claimConfirmationNotification($subscription)) {
            return;
        }

        $subscription->loadMissing(['creator', 'subscriber']);

        $recipientEmail = $subscription->subscriber?->email ?? $subscription->payer_email;

        if (! filled($recipientEmail)) {
            return;
        }

        Mail::to($recipientEmail)->send(new ClientServicePlanConfirmedMail(
            $subscription,
            $wasAuthorized && $planChanged,
        ));
    }

    private function claimConfirmationNotification(ServicePlanSubscription $subscription): bool
    {
        $fingerprint = hash('sha256', implode('|', [
            $subscription->id,
            $subscription->package_type,
            json_encode($subscription->selected_addon_ids ?? []),
            (string) $subscription->monthly_total,
            ServicePlanSubscription::STATUS_AUTHORIZED,
        ]));

        return Cache::add(
            'service-plan-confirmation-mail:'.$fingerprint,
            true,
            now()->addHour(),
        );
    }
}
