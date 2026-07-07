<script setup>
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    profileUrl: {
        type: String,
        required: true,
    },
    profileUsername: {
        type: String,
        required: true,
    },
    hasConfiguredServicePlans: {
        type: Boolean,
        default: false,
    },
    acrylicQrOrder: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <section class="barbershop-empty-dashboard">
        <img
            src="/images/barbearia-empty-state.png"
            alt="Ilustração de uma barbearia"
            class="barbershop-empty-dashboard__icon"
            width="560"
            height="420"
        />

        <p class="barbershop-empty-dashboard__message mb-0">
            Sua barbearia ainda não tem assinantes...
        </p>

        <Link
            :href="route('agenda.index')"
            class="btn btn-outline-dark barbershop-empty-dashboard__action-btn mt-3"
        >
            <i class="bi bi-journal-bookmark me-2" aria-hidden="true"></i>
            Abrir agenda
        </Link>

        <Link
            v-if="!hasConfiguredServicePlans"
            :href="`${route('profile.edit')}#planos-de-servico`"
            class="btn btn-dark barbershop-empty-dashboard__action-btn"
        >
            <i class="bi bi-scissors me-2" aria-hidden="true"></i>
            Crie seus planos
        </Link>

        <ProfileQrCode
            v-else
            class="barbershop-empty-dashboard__qr mt-4"
            :url="profileUrl"
            :filename="`${profileUsername}-profile`"
            owner-dashboard
            show-acrylic-order
            :acrylic-order="acrylicQrOrder"
        />
    </section>
</template>
