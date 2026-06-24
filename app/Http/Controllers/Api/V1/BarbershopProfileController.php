<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BarbershopServicePlanService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;

class BarbershopProfileController extends Controller
{
    public function show(string $username, BarbershopServicePlanService $servicePlanService): JsonResponse
    {
        $barbershop = User::query()
            ->where('username', $username)
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->firstOrFail();

        if (! $barbershop->hasPublicProfile()) {
            abort(404);
        }

        return response()->json([
            'profile' => [
                'name' => $barbershop->name,
                'username' => $barbershop->username,
                'profile_url' => $barbershop->profileUrl(),
                'profile_photo_url' => $barbershop->profile_photo_url,
                'member_since' => $barbershop->created_at->translatedFormat('F Y'),
            ],
            'service_plans' => $servicePlanService->publicPlansPayload($barbershop),
            'mercadopago_configured' => app(MercadoPagoService::class)->isConfigured(),
        ]);
    }
}
