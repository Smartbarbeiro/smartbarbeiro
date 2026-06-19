<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

defineProps({
    activeNav: {
        type: String,
        default: null,
        validator: (value) => [null, 'login', 'register'].includes(value),
    },
    useFrostedCard: {
        type: Boolean,
        default: true,
    },
    variant: {
        type: String,
        default: 'login',
        validator: (value) => ['login', 'register'].includes(value),
    },
});
</script>

<template>
    <MarketingLayout :active-nav="activeNav" :show-footer="false" :show-header-nav="false">
        <section
            class="marketing-hero marketing-hero-1"
            :class="{
                'marketing-hero-centered marketing-hero-login': variant === 'login',
                'marketing-hero-register': variant === 'register',
            }"
        >
            <div :class="useFrostedCard ? 'frosted-card' : 'auth-hero-slot'">
                <slot />
            </div>
        </section>
    </MarketingLayout>
</template>
