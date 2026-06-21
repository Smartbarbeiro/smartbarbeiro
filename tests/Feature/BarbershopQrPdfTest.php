<?php

namespace Tests\Feature;

use App\Models\AcrylicQrOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarbershopQrPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF is not installed. Run composer install.');
        }
    }

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

    public function test_admin_can_download_qr_pdf_from_acrylic_order(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();

        $order = AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$this->validOrderPayload(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.acrylic-qr-orders.pdf', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_download_qr_pdf_for_barbershop_user(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.qrcode.pdf', $barbershop))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_non_admin_cannot_download_qr_pdf(): void
    {
        $barbershop = User::factory()->create();
        $otherBarbershop = User::factory()->create();

        $order = AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$this->validOrderPayload(),
        ]);

        $this->actingAs($otherBarbershop)
            ->get(route('admin.acrylic-qr-orders.pdf', $order))
            ->assertForbidden();
    }
}
