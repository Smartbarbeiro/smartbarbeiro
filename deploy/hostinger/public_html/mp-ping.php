<?php

/**
 * Minimal Mercado Pago connectivity check.
 * URL: https://www.smartbarbeiro.com.br/mp-ping.php
 */

declare(strict_types=1);

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo "ok\n";
