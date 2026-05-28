<script setup>
defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const logoUrl = 'https://smartbarbeiro.com.br/logo.png';
</script>

<template>
    <img
        :src="logoUrl"
        alt="Smart Barbeiro"
        class="app-logo"
        :class="`app-logo-${size}`"
    />
</template>
