<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    hasSignedUp: {
        type: Boolean,
        default: false,
    },
    requiresPayment: {
        type: Boolean,
        default: false,
    },
    mercadopagoConfigured: {
        type: Boolean,
        default: false,
    },
});

const registerUrl = computed(() =>
    route('register', {
        redirect: `/barbearias/${props.profile.username}`,
    }),
);

const loginUrl = computed(() =>
    route('login', {
        redirect: `/barbearias/${props.profile.username}`,
    }),
);
</script>

<template>
    <div v-if="!isOwner">
        <div
            v-if="hasSignedUp"
            class="alert alert-success mb-0"
            role="alert"
        >
            Você está cadastrado nesta barbearia.
        </div>

        <div
            v-else
            class="d-flex flex-column align-items-start gap-3 flex-sm-row align-items-sm-center"
        >
            <Link
                v-if="!isAuthenticated"
                :href="registerUrl"
                class="text-decoration-none"
            >
                <PrimaryButton type="button">Cadastrar-se nesta barbearia</PrimaryButton>
            </Link>

            <template v-else-if="requiresPayment">
                <Link
                    v-if="mercadopagoConfigured"
                    :href="
                        route('profile.subscribe', {
                            username: profile.username,
                        })
                    "
                    method="post"
                    as="button"
                    class="text-decoration-none"
                >
                    <PrimaryButton type="button">Cadastrar-se nesta barbearia</PrimaryButton>
                </Link>
                <p v-else class="small text-warning mb-0">
                    Cadastro pago indisponível porque os pagamentos não estão
                    configurados.
                </p>
            </template>

            <Link
                v-else
                :href="
                    route('barbershop.signup', {
                        username: profile.username,
                    })
                "
                method="post"
                as="button"
                class="text-decoration-none"
            >
                <PrimaryButton type="button">Cadastrar-se nesta barbearia</PrimaryButton>
            </Link>

            <Link
                v-if="!isAuthenticated"
                :href="loginUrl"
                class="link-secondary small"
            >
                Já tem uma conta? Entrar
            </Link>
        </div>
    </div>
</template>
