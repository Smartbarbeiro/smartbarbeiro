<?php

namespace App\Http\Controllers;

use App\Services\ProfileSubscriptionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, ProfileSubscriptionSyncService $syncService): Response
    {
        $type = $request->input('type') ?? $request->input('topic');
        $dataId = $request->input('data.id') ?? $request->input('data_id') ?? $request->input('id');

        if (is_string($type) && str_contains($type, 'preapproval') && $dataId) {
            try {
                $syncService->syncByMercadoPagoId((string) $dataId);
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
