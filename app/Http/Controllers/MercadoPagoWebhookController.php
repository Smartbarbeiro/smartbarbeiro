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
        $dataId = $this->resourceId($request);
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $type = $request->input('type')
            ?? $request->input('topic')
            ?? $request->query('topic')
            ?? $request->query('type');

        $signatureValid = ! $mercadoPago->webhookSignatureConfigured()
            || (
                filled($xSignature)
                && $mercadoPago->verifyWebhookSignature($xSignature, $xRequestId, $dataId)
            );

        // Always acknowledge with 204 so Mercado Pago panel / retries succeed.
        // Only process events when the signature is valid (or no secret is configured).
        if (! $signatureValid) {
            Log::warning('Mercado Pago webhook acknowledged without valid signature', [
                'has_x_signature' => filled($xSignature),
                'has_x_request_id' => filled($xRequestId),
                'data_id' => $dataId,
                'type' => $type,
                'method' => $request->method(),
            ]);

            return response()->noContent();
        }

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

    /**
     * Mercado Pago sends the resource id as the query key "data.id".
     * Laravel's query('data.id') treats dots as nested array access, so use the
     * ParameterBag key directly for the query string form.
     */
    private function resourceId(Request $request): ?string
    {
        $candidates = [
            $request->query->get('data.id'),
            $request->query->get('data_id'),
            $request->query->get('id'),
            $request->input('data.id'),
            $request->input('data_id'),
            $request->input('id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && filled((string) $candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}
