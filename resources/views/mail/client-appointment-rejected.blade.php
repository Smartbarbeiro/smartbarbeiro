<x-mail::message>
# Agendamento não confirmado

Olá{{ filled($clientName) ? ', **'.$clientName.'**' : '' }}!

A **{{ $barbershopName }}** não pôde confirmar seu horário solicitado.

**Serviço:** {{ $serviceLabel }}

**Data:** {{ $scheduledDate }}

**Horário:** {{ $scheduledTime }}

Você pode escolher outro horário disponível.

<x-mail::button :url="$appointmentsUrl">
Agendar novamente
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
