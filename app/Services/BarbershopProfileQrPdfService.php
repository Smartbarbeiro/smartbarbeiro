<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use InvalidArgumentException;

class BarbershopProfileQrPdfService
{
    public function download(User $barbershop, ?string $filename = null): Response
    {
        abort_unless($barbershop->isBarbershop(), 403);

        $profileUrl = $barbershop->profileUrl();

        if ($profileUrl === null) {
            throw new InvalidArgumentException('Esta barbearia não possui perfil público.');
        }

        $qrCodeDataUri = $this->buildQrCodeDataUri($profileUrl);
        $filename ??= sprintf(
            'qrcode-%s.pdf',
            $barbershop->username ?? $barbershop->id,
        );

        return Pdf::loadView('pdf.barbershop-profile-qr', [
            'barbershopName' => $barbershop->name,
            'username' => $barbershop->username,
            'profileUrl' => $profileUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function buildQrCodeDataUri(string $profileUrl): string
    {
        $builder = new Builder(
            writer: new SvgWriter,
            data: $profileUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 420,
            margin: 12,
        );

        return $builder->build()->getDataUri();
    }
}
