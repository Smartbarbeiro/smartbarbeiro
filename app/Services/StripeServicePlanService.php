<?php

namespace App\Services;

use App\Models\ServicePlanSubscription;
use App\Models\User;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Util\ApiVersion;

class StripeServicePlanService
{
    private ?StripeClient $client = null;

    public function client(): StripeClient
    {
        if ($this->client === null) {
            $this->ensureConfigured();
            $this->client = new StripeClient(config('stripe.secret'));
        }

        return $this->client;
    }

    public function isConfigured(): bool
    {
        return filled(config('stripe.secret')) && filled(config('stripe.key'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mobilePaymentConfig(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return [
            'publishable_key' => (string) config('stripe.key'),
            'currency' => strtolower((string) config('stripe.currency', 'brl')),
            'merchant_display_name' => (string) config('stripe.merchant_display_name'),
            'apple_pay_merchant_id' => config('stripe.apple_pay_merchant_id'),
            'google_pay_test_env' => (bool) config('stripe.google_pay_test_env', true),
            'stripe_account' => null,
        ];
    }

    public function findOrCreateCustomer(User $subscriber): string
    {
        if (filled($subscriber->stripe_customer_id)) {
            return (string) $subscriber->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'email' => $subscriber->email,
            'name' => $subscriber->name,
            'metadata' => [
                'user_id' => (string) $subscriber->id,
            ],
        ]);

        $subscriber->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    public function createEphemeralKey(string $customerId): string
    {
        $key = $this->client()->ephemeralKeys->create(
            ['customer' => $customerId],
            ['stripe_version' => $this->apiVersion()],
        );

        return (string) $key->secret;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array{
     *     customer_id: string,
     *     customer_ephemeral_key_secret: string,
     *     payment_intent_client_secret: string,
     *     subscription_id: string
     * }
     */
    public function prepareMobileSubscription(
        ServicePlanSubscription $subscription,
        User $subscriber,
        array $selection,
    ): array {
        $customerId = $this->findOrCreateCustomer($subscriber);
        $stripeSubscription = $this->createIncompleteSubscription($subscription, $customerId, $selection);

        $paymentIntent = $stripeSubscription->latest_invoice->payment_intent ?? null;
        $clientSecret = is_object($paymentIntent) ? $paymentIntent->client_secret : null;

        if (! is_string($clientSecret) || $clientSecret === '') {
            throw new \RuntimeException(__('messages.stripe_no_client_secret'));
        }

        return [
            'customer_id' => $customerId,
            'customer_ephemeral_key_secret' => $this->createEphemeralKey($customerId),
            'payment_intent_client_secret' => $clientSecret,
            'subscription_id' => $stripeSubscription->id,
        ];
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     */
    public function createWebCheckoutSession(
        ServicePlanSubscription $subscription,
        User $subscriber,
        array $selection,
        string $successUrl,
        string $cancelUrl,
    ): string {
        $customerId = $this->findOrCreateCustomer($subscriber);

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price_data' => $this->lineItemPriceData($selection),
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'external_reference' => $subscription->external_reference,
                'service_plan_subscription_id' => (string) $subscription->id,
            ],
            'subscription_data' => [
                'metadata' => $this->subscriptionMetadata($subscription),
            ],
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new \RuntimeException(__('messages.stripe_no_checkout_url'));
        }

        return $session->url;
    }

    public function retrieveSubscription(string $stripeSubscriptionId): Subscription
    {
        return $this->client()->subscriptions->retrieve($stripeSubscriptionId, [
            'expand' => ['latest_invoice'],
        ]);
    }

    public function cancelSubscription(string $stripeSubscriptionId): Subscription
    {
        return $this->client()->subscriptions->cancel($stripeSubscriptionId);
    }

    public function mapSubscriptionStatus(?string $status): string
    {
        return match ($status) {
            'active', 'trialing' => ServicePlanSubscription::STATUS_AUTHORIZED,
            'past_due', 'unpaid' => ServicePlanSubscription::STATUS_PAUSED,
            'canceled', 'incomplete_expired' => ServicePlanSubscription::STATUS_CANCELLED,
            default => ServicePlanSubscription::STATUS_PENDING,
        };
    }

    public function apiExceptionMessage(ApiErrorException $exception): string
    {
        $message = $exception->getMessage();

        if ($exception->getJsonBody() && isset($exception->getJsonBody()['error']['message'])) {
            $message = (string) $exception->getJsonBody()['error']['message'];
        }

        return $message;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     */
    private function createIncompleteSubscription(
        ServicePlanSubscription $subscription,
        string $customerId,
        array $selection,
    ): Subscription {
        $stripeSubscription = $this->client()->subscriptions->create([
            'customer' => $customerId,
            'items' => [[
                'price_data' => $this->lineItemPriceData($selection),
            ]],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription',
            ],
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => $this->subscriptionMetadata($subscription),
        ]);

        $subscription->update([
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => $this->mapSubscriptionStatus($stripeSubscription->status),
        ]);

        return $stripeSubscription;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array<string, mixed>
     */
    private function lineItemPriceData(array $selection): array
    {
        return [
            'currency' => strtolower((string) config('stripe.currency', 'brl')),
            'product_data' => [
                'name' => mb_substr($selection['reason'], 0, 200),
            ],
            'unit_amount' => $this->amountInCents((float) $selection['monthly_total']),
            'recurring' => ['interval' => 'month'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function subscriptionMetadata(ServicePlanSubscription $subscription): array
    {
        return [
            'external_reference' => $subscription->external_reference,
            'service_plan_subscription_id' => (string) $subscription->id,
            'creator_user_id' => (string) $subscription->creator_user_id,
            'subscriber_user_id' => (string) $subscription->subscriber_user_id,
        ];
    }

    private function amountInCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function apiVersion(): string
    {
        $configured = config('stripe.api_version');

        return filled($configured) ? (string) $configured : ApiVersion::CURRENT;
    }
}
