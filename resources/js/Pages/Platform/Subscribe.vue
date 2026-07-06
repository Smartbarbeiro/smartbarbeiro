<script setup>
import DashboardAlert from '@/Components/DashboardAlert.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

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

const startCheckout = async () => {
    if (form.processing) {
        return;
    }

    form.clearErrors();
    form.processing = true;

    try {
        const response = await fetch(route('platform.subscribe.store'), {
            method: 'POST',
            headers: {
                Accept: 'text/html, application/xhtml+xml',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                'X-Inertia': 'true',
                'X-Inertia-Version': page.version ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
            credentials: 'same-origin',
        });

        const checkoutUrl = response.headers.get('X-Inertia-Location');

        if (checkoutUrl) {
            window.location.assign(checkoutUrl);
            return;
        }

        if (response.redirected && response.url) {
            window.location.assign(response.url);
            return;
        }

        const payload = await response.json().catch(() => null);

        if (payload?.errors?.subscribe) {
            form.setError('subscribe', payload.errors.subscribe);
        }
    } catch {
        form.setError(
            'subscribe',
            'Não foi possível abrir o pagamento. Tente novamente.',
        );
    } finally {
        form.processing = false;
    }
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

            <p v-if="plan.description" class="text-secondary mb-4">
                {{ plan.description }}
            </p>

            <p v-if="subscription && !subscription.is_active" class="text-warning mb-3">
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

            <InputError class="mb-3" :message="form.errors.subscribe" />

            <PrimaryButton
                type="button"
                class="w-100"
                :disabled="form.processing || !paymentsConfigured"
                @click="startCheckout"
            >
                Assinar com Mercado Pago
            </PrimaryButton>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
