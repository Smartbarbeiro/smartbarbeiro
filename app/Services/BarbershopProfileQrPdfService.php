<?php

namespace App\Services;

use App\Models\AcrylicQrOrder;
use App\Models\User;
use App\Support\BarbershopDisplayName;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use InvalidArgumentException;

class BarbershopProfileQrPdfService
{
    public function download(
        User $barbershop,
        ?string $filename = null,
        ?AcrylicQrOrder $acrylicQrOrder = null,
    ): Response {
        abort_unless($barbershop->isBarbershop(), 403);

        $profileUrl = $barbershop->profileUrl();

        if ($profileUrl === null) {
            throw new InvalidArgumentException('Esta barbearia não possui perfil público.');
        }

        $qrCodeDataUri = $this->buildQrCodeDataUri($profileUrl, size: 420, margin: 12);
        $miniQrCodeDataUri = $this->buildQrCodeDataUri($profileUrl, size: 320, margin: 8);
        $filename ??= sprintf(
            'qrcode-%s.pdf',
            $barbershop->username ?? $barbershop->id,
        );

        return Pdf::loadView('pdf.barbershop-profile-qr', [
            'barbershopName' => BarbershopDisplayName::from($barbershop->username, $barbershop->name),
            'username' => $barbershop->username,
            'profileUrl' => $profileUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
            'miniQrCodeDataUri' => $miniQrCodeDataUri,
            'recipient' => $this->recipientPayload($acrylicQrOrder),
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * @return array{name: string, phone: string, address: string}|null
     */
    private function recipientPayload(?AcrylicQrOrder $acrylicQrOrder): ?array
    {
        if ($acrylicQrOrder === null) {
            return null;
        }

        return [
            'name' => $acrylicQrOrder->recipient_name,
            'phone' => $acrylicQrOrder->phone,
            'address' => $acrylicQrOrder->formattedAddress(),
        ];
    }

    private function buildQrCodeDataUri(string $profileUrl, int $size = 420, int $margin = 12): string
    {
        $builder = new Builder(
            writer: new SvgWriter,
            data: $profileUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
        );

        return $builder->build()->getDataUri();
    }
}
