<script setup>
import { computed } from 'vue';

const props = defineProps({
    intent: {
        type: String,
        default: 'login',
        validator: (value) => ['login', 'register'].includes(value),
    },
    redirect: {
        type: String,
        default: null,
    },
    isCustomer: {
        type: Boolean,
        default: false,
    },
});

const href = computed(() => {
    const params = {
        provider: 'google',
        intent: props.intent,
    };

    if (props.redirect) {
        params.redirect = props.redirect;
    }

    if (props.isCustomer) {
        params.customer = 1;
    }

    return route('auth.social.redirect', params);
});
</script>

<template>
    <a :href="href" class="btn btn-outline-dark btn-lg w-100 oauth-google-btn">
        <span class="oauth-google-btn__icon" aria-hidden="true">G</span>
        Continuar com Google
    </a>
</template>
