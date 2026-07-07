<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <MarketingLayout>
        <Head title="SmartBarbeiro - Fidelize seus Clientes!">
            <meta
                head-key="description"
                name="description"
                content="SmartBarbeiro - A fidelização de clientes mais fácil para barbearias! Crie planos de assinatura, compartilhe seu QR code e gerencie clientes e agenda pelo celular."
            />
        </Head>

        <section id="hero1" class="marketing-hero marketing-hero-1">
            <div class="frosted-card">
                <h2>Tenha mais clientes,</h2>
                <h2>fidelidade e recursos.</h2>
                <p>
                    Traga sua clientela ao novo meio de garantir sua cadeira sempre ocupada.
                </p>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="btn btn-light btn-lg marketing-cta-btn"
                >
                    Adquira para sua barbearia
                </Link>
                <Link
                    v-else-if="canLogin"
                    :href="route('login')"
                    class="btn btn-light btn-lg marketing-cta-btn"
                >
                    Adquira para sua barbearia
                </Link>
            </div>
        </section>

        <section
            id="hero2"
            class="marketing-hero marketing-hero-2 overflow-hidden"
        >
            <div class="container-fluid marketing-hero-2__container px-0">
                <div class="row align-items-center g-0 marketing-hero-2__row">
                    <div class="col-lg-6 col-12 marketing-hero-2__media">
                        <img
                            src="/images/cadeira.png"
                            alt="Cadeira de barbearia"
                            class="marketing-hero-2-image img-fluid"
                        >
                    </div>
                    <div class="col-lg-6 col-12 marketing-hero-2__copy">
                        <h2>O futuro da barbearia<br> está aqui.</h2>
                        <p>
                            Descubra como a tecnologia pode transformar,<br>
                            sua barbearia e fidelizar seus clientes.
                        </p>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="btn btn-light btn-lg marketing-cta-btn"
                        >
                            Adquira para sua barbearia
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <section id="hero3" class="marketing-hero marketing-hero-3 container-fluid">
            <div class="container">
                <div class="row">
                    <div
                        id="rubi"
                        class="col-sm-12 col-md-6 text-center"
                    >
                        <img
                            src="/images/celular-mock.png"
                            alt="Aplicativo SmartBarbeiro no celular"
                            class="marketing-hero-3-image"
                        >
                        <h2 class="marketing-hero-3-title">
                            Fidelize seu cliente<br> com poucos cliques
                        </h2>
                        <p>
                            Garanta sua clientela com a <br>fidelização mais rápida do mercado.
                        </p>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="btn btn-light btn-lg marketing-cta-btn"
                        >
                            Adquira para sua barbearia
                        </Link>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-sm-12 col-md-12 marketing-features-container">
                        <div class="marketing-feature-card">
                            <img
                                src="/images/agenda.svg"
                                alt=""
                                width="100"
                                height="100"
                                aria-hidden="true"
                                class="marketing-feature-icon"
                            >
                            <h3>Crie o Contato</h3>
                            <p>
                                Tenha acesso a todas as informações do seu cliente de forma rápida e prática.
                            </p>
                        </div>
                        <div class="marketing-feature-card">
                            <img
                                src="/images/calendarios.svg"
                                alt=""
                                width="100"
                                height="100"
                                aria-hidden="true"
                                class="marketing-feature-icon"
                            >
                            <h3>Mantenha sua cadeira ocupada</h3>
                            <p>
                                Garanta que sua cadeira esteja sempre ocupada com a agenda inteligente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
