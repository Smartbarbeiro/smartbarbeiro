<?php

return [

    'barbershop_paid_signup_required' => 'Esta barbearia exige uma assinatura paga para se cadastrar.',
    'profile_no_subscription_required' => 'Este perfil não exige assinatura.',
    'payments_not_configured' => 'Os pagamentos ainda não estão configurados neste servidor.',
    'mercadopago_no_checkout_url' => 'O Mercado Pago não retornou a URL de pagamento. Tente novamente mais tarde.',
    'mercadopago_configure_token' => 'Configure MERCADOPAGO_ACCESS_TOKEN no arquivo .env para habilitar perfis pagos.',
    'subscription_cannot_be_cancelled' => 'Esta assinatura não pode ser cancelada.',
    'service_plan_invalid_package' => 'Selecione um pacote de serviço válido.',
    'service_plan_invalid_addons' => 'Um ou mais opcionais selecionados não estão disponíveis.',
    'service_plan_already_subscribed' => 'Você já possui um plano de serviço ativo nesta barbearia.',
    'service_plan_owner_cannot_subscribe' => 'O dono da barbearia não pode assinar o próprio plano.',
    'cep_not_found' => 'CEP não encontrado.',

    'status' => [
        'user-updated' => 'Usuário atualizado.',
        'user-deleted' => 'Usuário excluído. O perfil, assinaturas e armazenamento foram removidos.',
        'user-frozen' => 'Conta congelada. O usuário não pode entrar nem exibir perfil público.',
        'user-unfrozen' => 'Conta descongelada.',
        'barbershop-signup-success' => 'Cadastro na barbearia realizado com sucesso.',
        'service-plan-signup-pending' => 'Cadastro realizado. Confirme o pagamento do plano quando os pagamentos estiverem disponíveis.',
        'already-signed-up' => 'Você já está cadastrado nesta barbearia.',
        'already-subscribed' => 'Você já possui uma assinatura ativa neste perfil.',
        'subscription-cancelled' => 'Assinatura cancelada.',
        'subscription-plan-updated' => 'Plano de assinatura atualizado.',
        'service-plans-updated' => 'Planos de serviço atualizados.',
        'preferred-haircut-day-saved' => 'Dia preferido para o corte salvo.',
        'haircut-photo-uploaded' => 'Foto do corte enviada com sucesso.',
        'haircut-photo-deleted' => 'Foto removida.',
        'message-sent' => 'Mensagem enviada aos clientes.',
        'admin-message-sent' => 'Mensagem enviada aos destinatários.',
        'acrylic-qr-order-created' => 'Pedido de QR acrílico recebido. Entraremos em contato para produção e envio.',
        'acrylic-qr-order-updated' => 'Pedido de QR acrílico atualizado.',
        'service-plan-checkout-started' => 'Redirecionando para o pagamento.',
        'verification-link-sent' => 'Um novo link de verificação foi enviado para o seu e-mail.',
    ],

    'profile_subscription_payment' => [
        'on_time' => 'Pagamento em dia.',
        'update_method' => 'Atualize a forma de pagamento para manter seu acesso.',
        'update_button' => 'Atualizar forma de pagamento',
        'next_payment' => 'Próximo pagamento:',
    ],

    'subscription_status' => [
        'authorized' => 'Ativa',
        'pending' => 'Pagamento pendente',
        'paused' => 'Pausada',
        'cancelled' => 'Cancelada',
    ],

    'acrylic_qr_status' => [
        'pending' => 'Aguardando impressão',
        'printed' => 'Impresso',
        'shipped' => 'Enviado',
    ],

    'attributes' => [
        'name' => 'nome',
        'username' => 'nome da barbearia',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação de senha',
        'title' => 'título',
        'description' => 'descrição',
        'monthly_amount' => 'valor mensal',
        'profile_photo' => 'foto de perfil',
        'subject' => 'assunto',
        'body' => 'mensagem',
        'is_admin' => 'administrador',
        'is_frozen' => 'conta congelada',
    ],

];
