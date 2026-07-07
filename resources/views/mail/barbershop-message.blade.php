<x-mail::message>
# {{ $messageSubject }}

Olá, **{{ $recipientName }}**!

**{{ $barbershopName }}** enviou a seguinte mensagem:

{{ $messageBody }}

<x-mail::button :url="$inboxUrl">
Ver no Smart Barbeiro
</x-mail::button>

Você também pode ler esta mensagem na sua caixa de entrada do Smart Barbeiro.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
