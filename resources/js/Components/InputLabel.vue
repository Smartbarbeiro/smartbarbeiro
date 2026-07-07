<template>
    <label class="form-label">
        <span v-if="value">{{ value }}</span>
        <slot v-else />
    </label>
</template>

<script setup>
defineProps({
    value: {
        type: String,
        default: '',
    },
});
</script>
