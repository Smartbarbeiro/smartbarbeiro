<?php

namespace Tests\Unit;

use App\Services\MercadoPagoService;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Net\MPResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoServiceTest extends TestCase
{
    #[Test]
    public function test_api_exception_message_includes_cause_descriptions(): void
    {
        $service = app(MercadoPagoService::class);

        $response = new MPResponse(400, [
            'message' => 'Invalid value for payer_email.',
            'cause' => [
                ['code' => '101', 'description' => 'Both payer and collector must be real or test users'],
            ],
        ]);

        $exception = new MPApiException('Api error', $response);

        $message = $service->apiExceptionMessage($exception);

        $this->assertStringContainsString('Invalid value for payer_email.', $message);
        $this->assertStringContainsString(__('messages.mercadopago_sandbox_users_required'), $message);
    }

    #[Test]
    public function test_checkout_url_prefers_sandbox_init_point(): void
    {
        $service = app(MercadoPagoService::class);
        $preapproval = new \MercadoPago\Resources\PreApproval;
        $preapproval->init_point = 'https://mercadopago.test/live';
        $preapproval->sandbox_init_point = 'https://mercadopago.test/sandbox';

        $this->assertSame('https://mercadopago.test/sandbox', $service->checkoutUrl($preapproval));
    }

    #[Test]
    public function test_api_exception_message_maps_sandbox_user_requirement(): void
    {
        $service = app(MercadoPagoService::class);

        $response = new MPResponse(400, [
            'message' => 'Both payer and collector must be real or test users',
        ]);

        $exception = new MPApiException('Api error', $response);

        $this->assertSame(
            __('messages.mercadopago_sandbox_users_required'),
            $service->apiExceptionMessage($exception),
        );
    }

    #[Test]
    public function test_assert_sandbox_checkout_users_rejects_real_email_for_test_seller(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['test_user'],
            ]),
        ]);

        $service = app(MercadoPagoService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.mercadopago_test_buyer_required'));

        $service->assertSandboxCheckoutUsers('real@gmail.com');
    }

    #[Test]
    public function test_assert_sandbox_checkout_users_allows_real_email_for_real_seller(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['normal'],
            ]),
        ]);

        $service = app(MercadoPagoService::class);

        $service->assertSandboxCheckoutUsers('oauth-user@gmail.com');

        $this->assertTrue(true);
    }

    #[Test]
    public function test_assert_sandbox_checkout_users_rejects_test_email_for_real_seller(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['normal'],
            ]),
        ]);

        $service = app(MercadoPagoService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.mercadopago_real_buyer_required'));

        $service->assertSandboxCheckoutUsers('test_user_123@testuser.com');
    }

    #[Test]
    public function test_assert_sandbox_checkout_users_allows_test_email_for_test_seller(): void
    {
        config(['mercadopago.access_token' => 'TEST-fake-token']);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mercadopago.com/users/me' => \Illuminate\Support\Facades\Http::response([
                'tags' => ['test_user'],
            ]),
        ]);

        $service = app(MercadoPagoService::class);

        $service->assertSandboxCheckoutUsers('test_user_123@testuser.com');

        $this->assertTrue(true);
    }

    #[Test]
    public function test_verify_webhook_signature_accepts_valid_signature(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $service = app(MercadoPagoService::class);
        $dataId = 'ABC123';
        $requestId = 'req-1';
        $timestamp = '1704908010';
        $manifest = 'id:abc123;request-id:req-1;ts:1704908010;';
        $signature = hash_hmac('sha256', $manifest, 'test-secret');
        $xSignature = "ts={$timestamp},v1={$signature}";

        $this->assertTrue($service->verifyWebhookSignature($xSignature, $requestId, $dataId));
    }

    #[Test]
    public function test_verify_webhook_signature_rejects_invalid_signature(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $service = app(MercadoPagoService::class);

        $this->assertFalse($service->verifyWebhookSignature('ts=1,v1=bad', 'req-1', 'abc'));
    }
}
