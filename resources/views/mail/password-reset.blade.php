<x-mail::message>
# Redefinir sua senha

Olá{{ filled($userName) ? ', **'.$userName.'**' : '' }}!

Recebemos um pedido para redefinir a senha da sua conta no **{{ config('app.name') }}**.

<x-mail::button :url="$url">
Redefinir senha
</x-mail::button>

Este link expira em **{{ $expireMinutes }} minutos**.

Se você não solicitou a redefinição de senha, ignore este e-mail. Sua senha permanecerá a mesma.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
