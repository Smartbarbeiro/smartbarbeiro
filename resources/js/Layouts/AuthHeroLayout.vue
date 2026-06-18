<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

defineProps({
    activeNav: {
        type: String,
        default: null,
        validator: (value) => [null, 'login', 'register'].includes(value),
    },
});
</script>

<template>
    <MarketingLayout :active-nav="activeNav" :show-footer="false" :show-header-nav="false">
        <section class="marketing-hero marketing-hero-1 marketing-hero-centered marketing-hero-login">
            <div class="frosted-card">
                <slot />
            </div>
        </section>
    </MarketingLayout>
</template>
