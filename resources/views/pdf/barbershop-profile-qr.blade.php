<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <title>QR code — {{ $barbershopName }}</title>
        <style>
            @page {
                margin: 0;
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                background-color: #000000;
                color: #ffffff;
                font-family: DejaVu Sans, sans-serif;
                margin: 0;
                min-height: 100%;
                padding: 48px 40px;
            }

            .sheet {
                margin: 0 auto;
                max-width: 520px;
                text-align: center;
            }

            .brand {
                color: #ffffff;
                font-size: 12px;
                letter-spacing: 0.08em;
                margin: 0 0 24px;
                text-transform: uppercase;
            }

            h1 {
                color: #ffffff;
                font-size: 28px;
                line-height: 1.2;
                margin: 0 0 8px;
            }

            .username {
                color: #ffffff;
                font-size: 14px;
                margin: 0 0 28px;
            }

            .qr-frame {
                background: #ffffff;
                border: 2px solid #ffffff;
                border-radius: 16px;
                display: inline-block;
                margin: 0 auto 24px;
                padding: 18px;
            }

            .qr-frame img {
                display: block;
                height: 320px;
                width: 320px;
            }

            .profile-url {
                color: #ffffff;
                font-size: 13px;
                line-height: 1.5;
                margin: 0 0 18px;
                word-break: break-all;
            }

            .hint {
                color: #ffffff;
                font-size: 12px;
                line-height: 1.5;
                margin: 0;
            }

            .recipient {
                border: 1px solid #ffffff;
                border-radius: 12px;
                color: #ffffff;
                margin: 40px auto 0;
                max-width: 420px;
                padding: 16px 18px;
                text-align: left;
            }

            .recipient__title {
                color: #ffffff;
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 10px;
                text-transform: uppercase;
            }

            .recipient__line {
                color: #ffffff;
                font-size: 13px;
                line-height: 1.5;
                margin: 0 0 6px;
            }

            .recipient__line:last-child {
                margin-bottom: 0;
            }
        </style>
    </head>
    <body>
        <div class="sheet">
            <p class="brand">Smart Barbeiro</p>

            <h1>{{ $barbershopName }}</h1>

            @if ($username)
                <p class="username">{{ '@'.$username }}</p>
            @endif

            <div class="qr-frame">
                <img src="{{ $qrCodeDataUri }}" alt="QR code do perfil" />
            </div>

            <p class="profile-url">{{ $profileUrl }}</p>

            <p class="hint">
                Escaneie o QR code para abrir o perfil público desta barbearia.
            </p>

            @if ($recipient)
                <div class="recipient">
                    <p class="recipient__title">Destinatário</p>
                    <p class="recipient__line">
                        <strong>Nome:</strong> {{ $recipient['name'] }}
                    </p>
                    <p class="recipient__line">
                        <strong>Telefone:</strong> {{ $recipient['phone'] }}
                    </p>
                    <p class="recipient__line">
                        <strong>Endereço:</strong> {{ $recipient['address'] }}
                    </p>
                </div>
            @endif
        </div>
    </body>
</html>
