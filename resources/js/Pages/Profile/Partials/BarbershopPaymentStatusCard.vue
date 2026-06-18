<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    subscriptionPlan: {
        type: Object,
        default: null,
    },
    subscribeUrl: {
        type: String,
        default: null,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
    activeSubscribersCount: {
        type: Number,
        default: 0,
    },
});

const isPaidProfileEnabled = computed(
    () => props.subscriptionPlan?.is_enabled ?? false,
);

const isPaymentSynced = computed(
    () => !!props.subscriptionPlan?.payment_synced,
);

const statusLabel = computed(() => {
    if (!props.mercadopagoConfigured) {
        return 'Pagamentos indisponíveis';
    }

    if (!isPaidProfileEnabled.value) {
        return 'Perfil gratuito';
    }

    if (!isPaymentSynced.value) {
        return 'Pagamento pendente';
    }

    return 'Pagamentos ativos';
});

const statusBadgeClass = computed(() => {
    if (!props.mercadopagoConfigured) {
        return 'badge bg-secondary';
    }

    if (!isPaidProfileEnabled.value) {
        return 'badge bg-success';
    }

    if (!isPaymentSynced.value) {
        return 'badge bg-warning text-dark';
    }

    return 'badge bg-success';
});

const statusDescription = computed(() => {
    if (!props.mercadopagoConfigured) {
        return 'Os pagamentos ainda não estão configurados neste servidor. Entre em contato com o suporte para habilitar o Mercado Pago.';
    }

    if (!isPaidProfileEnabled.value) {
        return 'Seu perfil público está aberto. Clientes podem se cadastrar sem assinatura paga.';
    }

    if (!isPaymentSynced.value) {
        return 'O plano pago está ativo, mas ainda precisa ser sincronizado com o Mercado Pago.';
    }

    return 'Seu plano pago está ativo e pronto para receber assinaturas.';
});

const canUpdatePayment = computed(
    () =>
        props.mercadopagoConfigured &&
        isPaidProfileEnabled.value,
);

const syncForm = useForm({
    is_enabled: props.subscriptionPlan?.is_enabled ?? false,
    title: props.subscriptionPlan?.title ?? 'Acesso mensal ao perfil',
    description:
        props.subscriptionPlan?.description ??
        'Assine para ter acesso mensal ao conteúdo exclusivo do meu perfil.',
    monthly_amount: props.subscriptionPlan?.monthly_amount ?? 29.9,
});

const updatePayment = () => {
    if (!canUpdatePayment.value) {
        return;
    }

    syncForm.put(route('profile.subscription-plan.update'), {
        preserveScroll: true,
    });
};

const copySubscribeLink = async () => {
    if (!props.subscribeUrl) {
        return;
    }

    await navigator.clipboard.writeText(props.subscribeUrl);
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5 fw-semibold mb-1">Status do pagamento</h2>
            <p class="text-secondary small mb-0">
                Veja se seu perfil está configurado para cobrança e atualize o
                pagamento no Mercado Pago quando necessário.
            </p>
        </header>

        <div class="mt-4">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="fw-medium">Situação:</span>
                <span :class="statusBadgeClass">{{ statusLabel }}</span>
            </div>

            <p class="text-secondary small mb-0">
                {{ statusDescription }}
            </p>

            <dl
                v-if="isPaidProfileEnabled && mercadopagoConfigured"
                class="row small mt-3 mb-0"
            >
                <dt class="col-sm-4 text-secondary">Plano</dt>
                <dd class="col-sm-8 mb-2">
                    {{ subscriptionPlan?.title }}
                    <span class="text-secondary">
                        ({{ subscriptionPlan?.formatted_price }}/mês)
                    </span>
                </dd>

                <dt class="col-sm-4 text-secondary">Assinantes ativos</dt>
                <dd class="col-sm-8 mb-2">{{ activeSubscribersCount }}</dd>

                <template v-if="isPaymentSynced && subscribeUrl">
                    <dt class="col-sm-4 text-secondary">Link de assinatura</dt>
                    <dd class="col-sm-8 mb-0">
                        <span class="font-monospace text-break d-block">{{
                            subscribeUrl
                        }}</span>
                        <button
                            type="button"
                            class="btn btn-link link-primary p-0 mt-1"
                            @click="copySubscribeLink"
                        >
                            Copiar link
                        </button>
                    </dd>
                </template>
            </dl>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
            <PrimaryButton
                v-if="canUpdatePayment"
                type="button"
                :disabled="syncForm.processing"
                @click="updatePayment"
            >
                Atualizar pagamento
            </PrimaryButton>

            <Link
                v-if="isPaidProfileEnabled"
                :href="route('subscriptions.index')"
                class="link-secondary small"
            >
                Ver clientes assinantes
            </Link>

            <p
                v-if="$page.props.flash?.status === 'subscription-plan-updated'"
                class="text-secondary small mb-0"
            >
                Pagamento atualizado.
            </p>
        </div>
    </section>
</template>
