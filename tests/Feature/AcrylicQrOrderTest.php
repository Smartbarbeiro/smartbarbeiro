<?php

namespace Tests\Feature;

use App\Models\AcrylicQrOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcrylicQrOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validOrderPayload(): array
    {
        return [
            'recipient_name' => 'João Barbeiro',
            'phone' => '(67) 99999-9999',
            'postal_code' => '79002-000',
            'street' => 'Rua Example',
            'number' => '123',
            'complement' => 'Sala 1',
            'neighborhood' => 'Centro',
            'city' => 'Campo Grande',
            'state' => 'MS',
        ];
    }

    public function test_barbershop_can_request_acrylic_qr_order(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->post(route('profile.acrylic-qr-orders.store'), $this->validOrderPayload())
            ->assertRedirect()
            ->assertSessionHas('status', 'acrylic-qr-order-created');

        $this->assertDatabaseHas('acrylic_qr_orders', [
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            'city' => 'Campo Grande',
        ]);
    }

    public function test_barbershop_cannot_create_second_active_order(): void
    {
        $barbershop = User::factory()->create();

        AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$this->validOrderPayload(),
        ]);

        $this->actingAs($barbershop)
            ->from(route('dashboard'))
            ->post(route('profile.acrylic-qr-orders.store'), $this->validOrderPayload())
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('recipient_name');
    }

    public function test_active_order_payload_is_null_when_latest_order_is_shipped(): void
    {
        $barbershop = User::factory()->create();

        AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
            ...$this->validOrderPayload(),
        ]);

        $this->assertNull(
            app(\App\Services\AcrylicQrOrderService::class)->activeOrderPayloadFor($barbershop),
        );
    }

    public function test_barbershop_can_request_new_order_after_previous_is_shipped(): void
    {
        $barbershop = User::factory()->create();

        AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
            ...$this->validOrderPayload(),
        ]);

        $this->actingAs($barbershop)
            ->from(route('dashboard'))
            ->post(route('profile.acrylic-qr-orders.store'), $this->validOrderPayload())
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'acrylic-qr-order-created');

        $this->assertDatabaseHas('acrylic_qr_orders', [
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
        ]);

        $this->assertDatabaseCount('acrylic_qr_orders', 2);
    }

    public function test_admin_can_mark_order_printed_and_shipped(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();

        $order = AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$this->validOrderPayload(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.acrylic-qr-orders.update', $order), [
                'action' => 'printed',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'acrylic-qr-order-updated');

        $order->refresh();
        $this->assertSame(AcrylicQrOrder::STATUS_PRINTED, $order->status);
        $this->assertNotNull($order->printed_at);

        $this->actingAs($admin)
            ->patch(route('admin.acrylic-qr-orders.update', $order), [
                'action' => 'shipped',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'acrylic-qr-order-updated');

        $order->refresh();
        $this->assertSame(AcrylicQrOrder::STATUS_SHIPPED, $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_non_admin_cannot_access_admin_orders_page(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->get(route('admin.acrylic-qr-orders.index'))
            ->assertForbidden();
    }
}
