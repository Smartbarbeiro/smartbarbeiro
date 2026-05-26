<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    photoUrl: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'lg',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
    },
});

const sizeClasses = {
    sm: 'h-10 w-10 text-sm',
    md: 'h-16 w-16 text-lg',
    lg: 'h-24 w-24 text-2xl',
    xl: 'h-32 w-32 text-3xl',
};

const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return (
        parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
    ).toUpperCase();
});
</script>

<template>
    <div
        class="shrink-0 overflow-hidden rounded-full bg-indigo-100"
        :class="sizeClasses[size]"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="`${name} profile photo`"
            class="h-full w-full object-cover"
        />
        <div
            v-else
            class="flex h-full w-full items-center justify-center font-semibold text-indigo-700"
        >
            {{ initials }}
        </div>
    </div>
</template>
