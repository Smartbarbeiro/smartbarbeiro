<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Services\ServicePlanCheckoutService;
use App\Services\StripeServicePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class ServicePlanController extends Controller
{
    public function prepareCheckout(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        StripeServicePlanService $stripe,
    ): JsonResponse {
        $barbershop = $this->findBarbershop($username);

        $validated = $request->validate([
            'package_type' => ['required', 'in:cut,cut_beard'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer'],
        ]);

        if (! $stripe->isConfigured() || ! $stripe->acceptsPaymentsFor($barbershop)) {
            try {
                $checkoutService->savePendingSelection(
                    $barbershop,
                    $request->user(),
                    $validated['package_type'],
                    $validated['addon_ids'] ?? [],
                );
            } catch (\InvalidArgumentException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return response()->json([
                'message' => $stripe->isConfigured()
                    ? __('messages.stripe_connect_not_ready')
                    : __('messages.status.service-plan-signup-pending'),
            ], 422);
        }

        try {
            $prepared = $checkoutService->prepareMobileCheckout(
                $barbershop,
                $request->user(),
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (ApiErrorException $exception) {
            return response()->json([
                'message' => $stripe->apiExceptionMessage($exception),
            ], 422);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($prepared);
    }

    public function confirmCheckout(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        StripeServicePlanService $stripe,
    ): JsonResponse {
        $barbershop = $this->findBarbershop($username);

        $validated = $request->validate([
            'subscription_id' => ['required', 'string', 'max:255'],
        ]);

        if (! $stripe->isConfigured()) {
            return response()->json([
                'message' => __('messages.payments_not_configured'),
            ], 422);
        }

        try {
            $result = $checkoutService->confirmMobileCheckout(
                $barbershop,
                $request->user(),
                $validated['subscription_id'],
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (ApiErrorException $exception) {
            return response()->json([
                'message' => $stripe->apiExceptionMessage($exception),
            ], 422);
        }

        return response()->json([
            'checkout_url' => null,
            'status' => $result['status'],
            'message' => __('messages.status.service-plan-checkout-authorized'),
        ]);
    }

    public function membership(string $username, Request $request): JsonResponse
    {
        $barbershop = $this->findBarbershop($username);

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => __('messages.barbershop-signup-success'),
        ]);
    }

    private function findBarbershop(string $username): User
    {
        return User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();
    }
}
