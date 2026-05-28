<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Bem-vindo" />

    <div class="guest-shell">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="app-card p-4 p-md-5 text-center">
                        <div class="guest-brand mx-auto mb-3">
                            <ApplicationLogo size="lg" />
                        </div>
                        <h1 class="h3 fw-semibold mb-2">Smart Barbeiro</h1>
                        <p class="text-secondary mb-4">
                            Gerencie o perfil da sua barbearia, assinaturas e
                            cadastros de clientes em um só lugar.
                        </p>

                        <div
                            v-if="canLogin"
                            class="d-flex flex-wrap justify-content-center gap-2 mb-4"
                        >
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="btn btn-primary"
                            >
                                Painel
                            </Link>

                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="btn btn-primary"
                                >
                                    Entrar
                                </Link>

                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="btn btn-outline-secondary"
                                >
                                    Cadastrar
                                </Link>
                            </template>
                        </div>

                        <div class="row g-3 text-start">
                            <div class="col-md-4">
                                <div class="border border-secondary-subtle rounded p-3 h-100">
                                    <i class="bi bi-shop text-primary fs-4"></i>
                                    <h2 class="h6 fw-semibold mt-2 mb-1">
                                        Perfis públicos
                                    </h2>
                                    <p class="small text-secondary mb-0">
                                        Compartilhe a página da sua barbearia e o QR code com os clientes.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-secondary-subtle rounded p-3 h-100">
                                    <i class="bi bi-credit-card text-primary fs-4"></i>
                                    <h2 class="h6 fw-semibold mt-2 mb-1">
                                        Assinaturas
                                    </h2>
                                    <p class="small text-secondary mb-0">
                                        Ofereça acesso pago com cobrança mensal via Mercado Pago.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-secondary-subtle rounded p-3 h-100">
                                    <i class="bi bi-people text-primary fs-4"></i>
                                    <h2 class="h6 fw-semibold mt-2 mb-1">
                                        Cadastros de clientes
                                    </h2>
                                    <p class="small text-secondary mb-0">
                                        Permita que clientes se cadastrem na sua barbearia online.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p class="text-secondary small mt-4 mb-0">
                            Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
