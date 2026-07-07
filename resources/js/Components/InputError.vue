<script setup>
defineProps({
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div v-show="message" class="invalid-feedback d-block">
        {{ message }}
    </div>
</template>
