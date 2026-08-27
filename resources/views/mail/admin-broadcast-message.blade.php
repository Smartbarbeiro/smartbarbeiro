<x-mail::message>
# {{ $messageSubject }}

Olá, **{{ $recipientName }}**!

A equipe **Tesora** enviou a seguinte mensagem:

{{ $messageBody }}

<x-mail::button :url="$appUrl">
Abrir Tesora
</x-mail::button>

Você também verá esta mensagem como aviso ao entrar no Tesora.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
