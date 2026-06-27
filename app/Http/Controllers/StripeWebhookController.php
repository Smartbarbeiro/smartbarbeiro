<?php

namespace App\Http\Controllers;

use App\Services\ServicePlanSubscriptionPaymentService;
use App\Services\ServicePlanSubscriptionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Invoice;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        ServicePlanSubscriptionSyncService $servicePlanSyncService,
        ServicePlanSubscriptionPaymentService $paymentService,
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
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted(
                    $event->data->object,
                    $servicePlanSyncService,
                    $paymentService,
                ),
                'customer.subscription.updated',
                'customer.subscription.deleted' => $this->handleSubscriptionEvent($event->data->object, $servicePlanSyncService, $paymentService),
                'invoice.paid',
                'invoice.payment_failed',
                'invoice.finalized' => $this->handleInvoiceEvent($event->data->object, $paymentService),
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
        ServicePlanSubscriptionPaymentService $paymentService,
    ): void {
        if (! filled($session->id ?? null)) {
            return;
        }

        $subscription = $servicePlanSyncService->syncByCheckoutSessionId((string) $session->id);

        if ($subscription) {
            $paymentService->syncSchedule($subscription);
            $paymentService->syncInvoicesFromStripe($subscription);
        }
    }

    private function handleSubscriptionEvent(
        object $stripeSubscription,
        ServicePlanSubscriptionSyncService $servicePlanSyncService,
        ServicePlanSubscriptionPaymentService $paymentService,
    ): void {
        $subscription = $servicePlanSyncService->syncFromStripeSubscription($stripeSubscription);

        if ($subscription) {
            $paymentService->syncSchedule($subscription);
        }
    }

    private function handleInvoiceEvent(
        object $invoice,
        ServicePlanSubscriptionPaymentService $paymentService,
    ): void {
        if (! $invoice instanceof Invoice) {
            return;
        }

        $stripeSubscriptionId = is_string($invoice->subscription ?? null)
            ? $invoice->subscription
            : ($invoice->subscription->id ?? null);

        $subscription = null;

        if (filled($stripeSubscriptionId)) {
            $subscription = \App\Models\ServicePlanSubscription::query()
                ->where('stripe_subscription_id', $stripeSubscriptionId)
                ->first();
        }

        if (! $subscription && filled($invoice->metadata['service_plan_subscription_id'] ?? null)) {
            $subscription = \App\Models\ServicePlanSubscription::query()->find(
                $invoice->metadata['service_plan_subscription_id'],
            );
        }

        if (! $subscription) {
            return;
        }

        $paymentService->syncFromStripeInvoice($subscription, $invoice);
    }
}
