<?php

namespace Tests\Feature;

use App\Models\AcrylicQrOrder;
use App\Models\User;
use App\Support\BarbershopDisplayName;
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

    public function test_acrylic_order_qr_pdf_includes_recipient_details(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create([
            'username' => 'barbearia_centro',
            'name' => 'Legacy Name',
        ]);

        $order = AcrylicQrOrder::query()->create([
            'user_id' => $barbershop->id,
            'status' => AcrylicQrOrder::STATUS_PENDING,
            ...$this->validOrderPayload(),
        ]);

        $html = view('pdf.barbershop-profile-qr', [
            'barbershopName' => BarbershopDisplayName::from($barbershop->username, $barbershop->name),
            'username' => $barbershop->username,
            'profileUrl' => $barbershop->profileUrl(),
            'qrCodeDataUri' => 'data:image/svg+xml;base64,test',
            'miniQrCodeDataUri' => 'data:image/svg+xml;base64,mini',
            'recipient' => [
                'name' => $order->recipient_name,
                'phone' => $order->phone,
                'address' => $order->formattedAddress(),
            ],
        ])->render();

        $this->assertStringContainsString('Barbearia Centro', $html);
        $this->assertStringNotContainsString('Legacy Name', $html);
        $this->assertStringNotContainsString('@barbearia_centro', $html);
        $this->assertStringContainsString('class="plan-label">Plano Mensal</p>', $html);
        $this->assertStringContainsString('Destinatário', $html);
        $this->assertStringContainsString('João Barbeiro', $html);
        $this->assertStringContainsString('(67) 99999-9999', $html);
        $this->assertStringContainsString('recipient__contact-phone', $html);
        $this->assertStringNotContainsString(
            '<div class="sheet-bottom">'."\n".'                <p class="profile-url">',
            $html,
        );
        $this->assertStringNotContainsString(
            'sheet-bottom'."\n".'                    <div class="content">'."\n".'                        <p class="hint">',
            $html,
        );
        $this->assertStringContainsString(
            'Escaneie o QR code para abrir o perfil público desta barbearia.',
            $html,
        );
        $this->assertStringContainsString('Rua Example, 123', $html);
        $this->assertStringContainsString('Plano Mensal', $html);
        $this->assertStringContainsString('sheet-bottom', $html);
        $this->assertStringContainsString('bgcolor="#ffffff"', $html);
        $this->assertStringContainsString('bgcolor="#000000"', $html);
        $this->assertStringContainsString('mini-qr-cut', $html);
        $this->assertStringContainsString('border: 1px dashed #888888', $html);
        $this->assertStringContainsString('background-color: #ffffff', $html);
        $this->assertSame(6, substr_count($html, 'QR code Plano Mensal'));
        $this->assertSame(6, substr_count($html, 'data:image/svg+xml;base64,mini'));
        $this->assertStringContainsString('height: 320px', $html);
        $this->assertStringContainsString('height: 142px', $html);
        $this->assertStringContainsString('sheet-top-wrap', $html);
        $this->assertStringContainsString('min-height: 178.2mm', $html);
        $this->assertStringContainsString('padding: 28px 32px 0', $html);
        $this->assertStringContainsString('valign="middle"', $html);
        $this->assertStringContainsString('align="center"', $html);
        $this->assertStringContainsString('background-color: #000000', $html);
        $this->assertStringContainsString('color: #ffffff', $html);
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
