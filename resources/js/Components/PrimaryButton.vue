<template>
    <button type="submit" class="btn btn-primary" v-bind="$attrs">
        <slot />
    </button>
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});
</script>
