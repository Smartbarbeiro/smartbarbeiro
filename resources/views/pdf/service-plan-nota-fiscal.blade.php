<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <title>Nota fiscal — {{ $periodLabel }}</title>
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                color: #111827;
                font-family: DejaVu Sans, sans-serif;
                font-size: 12px;
                line-height: 1.5;
                margin: 0;
                padding: 36px 40px;
            }

            .header {
                border-bottom: 2px solid #111827;
                margin-bottom: 24px;
                padding-bottom: 16px;
            }

            h1 {
                font-size: 22px;
                margin: 0 0 4px;
            }

            .subtitle {
                color: #4b5563;
                margin: 0;
            }

            .section {
                margin-bottom: 20px;
            }

            .section-title {
                font-size: 11px;
                font-weight: bold;
                letter-spacing: 0.08em;
                margin: 0 0 8px;
                text-transform: uppercase;
            }

            .grid {
                width: 100%;
            }

            .grid td {
                padding: 4px 0;
                vertical-align: top;
            }

            .label {
                color: #6b7280;
                width: 140px;
            }

            .amount-box {
                background: #f3f4f6;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                margin-top: 24px;
                padding: 16px 18px;
            }

            .amount-box strong {
                font-size: 20px;
            }

            .footer {
                border-top: 1px solid #d1d5db;
                color: #6b7280;
                font-size: 10px;
                margin-top: 32px;
                padding-top: 12px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Nota fiscal eletrônica</h1>
            <p class="subtitle">Comprovante de pagamento de plano de serviço</p>
        </div>

        <div class="section">
            <p class="section-title">Prestador</p>
            <table class="grid">
                <tr>
                    <td class="label">Barbearia</td>
                    <td>{{ $barbershopName }}</td>
                </tr>
                <tr>
                    <td class="label">Perfil</td>
                    <td>@{{ $barbershopUsername }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <p class="section-title">Cliente</p>
            <table class="grid">
                <tr>
                    <td class="label">Nome</td>
                    <td>{{ $subscriberName }}</td>
                </tr>
                <tr>
                    <td class="label">E-mail</td>
                    <td>{{ $subscriberEmail }}</td>
                </tr>
                @if ($subscriberDocument)
                    <tr>
                        <td class="label">CPF/CNPJ</td>
                        <td>{{ $subscriberDocument }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="section">
            <p class="section-title">Serviço</p>
            <table class="grid">
                <tr>
                    <td class="label">Plano</td>
                    <td>{{ $packageLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Competência</td>
                    <td>{{ $periodLabel }}</td>
                </tr>
                <tr>
                    <td class="label">Referência</td>
                    <td>{{ $invoiceReference }}</td>
                </tr>
                <tr>
                    <td class="label">Pagamento confirmado</td>
                    <td>{{ $paidAt }}</td>
                </tr>
            </table>
        </div>

        <div class="amount-box">
            Valor pago: <strong>{{ $formattedAmount }}</strong>
        </div>

        <p class="footer">
            Documento emitido eletronicamente pela plataforma Smart Barbeiro para fins de
            comprovação de pagamento. Este documento não substitui a NF-e oficial quando
            exigida por lei, salvo se emitida pela barbearia em sistema fiscal próprio.
        </p>
    </body>
</html>
