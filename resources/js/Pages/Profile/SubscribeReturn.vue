<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import PaymentEmailMismatchCard from '@/Components/PaymentEmailMismatchCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    creator: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
    paymentEmailMismatch: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assinatura" />

        <template #header>
            <DashboardPageHeader icon="status" title="Status da assinatura" />
        </template>

        <DashboardContentCard
            icon="status"
            title="Assinatura de perfil"
            description="Confirmação do pagamento e liberação de acesso ao perfil."
        >
            <div
                v-if="paymentEmailMismatch"
                class="alert alert-warning mb-3"
            >
                <PaymentEmailMismatchCard :mismatch="paymentEmailMismatch" />
            </div>

            <p
                v-if="subscription?.is_active"
                class="text-success mb-3"
            >
                Sua assinatura de {{ creator.name }} está ativa. Agora você
                tem acesso ao perfil.
            </p>
            <p v-else class="text-warning mb-3">
                Estamos confirmando seu pagamento com o Mercado Pago. Se você
                concluiu o checkout, o acesso será liberado em breve.
            </p>

            <Link
                :href="
                    route('profile.public', {
                        username: creator.username,
                    })
                "
                class="btn btn-primary btn-sm"
            >
                Ir para o perfil
            </Link>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
