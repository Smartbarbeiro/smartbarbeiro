<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    subscription: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Assinatura da plataforma" />

        <template #header>
            <DashboardPageHeader icon="status" title="Status da assinatura" />
        </template>

        <DashboardContentCard
            icon="status"
            title="Assinatura da plataforma"
            description="Confirmação do pagamento e liberação de acesso."
            card-class="dashboard-content-card--narrow"
        >
            <p
                v-if="subscription?.is_active"
                class="text-success mb-3"
            >
                Sua assinatura da plataforma está ativa. Agora você pode usar
                todos os recursos da Smart Barbeiro.
            </p>
            <p v-else class="text-warning mb-3">
                Estamos confirmando seu pagamento com o Mercado Pago. Se você
                concluiu o checkout, o acesso será liberado em breve.
            </p>

            <Link
                :href="route('dashboard')"
                class="btn btn-primary btn-sm"
            >
                Ir para o painel
            </Link>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
