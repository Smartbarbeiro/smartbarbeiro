<script setup>
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    barbershop: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Plano de serviço" />

        <template #header>
            <DashboardPageHeader icon="status" title="Status do plano" />
        </template>

        <DashboardContentCard
            icon="service-plans"
            title="Plano de serviço"
            description="Confirmação do pagamento e ativação do plano na barbearia."
        >
            <p v-if="subscription?.is_active" class="text-success mb-3">
                Seu plano em {{ barbershop.name }} está ativo.
                <span v-if="subscription.package_label">
                    Pacote: {{ subscription.package_label }} ({{
                        subscription.formatted_total
                    }}/mês)
                </span>
            </p>
            <p v-else class="text-warning mb-3">
                Estamos confirmando seu pagamento com a Stripe. Se você
                concluiu o checkout, o plano será ativado em breve.
            </p>

            <Link
                :href="
                    route('profile.public', {
                        username: barbershop.username,
                    })
                "
                class="btn btn-primary btn-sm"
            >
                Ir para a barbearia
            </Link>
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
