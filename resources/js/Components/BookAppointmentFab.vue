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
  <button
    type="button"
    class="book-appointment-fab"
    aria-label="Agendar horário"
    @click="isOpen = true"
  >
    <i class="bi bi-calendar-plus" aria-hidden="true"></i>
    <span class="book-appointment-fab__label">Agendar</span>
  </button>

  <BookAppointmentModal
    :show="isOpen"
    :username="username"
    :default-service-label="defaultServiceLabel"
    :default-package-type="defaultPackageType"
    @close="isOpen = false"
  />
</template>
