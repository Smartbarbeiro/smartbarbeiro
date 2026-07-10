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
            width="800"
            height="602"
        />

        <p class="barbershop-empty-dashboard__message mb-0">
            Sua barbearia ainda não tem assinantes...
        </p>

        <div class="barbershop-empty-dashboard__actions">
            <Link
                v-if="!hasConfiguredServicePlans"
                :href="route('services.index')"
                class="btn btn-dark barbershop-empty-dashboard__action-btn"
            >
                <i class="bi bi-scissors me-2" aria-hidden="true"></i>
                Crie seus planos
            </Link>

            <ProfileQrCode
                v-else
                class="barbershop-empty-dashboard__qr"
                :url="profileUrl"
                :filename="`${profileUsername}-profile`"
                owner-dashboard
                show-acrylic-order
                :acrylic-order="acrylicQrOrder"
            />
        </div>
    </section>
</template>
