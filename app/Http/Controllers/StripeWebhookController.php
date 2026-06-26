<?php

namespace App\Http\Controllers;

use App\Services\ServicePlanSubscriptionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        ServicePlanSubscriptionSyncService $servicePlanSyncService,
    ): Response {
        $secret = config('stripe.webhook_secret');

        if (! filled($secret)) {
            return response()->noContent();
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature) || $signature === '') {
            return response()->noContent(400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe webhook signature verification failed', [
                'message' => $exception->getMessage(),
            ]);

            return response()->noContent(400);
        } catch (\UnexpectedValueException $exception) {
            return response()->noContent(400);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object, $servicePlanSyncService),
                'customer.subscription.updated',
                'customer.subscription.deleted' => $servicePlanSyncService->syncFromStripeSubscription($event->data->object),
                default => null,
            };
        } catch (\Throwable $exception) {
            Log::warning('Stripe webhook sync failed', [
                'type' => $event->type,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->noContent();
    }

    private function handleCheckoutSessionCompleted(
        object $session,
        ServicePlanSubscriptionSyncService $servicePlanSyncService,
    ): void {
        if (! filled($session->id ?? null)) {
            return;
        }

        $servicePlanSyncService->syncByCheckoutSessionId((string) $session->id);
    }
}
