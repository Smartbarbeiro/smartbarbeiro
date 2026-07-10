<script setup>
import BookAppointmentModal from '@/Components/BookAppointmentModal.vue';
import { ref } from 'vue';

defineProps({
    username: {
        type: String,
        required: true,
    },
    defaultServiceLabel: {
        type: String,
        required: true,
    },
    defaultPackageType: {
        type: String,
        default: null,
    },
});

const isOpen = ref(false);
</script>

<template>
    <div class="book-appointment-card">
        <button
            type="button"
            class="btn btn-primary"
            @click="isOpen = true"
        >
            <i class="bi bi-calendar-plus me-2"></i>
            Agendar horário
        </button>

        <BookAppointmentModal
            :show="isOpen"
            :username="username"
            :default-service-label="defaultServiceLabel"
            :default-package-type="defaultPackageType"
            @close="isOpen = false"
        />
    </div>
</template>
