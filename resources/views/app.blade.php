<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#000000">
        <meta name="description" content="Tesora - A fidelização de clientes mais fácil para barbearias! Crie planos de assinatura, compartilhe seu QR code e gerencie clientes e agenda pelo celular.">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="Tesora" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <link rel="manifest" href="/site.webmanifest" />
        @endauth

        {{-- Inter early (parallel with JS). Avoid CSS @import — that delays fonts until after the Vite CSS bundle. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        >

        {{-- Local heading font: discover ASAP --}}
        @if (file_exists(public_path('fonts/kadwa-latin-400-normal.woff2')))
            <link
                rel="preload"
                href="{{ asset('fonts/kadwa-latin-400-normal.woff2') }}"
                as="font"
                type="font/woff2"
                crossorigin
            >
        @endif

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
