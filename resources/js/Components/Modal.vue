<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'lg',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const dialogClass = computed(() => {
    return {
        sm: 'modal-sm',
        md: '',
        lg: 'modal-lg',
        xl: 'modal-xl',
        '2xl': 'modal-xl',
    }[props.maxWidth];
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (event) => {
    if (event.key === 'Escape' && props.show) {
        close();
    }
};

watch(
    () => props.show,
    (visible) => {
        document.body.style.overflow = visible ? 'hidden' : '';
    },
);

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        class="modal fade"
        :class="{ show: show }"
        tabindex="-1"
        :style="{ display: show ? 'block' : 'none' }"
        aria-modal="true"
        role="dialog"
        @click.self="close"
    >
        <div class="modal-dialog" :class="dialogClass">
            <div class="modal-content bg-dark text-light border-secondary">
                <slot />
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>
