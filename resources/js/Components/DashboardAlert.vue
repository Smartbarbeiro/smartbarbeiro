<script setup>
import { computed, inject, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (value) =>
            ['success', 'info', 'warning', 'danger'].includes(value),
    },
    show: {
        type: Boolean,
        default: true,
    },
    autoDismiss: {
        type: Boolean,
        default: true,
    },
    autoDismissMs: {
        type: Number,
        default: 10000,
    },
});

const stackEl = inject('dashboardAlertStack', ref(null));
const dismissed = ref(false);
let dismissTimer = null;

const visible = computed(() => props.show && !dismissed.value);
const teleportTarget = computed(() => stackEl?.value ?? null);

const clearDismissTimer = () => {
    if (dismissTimer !== null) {
        clearTimeout(dismissTimer);
        dismissTimer = null;
    }
};

const dismiss = () => {
    dismissed.value = true;
    clearDismissTimer();
};

const scheduleAutoDismiss = () => {
    clearDismissTimer();

    if (!props.autoDismiss || !visible.value) {
        return;
    }

    dismissTimer = setTimeout(() => {
        dismiss();
    }, props.autoDismissMs);
};

watch(
    () => props.show,
    (newShow) => {
        if (newShow) {
            dismissed.value = false;
        }
    },
    { immediate: true },
);

watch(
    visible,
    (isVisible) => {
        if (isVisible) {
            scheduleAutoDismiss();
        } else {
            clearDismissTimer();
        }
    },
    { immediate: true },
);

onBeforeUnmount(clearDismissTimer);
</script>

<template>
    <Teleport v-if="teleportTarget" :to="teleportTarget">
        <Transition name="dashboard-alert-fade" appear>
            <div
                v-if="visible"
                class="alert dashboard-alert"
                :class="`alert-${variant}`"
                role="alert"
            >
                <div class="dashboard-alert__content">
                    <slot />
                </div>

                <button
                    type="button"
                    class="dashboard-alert__close"
                    aria-label="Fechar alerta"
                    @click="dismiss"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
