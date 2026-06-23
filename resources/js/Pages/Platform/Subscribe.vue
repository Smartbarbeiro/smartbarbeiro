<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

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

const startCheckout = () => {
    form.post(route('platform.subscribe.store'));
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assinar plataforma" />

        <template #header>
            <h1 class="h4 mb-0 fw-semibold">Assine o plano da plataforma</h1>
        </template>

        <div class="app-card p-4 mx-auto" style="max-width: 32rem">
            <p class="text-muted small mb-1">Plano mensal</p>
            <h2 class="h3 fw-semibold mb-2">{{ plan.title }}</h2>
            <p class="display-6 fw-bold mb-3">{{ plan.formatted_price }}</p>

            <p v-if="plan.description" class="text-muted mb-4">
                {{ plan.description }}
            </p>

            <p v-if="subscription && !subscription.is_active" class="text-warning mb-3">
                Status: {{ subscription.status_label }}
            </p>

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
        </div>
    </AuthenticatedLayout>
</template>
