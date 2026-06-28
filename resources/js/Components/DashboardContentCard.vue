<script setup>
import DashboardPageIcon from '@/Components/DashboardPageIcon.vue';

defineProps({
    icon: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
    nested: {
        type: Boolean,
        default: false,
    },
    centered: {
        type: Boolean,
        default: false,
    },
    cardClass: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <section
        class="dashboard-content-card"
        :class="[
            {
                'dashboard-content-card--nested': nested,
                'dashboard-content-card--centered': centered,
                'dashboard-content-card--intro-only': icon && title && !$slots.default,
            },
            cardClass,
        ]"
    >
        <div
            v-if="icon && title && !nested"
            class="dashboard-content-card__intro"
        >
            <DashboardPageIcon :name="icon" class="dashboard-content-card__icon" />
            <h2 class="dashboard-content-card__title">{{ title }}</h2>
            <p
                v-if="description"
                class="dashboard-content-card__description"
            >
                {{ description }}
            </p>
        </div>

        <div
            v-if="$slots.default"
            class="dashboard-content-card__body"
            :class="{ 'dashboard-content-card__body--solo': nested || !icon || !title }"
        >
            <slot />
        </div>
    </section>
</template>
