<x-mail::message>
# Nova solicitação de agendamento

Olá{{ filled($barbershopName) ? ', **'.$barbershopName.'**' : '' }}!

**{{ $clientName }}** pediu um horário na sua barbearia.

**Serviço:** {{ $serviceLabel }}

**Data:** {{ $scheduledDate }}

**Horário:** {{ $scheduledTime }}

@if (filled($clientNotes))
**Observações do cliente:** {{ $clientNotes }}
@endif

Confirme ou recuse a solicitação na agenda.

<x-mail::button :url="$agendaUrl">
Abrir agenda
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
