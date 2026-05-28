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
    sm: 'avatar-sm',
    md: 'avatar-md',
    lg: 'avatar-lg',
    xl: 'avatar-xl',
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
        class="avatar-circle"
        :class="[sizeClasses[size], { 'avatar-has-photo': photoUrl }]"
    >
        <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="`${name} profile photo`"
            class="w-100 h-100 object-fit-cover"
        />
        <span v-else>{{ initials }}</span>
    </div>
</template>
