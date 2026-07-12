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

    public function connectEnabled(): bool
    {
        return $this->isConfigured() && (bool) config('stripe.connect_enabled', true);
    }

    public function acceptsPaymentsFor(User $barbershop): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if (! $this->connectEnabled()) {
            return true;
        }

        return $barbershop->isStripeConnectReady();
    }

    public function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mobilePaymentConfig(?User $barbershop = null): ?array
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
            // Destination charges stay on the platform account.
            'stripe_account' => null,
            'connect_ready' => $barbershop ? $this->acceptsPaymentsFor($barbershop) : null,
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
        ?User $barbershop = null,
    ): array {
        $barbershop ??= $subscription->creator;
        $customerId = $this->findOrCreateCustomer($subscriber);
        $stripeSubscription = $this->createIncompleteSubscription(
            $subscription,
            $customerId,
            $selection,
            $barbershop,
        );

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
        ?User $barbershop = null,
    ): string {
        $barbershop ??= $subscription->creator;
        $customerId = $this->findOrCreateCustomer($subscriber);

        $session = $this->client()->checkout->sessions->create(
            $this->webCheckoutSessionParams(
                $subscription,
                $customerId,
                $selection,
                $successUrl,
                $cancelUrl,
                $barbershop,
            ),
        );

        if (! is_string($session->url) || $session->url === '') {
            throw new \RuntimeException(__('messages.stripe_no_checkout_url'));
        }

        return $session->url;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array<string, mixed>
     */
    public function webCheckoutSessionParams(
        ServicePlanSubscription $subscription,
        string $customerId,
        array $selection,
        string $successUrl,
        string $cancelUrl,
        ?User $barbershop = null,
    ): array {
        $params = [
            'mode' => 'subscription',
            'customer' => $customerId,
            'locale' => 'pt-BR',
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
            'subscription_data' => $this->subscriptionData($subscription, $barbershop),
            'payment_method_types' => $this->checkoutPaymentMethodTypes(),
        ];

        $paymentMethodOptions = $this->checkoutPaymentMethodOptions($selection);

        if ($paymentMethodOptions !== []) {
            $params['payment_method_options'] = $paymentMethodOptions;
        }

        return $params;
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
        ?User $barbershop = null,
    ): Subscription {
        $params = [
            'customer' => $customerId,
            'items' => [[
                'price_data' => $this->lineItemPriceData($selection),
            ]],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => $this->subscriptionPaymentSettings($selection),
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => $this->subscriptionMetadata($subscription),
        ];

        $destination = $this->destinationChargeParams($barbershop ?? $subscription->creator);

        if ($destination !== []) {
            $params = [...$params, ...$destination];
        }

        $stripeSubscription = $this->client()->subscriptions->create($params);

        $subscription->update([
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => $this->mapSubscriptionStatus($stripeSubscription->status),
        ]);

        return $stripeSubscription;
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionData(ServicePlanSubscription $subscription, ?User $barbershop): array
    {
        return [
            'metadata' => $this->subscriptionMetadata($subscription),
            ...$this->destinationChargeParams($barbershop),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function destinationChargeParams(?User $barbershop): array
    {
        if (! $barbershop || ! $this->connectEnabled() || ! $barbershop->isStripeConnectReady()) {
            return [];
        }

        $params = [
            'transfer_data' => [
                'destination' => (string) $barbershop->stripe_connect_account_id,
            ],
        ];

        $feePercent = (float) config('stripe.application_fee_percent', 10);

        if ($feePercent > 0) {
            $params['application_fee_percent'] = min(100, max(0, $feePercent));
        }

        return $params;
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

    /**
     * @return list<string>
     */
    private function checkoutPaymentMethodTypes(): array
    {
        $types = ['card'];

        if ($this->pixEnabled()) {
            $types[] = 'pix';
        }

        return $types;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array<string, mixed>
     */
    private function checkoutPaymentMethodOptions(array $selection): array
    {
        if (! $this->pixEnabled()) {
            return [];
        }

        return [
            'pix' => [
                'mandate_options' => $this->pixMandateOptions($selection),
            ],
        ];
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array<string, mixed>
     */
    private function subscriptionPaymentSettings(array $selection): array
    {
        $settings = [
            'save_default_payment_method' => 'on_subscription',
            'payment_method_types' => $this->checkoutPaymentMethodTypes(),
        ];

        if ($this->pixEnabled()) {
            $settings['payment_method_options'] = [
                'pix' => [
                    'mandate_options' => $this->pixMandateOptions($selection),
                ],
            ];
        }

        return $settings;
    }

    /**
     * @param  array{reason: string, monthly_total: float}  $selection
     * @return array<string, mixed>
     */
    private function pixMandateOptions(array $selection): array
    {
        $amount = $this->amountInCents((float) $selection['monthly_total']);

        return [
            // Headroom for small plan changes / IOF presentation without re-mandate.
            'amount' => max($amount, (int) round($amount * 1.2)),
            'amount_type' => 'maximum',
            'currency' => strtolower((string) config('stripe.currency', 'brl')),
            'payment_schedule' => 'monthly',
            'reference' => mb_substr((string) $selection['reason'], 0, 35),
        ];
    }

    private function pixEnabled(): bool
    {
        return (bool) config('stripe.pix_enabled', true)
            && strtolower((string) config('stripe.currency', 'brl')) === 'brl';
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
