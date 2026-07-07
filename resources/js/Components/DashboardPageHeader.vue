<script setup>
import DashboardPageIcon from '@/Components/DashboardPageIcon.vue';

defineProps({
    icon: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="dashboard-page-header">
        <DashboardPageIcon :name="icon" :size="28" class="dashboard-page-header__icon" />
        <h1 class="dashboard-page-header__title h4 mb-0 fw-semibold">
            {{ title }}
        </h1>
    </div>
</template>
