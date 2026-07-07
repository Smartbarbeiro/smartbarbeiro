<?php

namespace App\Http\Controllers;

use App\Models\ProfileSubscription;
use App\Models\ServicePlanSubscription;
use App\Models\User;
use App\Services\AdminPlatformSubscriptionOverviewService;
use App\Services\BarbershopExpectedMonthlyRevenueService;
use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionCancellationService;
use App\Services\ServicePlanSubscriptionPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use MercadoPago\Exceptions\MPApiException;

class ProfileSubscriptionController extends Controller
{
    public function index(
        Request $request,
        ServicePlanSubscriptionPaymentService $paymentService,
    ): Response {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminPlatformSubscriptionsIndex(
                app(AdminPlatformSubscriptionOverviewService::class),
            );
        }

        if ($user->isBarbershop()) {
            return $this->barbershopClientsIndex($user, $paymentService);
        }

        $profileSubscriptions = $user
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

        $servicePlanSubscriptions = $user
            ->servicePlanSubscriptions()
            ->with('creator:id,name,username')
            ->latest()
            ->get()
            ->map(function (ServicePlanSubscription $subscription) use ($paymentService) {
                return [
                    ...$subscription->toSummaryArray(),
                    'creator' => [
                        'name' => $subscription->creator->name,
                        'username' => $subscription->creator->username,
                        'profile_url' => $subscription->creator->profileUrl(),
                    ],
                    'payment_history' => $paymentService->paymentHistoryPayload($subscription),
                ];
            });

        $subscriptions = $profileSubscriptions
            ->concat($servicePlanSubscriptions)
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'isBarbershopClientsView' => false,
            'isAdminPlatformSubscriptionsView' => false,
            'platformPayingBarbershops' => [],
            'platformRevenueSummary' => null,
            'expectedMonthlyRevenue' => null,
            'mercadopagoConfigured' => app(MercadoPagoService::class)->isConfigured(),
        ]);
    }

    private function adminPlatformSubscriptionsIndex(
        AdminPlatformSubscriptionOverviewService $overviewService,
    ): Response {
        $overview = $overviewService->payload();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => [],
            'isBarbershopClientsView' => false,
            'isAdminPlatformSubscriptionsView' => true,
            'platformPayingBarbershops' => $overview['barbershops'],
            'platformRevenueSummary' => $overview['summary'],
            'expectedMonthlyRevenue' => null,
            'mercadopagoConfigured' => app(MercadoPagoService::class)->isConfigured(),
        ]);
    }

    private function barbershopClientsIndex(
        User $barbershop,
        ServicePlanSubscriptionPaymentService $paymentService,
    ): Response {
        $subscriptionPlan = $barbershop->subscriptionPlan;
        $profileMonthlyAmount = $subscriptionPlan && $subscriptionPlan->is_enabled
            ? (float) $subscriptionPlan->monthly_amount
            : 0.0;
        $formattedProfilePrice = $subscriptionPlan?->is_enabled
            ? $subscriptionPlan->formattedPrice()
            : 'R$ 0,00';

        $profileSubscriptions = $barbershop->subscribers()
            ->with('subscriber:id,name,email,username')
            ->latest()
            ->get()
            ->map(fn (ProfileSubscription $subscription) => [
                ...$subscription->toSummaryArray(),
                'subscriber' => [
                    'name' => $subscription->subscriber->name,
                    'email' => $subscription->subscriber->email,
                    'username' => $subscription->subscriber->username,
                ],
                'monthly_total' => $profileMonthlyAmount,
                'formatted_total' => $formattedProfilePrice,
            ]);

        $servicePlanSubscriptions = $barbershop->servicePlanSubscribers()
            ->with('subscriber:id,name,email,username')
            ->latest()
            ->get()
            ->map(function (ServicePlanSubscription $subscription) use ($paymentService) {
                return [
                    ...$subscription->toSummaryArray(),
                    'subscriber' => [
                        'name' => $subscription->subscriber->name,
                        'email' => $subscription->subscriber->email,
                        'username' => $subscription->subscriber->username,
                    ],
                    'payment_history' => $paymentService->paymentHistoryPayload($subscription),
                ];
            });

        $subscriptions = $profileSubscriptions
            ->concat($servicePlanSubscriptions)
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'isBarbershopClientsView' => true,
            'isAdminPlatformSubscriptionsView' => false,
            'platformPayingBarbershops' => [],
            'platformRevenueSummary' => null,
            'expectedMonthlyRevenue' => app(BarbershopExpectedMonthlyRevenueService::class)
                ->payloadFor($barbershop),
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
