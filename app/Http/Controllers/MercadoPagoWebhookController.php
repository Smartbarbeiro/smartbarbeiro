<?php

namespace App\Http\Controllers;

use App\Services\BarbershopPlatformSubscriptionSyncService;
use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $mercadoPago,
        ProfileSubscriptionSyncService $profileSyncService,
        BarbershopPlatformSubscriptionSyncService $platformSyncService,
    ): Response {
        $dataId = $request->query('data.id')
            ?? $request->input('data.id')
            ?? $request->input('data_id')
            ?? $request->input('id');

        if ($mercadoPago->webhookSignatureConfigured()) {
            if (! $mercadoPago->verifyWebhookSignature(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                is_string($dataId) ? $dataId : null,
            )) {
                Log::warning('Mercado Pago webhook signature verification failed');

                return response()->noContent(401);
            }
        }

        $type = $request->input('type') ?? $request->input('topic');

        if (is_string($type) && str_contains($type, 'preapproval') && $dataId) {
            try {
                $profileSyncService->syncByMercadoPagoId((string) $dataId);
                $platformSyncService->syncByMercadoPagoId((string) $dataId);
            } catch (\Throwable $exception) {
                Log::warning('Mercado Pago webhook sync failed', [
                    'type' => $type,
                    'id' => $dataId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return response()->noContent();
    }
}
