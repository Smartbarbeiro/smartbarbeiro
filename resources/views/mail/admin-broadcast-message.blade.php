<x-mail::message>
# {{ $messageSubject }}

Olá, **{{ $recipientName }}**!

A equipe **Smart Barbeiro** enviou a seguinte mensagem:

{{ $messageBody }}

<x-mail::button :url="$appUrl">
Abrir Smart Barbeiro
</x-mail::button>

Você também verá esta mensagem como aviso ao entrar no Smart Barbeiro.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
