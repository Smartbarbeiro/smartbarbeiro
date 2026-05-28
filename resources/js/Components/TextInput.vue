<script setup>
import { onMounted, ref } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        v-model="model"
        class="form-control"
        v-bind="$attrs"
    />
</template>
