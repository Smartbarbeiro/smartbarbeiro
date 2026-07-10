<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    enabled: {
        type: Boolean,
        default: false,
    },
    connect: {
        type: Object,
        default: () => ({
            account_id: null,
            charges_enabled: false,
            payouts_enabled: false,
            details_submitted: false,
            ready: false,
        }),
    },
});

const starting = ref(false);

const statusLabel = computed(() => {
    if (!props.enabled) {
        return 'Indisponível';
    }

    if (props.connect?.ready) {
        return 'Recebimentos ativos';
    }

    if (props.connect?.details_submitted || props.connect?.account_id) {
        return 'Configuração pendente';
    }

    return 'Não conectado';
});

const statusBadgeClass = computed(() => {
    if (!props.enabled) {
        return 'badge bg-secondary';
    }

    if (props.connect?.ready) {
        return 'badge bg-success';
    }

    if (props.connect?.account_id) {
        return 'badge bg-warning text-dark';
    }

    return 'badge bg-secondary';
});

const statusDescription = computed(() => {
    if (!props.enabled) {
        return 'A Stripe ainda não está habilitada neste servidor para recebimentos de planos de serviço.';
    }

    if (props.connect?.ready) {
        return 'Sua conta Stripe Express está pronta. Os pagamentos dos clientes vão para você, com a taxa da plataforma.';
    }

    if (props.connect?.account_id) {
        return 'Continue o cadastro na Stripe para liberar cobranças e saques.';
    }

    return 'Conecte sua conta Stripe para receber os pagamentos dos planos de corte dos clientes.';
});

const buttonLabel = computed(() => {
    if (props.connect?.ready) {
        return 'Atualizar dados na Stripe';
    }

    if (props.connect?.account_id) {
        return 'Continuar configuração';
    }

    return 'Conectar Stripe';
});

const startOnboarding = () => {
    starting.value = true;
    router.post(
        route('stripe-connect.start'),
        {},
        {
            onFinish: () => {
                starting.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="d-flex flex-column gap-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <p class="mb-1 fw-semibold">Recebimentos dos planos (Stripe)</p>
                <span :class="statusBadgeClass">{{ statusLabel }}</span>
            </div>
        </div>

        <p class="mb-0 text-body-secondary">{{ statusDescription }}</p>

        <div v-if="enabled" class="d-flex flex-wrap gap-2">
            <PrimaryButton
                type="button"
                :disabled="starting"
                @click="startOnboarding"
            >
                {{ starting ? 'Abrindo Stripe…' : buttonLabel }}
            </PrimaryButton>
        </div>
    </div>
</template>
