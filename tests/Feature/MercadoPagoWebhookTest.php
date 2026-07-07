<?php

namespace Tests\Feature;

use App\Services\BarbershopPlatformSubscriptionSyncService;
use App\Services\MercadoPagoService;
use App\Services\ProfileSubscriptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_acknowledges_unsigned_request_without_processing(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) {
            $mock->shouldNotReceive('syncByMercadoPagoId');
        });

        $this->mock(BarbershopPlatformSubscriptionSyncService::class, function ($mock) {
            $mock->shouldNotReceive('syncByMercadoPagoId');
        });

        // Mercado Pago panel "simulate" often omits signature headers.
        $this->postJson('/webhooks/mercadopago', [
            'type' => 'subscription_preapproval',
            'data' => ['id' => 'mp-1'],
        ])->assertNoContent();
    }

    public function test_webhook_acknowledges_invalid_signature_without_processing(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) {
            $mock->shouldNotReceive('syncByMercadoPagoId');
        });

        $this->mock(BarbershopPlatformSubscriptionSyncService::class, function ($mock) {
            $mock->shouldNotReceive('syncByMercadoPagoId');
        });

        $this->withHeaders([
            'x-signature' => 'ts=1,v1=bad',
            'x-request-id' => 'req-1',
        ])->postJson('/webhooks/mercadopago?data.id=mp-1', [
            'type' => 'subscription_preapproval',
            'data' => ['id' => 'mp-1'],
        ])->assertNoContent();
    }

    public function test_webhook_accepts_signed_notification_with_query_data_id(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $dataId = 'ABC123';
        $requestId = 'req-panel-1';
        $timestamp = '1704908010';
        $manifest = 'id:abc123;request-id:req-panel-1;ts:1704908010;';
        $signature = hash_hmac('sha256', $manifest, 'test-secret');

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) use ($dataId) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with($dataId);
        });

        $this->mock(BarbershopPlatformSubscriptionSyncService::class, function ($mock) use ($dataId) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with($dataId);
        });

        $this->withHeaders([
            'x-signature' => "ts={$timestamp},v1={$signature}",
            'x-request-id' => $requestId,
        ])->postJson('/webhooks/mercadopago?data.id='.$dataId.'&type=subscription_preapproval', [
            'type' => 'subscription_preapproval',
            'data' => ['id' => $dataId],
        ])->assertNoContent();
    }

    public function test_webhook_accepts_signed_notification_with_body_data_id(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $dataId = 'xyz789';
        $requestId = 'req-body-1';
        $timestamp = '1704908010';
        $manifest = 'id:xyz789;request-id:req-body-1;ts:1704908010;';
        $signature = hash_hmac('sha256', $manifest, 'test-secret');

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) use ($dataId) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with($dataId);
        });

        $this->mock(BarbershopPlatformSubscriptionSyncService::class, function ($mock) use ($dataId) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with($dataId);
        });

        $this->withHeaders([
            'x-signature' => "ts={$timestamp},v1={$signature}",
            'x-request-id' => $requestId,
        ])->postJson('/webhooks/mercadopago', [
            'type' => 'subscription_preapproval',
            'data' => ['id' => $dataId],
        ])->assertNoContent();
    }

    public function test_webhook_returns_no_content_for_non_preapproval_topics(): void
    {
        config(['mercadopago.webhook_secret' => 'test-secret']);

        $dataId = 'payment-1';
        $requestId = 'req-payment-1';
        $timestamp = '1704908010';
        $manifest = 'id:payment-1;request-id:req-payment-1;ts:1704908010;';
        $signature = hash_hmac('sha256', $manifest, 'test-secret');

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) {
            $mock->shouldNotReceive('syncByMercadoPagoId');
        });

        $this->withHeaders([
            'x-signature' => "ts={$timestamp},v1={$signature}",
            'x-request-id' => $requestId,
        ])->postJson('/webhooks/mercadopago?data.id='.$dataId.'&type=payment', [
            'type' => 'payment',
            'data' => ['id' => $dataId],
        ])->assertNoContent();
    }

    public function test_resource_id_is_read_from_query_data_id_key(): void
    {
        config(['mercadopago.webhook_secret' => null]);

        $service = app(MercadoPagoService::class);
        $this->assertFalse($service->webhookSignatureConfigured());

        $this->mock(ProfileSubscriptionSyncService::class, function ($mock) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with('QUERYID');
        });

        $this->mock(BarbershopPlatformSubscriptionSyncService::class, function ($mock) {
            $mock->shouldReceive('syncByMercadoPagoId')->once()->with('QUERYID');
        });

        $this->post('/webhooks/mercadopago?data.id=QUERYID&type=subscription_preapproval')
            ->assertNoContent();
    }
}
