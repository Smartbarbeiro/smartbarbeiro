<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscriptionPlanController extends Controller
{
    public function update(
        Request $request,
        ProfileSubscriptionPlanService $planService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
        if (! $mercadoPago->isConfigured()) {
            return back()->withErrors([
                'mercadopago' => 'Configure MERCADOPAGO_ACCESS_TOKEN in your .env file to enable paid profiles.',
            ]);
        }

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_amount' => ['required', 'numeric', 'min:1', 'max:99999'],
        ]);

        try {
            $planService->upsertForUser($request->user(), [
                ...$validated,
                'currency_id' => config('mercadopago.currency_id'),
            ]);
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'mercadopago' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        }

        return back()->with('status', 'subscription-plan-updated');
    }
}
