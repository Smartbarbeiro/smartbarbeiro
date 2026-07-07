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
                background-color: #ffffff;
                color: #000000;
                font-family: DejaVu Sans, sans-serif;
                margin: 0;
                padding: 0;
            }

            .layout {
                border-collapse: collapse;
                width: 100%;
            }

            .layout td {
                padding: 0;
                vertical-align: top;
            }

            .sheet-top-wrap {
                background-color: #ffffff;
                padding: 36px 0;
            }

            .sheet-top {
                background-color: #000000;
                color: #ffffff;
                padding: 56px 40px 48px;
            }

            .sheet-bottom {
                background-color: #ffffff;
                color: #000000;
                padding: 24px 32px 40px;
                text-align: center;
            }

            .content {
                margin: 0 auto;
                max-width: 520px;
                text-align: center;
                width: 100%;
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
                margin: 0 auto;
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
                margin: 24px 0 0;
                word-break: break-all;
            }

            .hint {
                color: #dddddd;
                font-size: 12px;
                line-height: 1.5;
                margin: 18px 0 0;
            }

            .recipient {
                border: 1px solid #cccccc;
                border-radius: 12px;
                color: #000000;
                margin: 36px auto 0;
                max-width: 420px;
                padding: 16px 18px;
                text-align: left;
            }

            .recipient__title {
                color: #000000;
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 10px;
                text-transform: uppercase;
            }

            .recipient__contact {
                border-collapse: collapse;
                margin: 0 0 6px;
                width: 100%;
            }

            .recipient__contact td {
                color: #000000;
                font-size: 13px;
                line-height: 1.5;
                padding: 0;
                vertical-align: top;
            }

            .recipient__contact-name {
                text-align: left;
                width: 55%;
            }

            .recipient__contact-phone {
                text-align: right;
                width: 45%;
            }

            .recipient__line {
                color: #000000;
                font-size: 13px;
                line-height: 1.5;
                margin: 0;
            }

            .mini-qr-row {
                margin: 32px auto 0;
                text-align: center;
                width: 100%;
            }

            .mini-qr-row table {
                border-collapse: collapse;
                margin: 0 auto;
                width: auto;
            }

            .mini-qr-copy {
                padding: 10px 12px;
                text-align: center;
                vertical-align: top;
            }

            .mini-qr-copy--second-row {
                padding-top: 18px;
            }

            .mini-qr-cut {
                border: 1px dashed #888888;
                display: inline-block;
                padding: 12px 10px 10px;
            }

            .mini-qr-label {
                color: #000000;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.04em;
                margin: 0 0 10px;
                text-transform: uppercase;
            }

            .mini-qr-frame {
                background: #ffffff;
                border: 1px solid #bbbbbb;
                display: inline-block;
                padding: 8px;
            }

            .mini-qr-frame img {
                display: block;
                height: 156px;
                width: 156px;
            }
        </style>
    </head>
    <body>
        <table class="layout" role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td bgcolor="#ffffff" class="sheet-top-wrap" style="background-color: #ffffff;">
                    <div class="sheet-top" style="background-color: #000000; color: #ffffff;">
                        <div class="content">
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
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td bgcolor="#ffffff" class="sheet-bottom" style="background-color: #ffffff; color: #000000;">
                    <div class="content">
                        @if ($recipient)
                            <div class="recipient">
                                <p class="recipient__title">Destinatário</p>
                                <table class="recipient__contact" role="presentation">
                                    <tr>
                                        <td class="recipient__contact-name">
                                            <strong>Nome:</strong> {{ $recipient['name'] }}
                                        </td>
                                        <td class="recipient__contact-phone">
                                            <strong>Telefone:</strong> {{ $recipient['phone'] }}
                                        </td>
                                    </tr>
                                </table>
                                <p class="recipient__line">
                                    <strong>Endereço:</strong> {{ $recipient['address'] }}
                                </p>
                            </div>
                        @endif

                        <div class="mini-qr-row">
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    @foreach (range(1, 3) as $copy)
                                        <td class="mini-qr-copy">
                                            <div class="mini-qr-cut">
                                                <p class="mini-qr-label">Plano Mensal</p>
                                                <div class="mini-qr-frame">
                                                    <img src="{{ $miniQrCodeDataUri }}" alt="QR code Plano Mensal" />
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach (range(1, 3) as $copy)
                                        <td class="mini-qr-copy mini-qr-copy--second-row">
                                            <div class="mini-qr-cut">
                                                <p class="mini-qr-label">Plano Mensal</p>
                                                <div class="mini-qr-frame">
                                                    <img src="{{ $miniQrCodeDataUri }}" alt="QR code Plano Mensal" />
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
