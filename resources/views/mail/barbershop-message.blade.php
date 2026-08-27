<x-mail::message>
# {{ $messageSubject }}

Olá, **{{ $recipientName }}**!

**{{ $barbershopName }}** enviou a seguinte mensagem:

{{ $messageBody }}

<x-mail::button :url="$inboxUrl">
Ver no Tesora
</x-mail::button>

Você também pode ler esta mensagem na sua caixa de entrada do Tesora.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
