<?php

/**
 * Patches checkout controllers to use Inertia::location() for Mercado Pago / Stripe.
 * Visit once: https://www.tesora.com.br/patch-inertia-checkout-redirect-tesora.php
 * Then: clear-cache-tesora.php
 * DELETE this file after success.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$laravelRoot = dirname(__DIR__).'/laravel';
if (! is_file($laravelRoot.'/vendor/autoload.php') && is_file(dirname(__DIR__).'/laravel/laravel/vendor/autoload.php')) {
    $laravelRoot = dirname(__DIR__).'/laravel/laravel';
}

$controllersDir = $laravelRoot.'/app/Http/Controllers';

$files = [
    'BarbershopPlatformSubscribeController.php' => [
        'redirect_away' => "return redirect()->away(\$result['checkout_url']);",
        'inertia_location' => "return Inertia::location(\$result['checkout_url']);",
        'http_response_use' => "use Symfony\\Component\\HttpFoundation\\Response as HttpResponse;",
        'store_return' => '): RedirectResponse {',
        'store_return_fixed' => '): RedirectResponse|HttpResponse {',
    ],
    'ProfileSubscribeController.php' => [
        'redirect_away' => 'return redirect()->away($mercadoPago->checkoutUrl($preapproval));',
        'inertia_location' => 'return Inertia::location($mercadoPago->checkoutUrl($preapproval));',
        'http_response_use' => "use Symfony\\Component\\HttpFoundation\\Response as HttpResponse;",
        'store_return' => '): RedirectResponse {',
        'store_return_fixed' => '): RedirectResponse|HttpResponse {',
    ],
    'ServicePlanSubscribeController.php' => [
        'redirect_away' => "return redirect()->away(\$checkout['checkout_url']);",
        'inertia_location' => "return Inertia::location(\$checkout['checkout_url']);",
        'http_response_use' => "use Symfony\\Component\\HttpFoundation\\Response as HttpResponse;",
        'store_return' => '): RedirectResponse {',
        'store_return_fixed' => '): RedirectResponse|HttpResponse {',
    ],
];

foreach ($files as $name => $replacements) {
    $path = $controllersDir.'/'.$name;

    if (! is_file($path)) {
        echo "SKIP missing: $path\n";
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        echo "FAIL read: $path\n";
        continue;
    }

    $original = $content;

    if (str_contains($content, $replacements['inertia_location'])) {
        echo "OK already patched: $name\n";
        continue;
    }

    if (! str_contains($content, $replacements['redirect_away'])) {
        echo "WARN pattern not found in $name — upload controller manually\n";
        continue;
    }

    $content = str_replace($replacements['redirect_away'], $replacements['inertia_location'], $content);

    if (! str_contains($content, $replacements['http_response_use'])) {
        $content = str_replace(
            "use MercadoPago\\Exceptions\\MPApiException;\n",
            "use MercadoPago\\Exceptions\\MPApiException;\n".$replacements['http_response_use']."\n",
            $content,
        );

        if (! str_contains($content, $replacements['http_response_use'])) {
            $content = str_replace(
                "use Stripe\\Exception\\ApiErrorException;\n",
                "use Stripe\\Exception\\ApiErrorException;\n".$replacements['http_response_use']."\n",
                $content,
            );
        }
    }

    $content = str_replace($replacements['store_return'], $replacements['store_return_fixed'], $content);

    if ($content === $original) {
        echo "WARN no changes for $name\n";
        continue;
    }

    if (file_put_contents($path, $content) === false) {
        echo "FAIL write: $path\n";
        continue;
    }

    echo "PATCHED: $name\n";
}

echo "\nNext: https://www.tesora.com.br/clear-cache-tesora.php\n";
echo "DELETE patch-inertia-checkout-redirect-tesora.php when done.\n";
