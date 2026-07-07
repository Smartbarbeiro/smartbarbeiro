<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarbershopPlatformPlan;
use App\Services\BarbershopPlatformPlanService;
use App\Services\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;

class BarbershopPlatformPlanController extends Controller
{
    public function edit(): Response
    {
        $plan = BarbershopPlatformPlan::current();

        return Inertia::render('Admin/PlatformPlan/Edit', [
            'plan' => [
                'title' => $plan->title,
                'description' => $plan->description,
                'monthly_amount' => (float) $plan->monthly_amount,
                'formatted_price' => $plan->formattedPrice(),
                'currency_id' => $plan->currency_id,
                'is_active' => $plan->is_active,
                'mercadopago_configured' => app(MercadoPagoService::class)->isConfigured(),
                'mercadopago_preapproval_plan_id' => $plan->mercadopago_preapproval_plan_id,
            ],
        ]);
    }

    public function update(
        Request $request,
        BarbershopPlatformPlanService $planService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'monthly_amount' => ['required', 'numeric', 'min:1', 'max:99999'],
            'is_active' => ['boolean'],
        ]);

        $plan = BarbershopPlatformPlan::current();

        try {
            $planService->update($plan, $validated);
        } catch (MPApiException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'monthly_amount' => $mercadoPago->apiExceptionMessage($exception),
                ]);
        }

        return back()->with('status', 'platform-plan-updated');
    }
}
