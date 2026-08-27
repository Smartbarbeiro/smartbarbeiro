<x-mail::message>
@if ($isPlanUpdate)
# Seu plano foi atualizado
@else
# Pagamento confirmado!
@endif

Olá{{ filled($clientName) ? ', **'.$clientName.'**' : '' }}!

@if ($isPlanUpdate)
Seu plano na **{{ $barbershopName }}** foi alterado e o pagamento foi confirmado. Confira os detalhes:
@else
Seu plano na **{{ $barbershopName }}** foi confirmado com sucesso. Você já pode usar seus benefícios na barbearia.
@endif

**Plano:** {{ $packageLabel }}

@if (count($addonLabels) > 0)
**Opcionais:**
@foreach ($addonLabels as $addonLabel)
- {{ $addonLabel }}
@endforeach
@endif

**Valor mensal:** {{ $formattedTotal }}

@if (filled($nextPaymentDate))
**Próxima cobrança:** {{ $nextPaymentDate }}
@endif

<x-mail::button :url="$profileUrl">
Ver barbearia
</x-mail::button>

Acompanhe sua assinatura e preferências no painel do Tesora.

<x-mail::button :url="$dashboardUrl">
Ir para o painel
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
