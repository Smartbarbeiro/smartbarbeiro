<?php

namespace App\Http\Controllers;

use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $profileSubscriptions = $request->user()
            ->profileSubscriptions()
            ->with('creator:id,name,username')
            ->latest()
            ->get()
            ->map(fn (ProfileSubscription $subscription) => [
                ...$subscription->toSummaryArray(),
                'creator' => [
                    'name' => $subscription->creator->name,
                    'username' => $subscription->creator->username,
                    'profile_url' => $subscription->creator->profileUrl(),
                ],
            ]);

        $servicePlanSubscriptions = $request->user()
            ->servicePlanSubscriptions()
            ->with('creator:id,name,username')
            ->latest()
            ->get()
            ->map(fn (ServicePlanSubscription $subscription) => [
                ...$subscription->toSummaryArray(),
                'creator' => [
                    'name' => $subscription->creator->name,
                    'username' => $subscription->creator->username,
                    'profile_url' => $subscription->creator->profileUrl(),
                ],
            ]);

        $subscriptions = $profileSubscriptions
            ->concat($servicePlanSubscriptions)
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'mercadopagoConfigured' => app(MercadoPagoService::class)->isConfigured(),
        ]);
    }

    public function destroy(
        ProfileSubscription $subscription,
        Request $request,
        ProfileSubscriptionCancellationService $cancellationService,
        MercadoPagoService $mercadoPago,
    ): RedirectResponse {
        $this->authorize('cancel', $subscription);

        try {
            $cancellationService->cancel($subscription, $request->user());
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
