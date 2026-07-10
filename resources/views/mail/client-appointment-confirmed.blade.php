<x-mail::message>
# Agendamento confirmado!

Olá{{ filled($clientName) ? ', **'.$clientName.'**' : '' }}!

Sua solicitação na **{{ $barbershopName }}** foi confirmada.

**Serviço:** {{ $serviceLabel }}

**Data:** {{ $scheduledDate }}

**Horário:** {{ $scheduledTime }}

@if (filled($performerName))
**Profissional:** {{ $performerName }}
@endif

<x-mail::button :url="$appointmentsUrl">
Ver meus agendamentos
</x-mail::button>

<x-mail::button :url="$profileUrl">
Ver barbearia
</x-mail::button>

Até lá!<br>
{{ config('app.name') }}
</x-mail::message>
