<template>
    <button type="button" class="btn btn-danger" v-bind="$attrs">
        <slot />
    </button>
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});
</script>
