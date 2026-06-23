<?php

namespace App\Services;

use App\Models\BarbershopPlatformPlan;
use Illuminate\Support\Facades\Log;
use MercadoPago\Exceptions\MPApiException;

class BarbershopPlatformPlanService
{
    public function __construct(
        private MercadoPagoService $mercadoPago,
    ) {}

    public function update(BarbershopPlatformPlan $plan, array $data): BarbershopPlatformPlan
    {
        $plan->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'monthly_amount' => $data['monthly_amount'],
            'currency_id' => $data['currency_id'] ?? config('mercadopago.currency_id', 'BRL'),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        if ($plan->is_active && $this->mercadoPago->isConfigured()) {
            $this->syncMercadoPagoPlan($plan);
        }

        $plan->save();

        return $plan;
    }

    private function syncMercadoPagoPlan(BarbershopPlatformPlan $plan): void
    {
        $backUrl = $this->subscriptionBackUrl('/assinatura/plataforma/retorno');
        $reason = $plan->title.' — Smart Barbeiro';

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
            Log::error('Mercado Pago platform plan sync failed', [
                'message' => $this->mercadoPago->apiExceptionMessage($exception),
            ]);

            throw $exception;
        }
    }

    private function subscriptionBackUrl(string $path): string
    {
        $base = rtrim((string) config('mercadopago.back_url', config('app.url')), '/');

        return $base.$path;
    }
}
