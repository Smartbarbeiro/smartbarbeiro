<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\ServicePlanCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\MPApiException;

class ServicePlanController extends Controller
{
    public function checkout(
        string $username,
        Request $request,
        ServicePlanCheckoutService $checkoutService,
        MercadoPagoService $mercadoPago,
    ): JsonResponse {
        $barbershop = $this->findBarbershop($username);

        $validated = $request->validate([
            'package_type' => ['required', 'in:cut,cut_beard'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer'],
        ]);

        if (! $mercadoPago->isConfigured()) {
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
                'checkout_url' => null,
                'status' => 'pending',
                'message' => __('messages.service-plan-signup-pending'),
            ]);
        }

        try {
            $checkout = $checkoutService->startCheckout(
                $barbershop,
                $request->user(),
                $validated['package_type'],
                $validated['addon_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (MPApiException $exception) {
            return response()->json([
                'message' => $mercadoPago->apiExceptionMessage($exception),
            ], 422);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'checkout_url' => $checkout['checkout_url'],
            'status' => 'checkout',
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
