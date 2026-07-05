<x-mail::message>
# Sua conta foi criada!

Olá, **{{ $barbershopName }}**!

Parabéns por criar sua barbearia no **{{ config('app.name') }}**. Sua conta já está pronta — agora é só seguir estes passos para começar a fidelizar seus clientes:

## 1. Finalizar a assinatura

Ative sua assinatura da plataforma para liberar o painel completo e receber pagamentos dos planos dos seus clientes.

<x-mail::button :url="$platformSubscribeUrl">
Finalizar a assinatura
</x-mail::button>

## 2. Criar os planos e valores

Configure os planos de corte, barba e serviços extras com os preços da sua barbearia.

<x-mail::button :url="$servicePlansUrl">
Criar planos e valores
</x-mail::button>

## 3. Compartilhar e pedir o QR code

No seu perfil, baixe o QR code digital ou peça o cartão acrílico físico. Coloque-o na barbearia para que os clientes escaneiem e assinem seus planos.

<x-mail::button :url="$profileUrl">
Ver perfil e QR code
</x-mail::button>

Quando tudo estiver pronto, acesse o painel para acompanhar assinantes e a agenda de cortes.

<x-mail::button :url="$dashboardUrl">
Ir para o painel
</x-mail::button>

Qualquer dúvida, responda este e-mail — estamos aqui para ajudar.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
