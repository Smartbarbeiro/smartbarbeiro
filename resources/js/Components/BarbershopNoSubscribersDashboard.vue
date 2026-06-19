<script setup>
import ProfileQrCode from '@/Components/ProfileQrCode.vue';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    profileUrl: {
        type: String,
        required: true,
    },
    acrylicOrder: {
        type: Object,
        default: null,
    },
});

const showQrCode = ref(false);
const page = usePage();
</script>

<template>
    <section class="barbershop-empty-dashboard">
        <img
            src="/images/barbearia-empty-state.png"
            alt=""
            class="barbershop-empty-dashboard__icon"
            width="280"
            height="280"
        />

        <p class="barbershop-empty-dashboard__message mb-0">
            Você ainda não tem assinantes!
        </p>

        <button
            type="button"
            class="btn btn-dark barbershop-empty-dashboard__share-btn"
            @click="showQrCode = !showQrCode"
        >
            Compartilhar Qr-code
        </button>

        <div v-show="showQrCode" class="barbershop-empty-dashboard__qr">
            <ProfileQrCode
                :url="profileUrl"
                :filename="`${page.props.auth.user.username}-profile`"
                hide-share-button
                default-expanded
                show-acrylic-order
                :acrylic-order="acrylicOrder"
            />
        </div>
    </section>
</template>
