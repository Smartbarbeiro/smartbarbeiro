<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'sidebar',
        validator: (value) => ['sidebar', 'bottom'].includes(value),
    },
});
</script>

<template>
    <Link
        :href="href"
        :class="[
            variant === 'bottom' ? 'mobile-bottom-link' : 'sidebar-link',
            { active },
        ]"
        :title="variant === 'sidebar' ? undefined : label"
        :aria-label="label"
    >
        <i :class="`bi bi-${icon}`"></i>
        <span
            v-if="variant === 'bottom'"
            class="mobile-bottom-label"
        >
            {{ label }}
        </span>
        <span v-else class="sidebar-link-label">
            {{ label }}
        </span>
    </Link>
</template>
