<?php

return [

    'barbershop_paid_signup_required' => 'Esta barbearia exige uma assinatura paga para se cadastrar.',
    'profile_no_subscription_required' => 'Este perfil não exige assinatura.',
    'payments_not_configured' => 'Os pagamentos ainda não estão configurados neste servidor.',
    'mercadopago_no_checkout_url' => 'O Mercado Pago não retornou a URL de pagamento. Tente novamente mais tarde.',
    'mercadopago_configure_token' => 'Configure MERCADOPAGO_ACCESS_TOKEN no arquivo .env para habilitar perfis pagos.',
    'subscription_cannot_be_cancelled' => 'Esta assinatura não pode ser cancelada.',

    'status' => [
        'user-updated' => 'Usuário atualizado.',
        'user-deleted' => 'Usuário excluído. O perfil, assinaturas e armazenamento foram removidos.',
        'user-frozen' => 'Conta congelada. O usuário não pode entrar nem exibir perfil público.',
        'user-unfrozen' => 'Conta descongelada.',
        'barbershop-signup-success' => 'Cadastro na barbearia realizado com sucesso.',
        'already-signed-up' => 'Você já está cadastrado nesta barbearia.',
        'already-subscribed' => 'Você já possui uma assinatura ativa neste perfil.',
        'subscription-cancelled' => 'Assinatura cancelada.',
        'subscription-plan-updated' => 'Plano de assinatura atualizado.',
        'verification-link-sent' => 'Um novo link de verificação foi enviado para o seu e-mail.',
    ],

    'subscription_status' => [
        'authorized' => 'Ativa',
        'pending' => 'Pagamento pendente',
        'paused' => 'Pausada',
        'cancelled' => 'Cancelada',
    ],

    'attributes' => [
        'name' => 'nome',
        'username' => 'nome de usuário',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação de senha',
        'title' => 'título',
        'description' => 'descrição',
        'monthly_amount' => 'valor mensal',
        'profile_photo' => 'foto de perfil',
        'is_admin' => 'administrador',
        'is_frozen' => 'conta congelada',
    ],

];
