<script setup>
defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const logoUrl = '/images/logo.png';
</script>

<template>
    <img
        :src="logoUrl"
        alt="Tesora"
        class="app-logo"
        :class="`app-logo-${size}`"
    />
</template>
