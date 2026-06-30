<?php

/**
 * Generate Hostinger deploy guide PDF.
 *
 * Usage: php scripts/generate-hostinger-deploy-guide-pdf.php
 */

declare(strict_types=1);

use Barryvdh\DomPDF\Facade\Pdf;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$outputDir = __DIR__.'/../deploy/hostinger/docs';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$outputPath = $outputDir.'/atualizar-hostinger.pdf';

Pdf::loadView('pdf.hostinger-deploy-guide', [
    'generatedAt' => now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
])
    ->setPaper('a4', 'portrait')
    ->save($outputPath);

echo "PDF saved: {$outputPath}\n";
