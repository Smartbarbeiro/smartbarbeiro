<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mismatch: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const billingForm = useForm({
    action: 'billing',
    payer_email: props.mismatch.payer_email,
});

const accountForm = useForm({
    action: 'account',
    payer_email: props.mismatch.payer_email,
});

const canUseAccountEmail = computed(
    () =>
        props.mismatch.account_email.toLowerCase()
        !== props.mismatch.payer_email.toLowerCase(),
);

const saveBillingEmail = () => {
    billingForm.post(route('profile.payment-email.update'), {
        preserveScroll: true,
    });
};

const useAccountEmail = () => {
    if (
        !window.confirm(
            `Alterar o e-mail da sua conta para ${props.mismatch.payer_email}? Você passará a entrar com esse e-mail.`,
        )
    ) {
        return;
    }

    accountForm.post(route('profile.payment-email.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div
        class="payment-email-mismatch"
        :class="{ 'payment-email-mismatch--compact': compact }"
        role="alert"
    >
        <p class="payment-email-mismatch__title fw-semibold mb-2">
            E-mail do Mercado Pago diferente do cadastro
        </p>
        <p class="payment-email-mismatch__text small mb-3">
            Você se cadastrou com
            <strong>{{ mismatch.account_email }}</strong
            >, mas o pagamento foi feito com
            <strong>{{ mismatch.payer_email }}</strong
            >. Para evitar problemas com cobranças e recibos, alinhe os
            e-mails abaixo.
        </p>

        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
            <PrimaryButton
                type="button"
                :disabled="billingForm.processing"
                @click="saveBillingEmail"
            >
                Usar {{ mismatch.payer_email }} como e-mail de pagamento
            </PrimaryButton>
            <SecondaryButton
                v-if="canUseAccountEmail"
                type="button"
                :disabled="accountForm.processing"
                @click="useAccountEmail"
            >
                Alterar e-mail da conta para {{ mismatch.payer_email }}
            </SecondaryButton>
        </div>
    </div>
</template>
