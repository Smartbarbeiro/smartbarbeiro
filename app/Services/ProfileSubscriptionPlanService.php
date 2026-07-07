<?php

namespace App\Services;

use App\Models\ProfileSubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscriptionPlanService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
    ) {}

    public function upsertForUser(User $user, array $data): ProfileSubscriptionPlan
    {
        $plan = $user->subscriptionPlan ?? new ProfileSubscriptionPlan(['user_id' => $user->id]);

        $plan->fill([
            'is_enabled' => (bool) $data['is_enabled'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'monthly_amount' => $data['monthly_amount'],
            'currency_id' => $data['currency_id'] ?? config('mercadopago.currency_id'),
        ]);

        if ($plan->is_enabled && $this->mercadoPago->isConfigured()) {
            $this->syncMercadoPagoPlan($user, $plan);
        }

        $plan->save();

        return $plan;
    }

    private function syncMercadoPagoPlan(User $user, ProfileSubscriptionPlan $plan): void
    {
        $backUrl = route('profile.public', $user->username);
        $reason = $plan->title.' — '.$user->name;

        try {
            if ($plan->mercadopago_preapproval_plan_id) {
                $this->mercadoPago->updatePreApprovalPlan(
                    $plan->mercadopago_preapproval_plan_id,
                    $reason,
                    (float) $plan->monthly_amount,
                    $plan->currency_id,
                    $backUrl,
                );
            } else {
                $mpPlan = $this->mercadoPago->createPreApprovalPlan(
                    $reason,
                    (float) $plan->monthly_amount,
                    $plan->currency_id,
                    $backUrl,
                );

                $plan->mercadopago_preapproval_plan_id = $mpPlan->id;
            }
        } catch (MPApiException $exception) {
            Log::error('Mercado Pago plan sync failed', [
                'user_id' => $user->id,
                'message' => $this->mercadoPago->apiExceptionMessage($exception),
            ]);

            throw $exception;
        }
    }
}
