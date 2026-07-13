<script setup>
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
    paymentsConfigured: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({});

const subscribeError = computed(
    () => page.props.errors?.subscribe ?? form.errors.subscribe ?? null,
);

const onTrial = computed(() => Boolean(props.subscription?.is_on_trial));

const trialDaysRemaining = computed(
    () => props.subscription?.trial_days_remaining ?? null,
);

const startCheckout = () => {
    if (form.processing) {
        return;
    }

    form.clearErrors();

    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (!token) {
        form.setError(
            'subscribe',
            'Sessão expirada. Recarregue a página e tente novamente.',
        );

        return;
    }

    form.processing = true;

    const nativeForm = document.createElement('form');
    nativeForm.method = 'POST';
    nativeForm.action = route('platform.subscribe.store');

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = token;
    nativeForm.appendChild(csrfInput);

    document.body.appendChild(nativeForm);
    nativeForm.submit();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assinar plataforma" />

        <template #header>
            <DashboardPageHeader
                icon="platform-plan"
                title="Assine o plano da plataforma"
            />
        </template>

        <DashboardContentCard
            icon="platform-plan"
            title="Plano da plataforma"
            description="Assine o plano mensal para manter seu perfil público ativo."
            card-class="mx-auto dashboard-content-card--narrow"
        >
            <p class="text-secondary small mb-1">Plano mensal</p>
            <h2 class="h3 fw-semibold mb-2">{{ plan.title }}</h2>
            <p class="display-6 fw-bold mb-3">{{ plan.formatted_price }}</p>

            <p v-if="plan.trial_days" class="text-success mb-3">
                {{ plan.trial_days }} dias grátis no cadastro
            </p>

            <p v-if="plan.description" class="text-secondary mb-4">
                {{ plan.description }}
            </p>

            <DashboardAlert
                :show="onTrial"
                variant="success"
                class="mb-3"
            >
                Você está no período gratuito
                <template v-if="trialDaysRemaining !== null">
                    — restam {{ trialDaysRemaining }}
                    {{ trialDaysRemaining === 1 ? 'dia' : 'dias' }}
                </template>
                . Pode assinar agora ou continuar usando a plataforma de graça.
            </DashboardAlert>

            <p
                v-if="!onTrial && subscription && !subscription.is_active"
                class="text-warning mb-3"
            >
                Status: {{ subscription.status_label }}
            </p>

            <DashboardAlert
                :show="page.props.flash?.status === 'platform-subscription-pending'"
                variant="warning"
                class="mb-3"
            >
                {{ page.props.flash?.statusMessage }}
            </DashboardAlert>

            <p v-if="!paymentsConfigured" class="text-warning mb-4">
                Os pagamentos ainda não estão configurados neste servidor. Seu
                cadastro foi salvo; tente novamente quando o pagamento estiver
                disponível.
            </p>

            <InputError class="mb-3" :message="subscribeError" />

            <form @submit.prevent="startCheckout" class="d-grid gap-2">
                <PrimaryButton
                    type="submit"
                    class="w-100"
                    :disabled="form.processing || !paymentsConfigured"
                >
                    Assinar com Mercado Pago
                </PrimaryButton>
            </form>

            <p v-if="onTrial" class="mt-3 mb-0">
                <Link
                    :href="route('dashboard')"
                    class="btn btn-outline-secondary w-100"
                >
                    Continuar com período gratuito
                </Link>
            </p>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
