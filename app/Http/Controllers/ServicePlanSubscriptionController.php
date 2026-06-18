<?php

namespace App\Http\Controllers;

use App\Models\ServicePlanSubscription;
use App\Services\MercadoPagoService;
use App\Services\ServicePlanSubscriptionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\MPApiException;

class ServicePlanSubscriptionController extends Controller
{
    public function destroy(
        ServicePlanSubscription $servicePlanSubscription,
        Request $request,
        ServicePlanSubscriptionCancellationService $cancellationService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
        $this->authorize('cancel', $servicePlanSubscription);

        try {
            $cancellationService->cancel($servicePlanSubscription, $request->user());
        } catch (MPApiException $exception) {
            return back()->withErrors([
                'cancel' => $mercadoPago->apiExceptionMessage($exception),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'cancel' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'subscription-cancelled');
    }
}
