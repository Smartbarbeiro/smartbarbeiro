<template>
    <button type="button" class="btn btn-outline-secondary" v-bind="$attrs">
        <slot />
    </button>
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});
</script>
