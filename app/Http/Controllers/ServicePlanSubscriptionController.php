<?php

namespace App\Http\Controllers;

use App\Models\ServicePlanSubscription;
use App\Services\ServicePlanSubscriptionCancellationService;
use App\Services\StripeServicePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class ServicePlanSubscriptionController extends Controller
{
    public function destroy(
        ServicePlanSubscription $servicePlanSubscription,
        Request $request,
        ServicePlanSubscriptionCancellationService $cancellationService,
        StripeServicePlanService $stripe,
    ): RedirectResponse {
        $this->authorize('cancel', $servicePlanSubscription);

        try {
            $cancellationService->cancel($servicePlanSubscription, $request->user());
        } catch (ApiErrorException $exception) {
            return back()->withErrors([
                'cancel' => $stripe->apiExceptionMessage($exception),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors([
                'cancel' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'subscription-cancelled');
    }
}
