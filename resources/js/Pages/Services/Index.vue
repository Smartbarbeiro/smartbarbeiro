<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DashboardContentCard from '@/Components/DashboardContentCard.vue';
import DashboardPageHeader from '@/Components/DashboardPageHeader.vue';
import ManageServicePlansForm from '@/Pages/Profile/Partials/ManageServicePlansForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    servicePlans: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Serviços" />

        <template #header>
            <DashboardPageHeader icon="service-plans" title="Serviços" />
        </template>

        <DashboardContentCard
            icon="service-plans"
            title="Planos de serviço"
            description="Monte pacotes e opcionais para seus clientes assinarem e use na agenda."
        >
            <ManageServicePlansForm :service-plans="servicePlans" />
        </DashboardContentCard>
    </AuthenticatedLayout>
</template>
