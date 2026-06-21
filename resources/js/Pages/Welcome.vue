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
        <Head title="SmartBarbeiro - Fidelize seus Clientes!" />

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
            <div class="container-fluid px-0">
                <div class="row align-items-center g-0">
                    <div class="col-lg-6 col-md-12 text-center ms-lg-auto">
                        <img
                            src="/images/cadeira.svg"
                            alt="Cadeira de barbearia"
                            class="marketing-hero-2-image img-fluid"
                        >
                    </div>
                    <div class="col-lg-3 col-md-12 text-center">
                        <h2>O futuro da barbearia<br> está aqui.</h2>
                        <p>
                            Descubra como a tecnologia pode transformar sua barbearia e fidelizar seus clientes.
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
                            src="/images/celular-mock.svg"
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
                            <svg
                                width="100"
                                height="100"
                                viewBox="0 0 140 140"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <g clip-path="url(#clip-contact)">
                                    <path
                                        d="M116.666 -3.05176e-05H17.4992V17.5H5.83252V29.1666H17.4992V40.8333H5.83252V52.5H17.4992V64.1666H5.83252V75.8333H17.4992V87.5H5.83252V99.1666H17.4992V110.833H5.83252V122.5H17.4992V140H116.666C121.307 140 125.758 138.156 129.04 134.874C132.322 131.592 134.166 127.141 134.166 122.5V17.5C134.166 12.8587 132.322 8.40749 129.04 5.1256C125.758 1.84371 121.307 -3.05176e-05 116.666 -3.05176e-05V-3.05176e-05ZM122.499 122.5C122.499 124.047 121.885 125.531 120.791 126.625C119.697 127.719 118.213 128.333 116.666 128.333H29.1659V11.6666H116.666C118.213 11.6666 119.697 12.2812 120.791 13.3752C121.885 14.4691 122.499 15.9529 122.499 17.5V122.5ZM75.8325 70C79.2937 70 82.6771 68.9736 85.555 67.0507C88.4329 65.1278 90.6759 62.3946 92.0004 59.1969C93.3249 55.9992 93.6715 52.4806 92.9963 49.0859C92.321 45.6912 90.6543 42.573 88.2069 40.1256C85.7595 37.6782 82.6413 36.0115 79.2466 35.3362C75.8519 34.661 72.3333 35.0075 69.1356 36.3321C65.9379 37.6566 63.2047 39.8996 61.2818 42.7775C59.3589 45.6553 58.3325 49.0388 58.3325 52.5C58.3325 57.1413 60.1763 61.5925 63.4581 64.8743C66.74 68.1562 71.1912 70 75.8325 70ZM104.999 93.3333V105H93.3325V93.3333C93.3325 91.7862 92.7179 90.3025 91.624 89.2085C90.53 88.1146 89.0463 87.5 87.4992 87.5H64.1658C62.6188 87.5 61.135 88.1146 60.0411 89.2085C58.9471 90.3025 58.3325 91.7862 58.3325 93.3333V105H46.6659V93.3333C46.6659 88.692 48.5096 84.2408 51.7915 80.9589C55.0734 77.677 59.5246 75.8333 64.1658 75.8333H87.4992C92.1405 75.8333 96.5917 77.677 99.8736 80.9589C103.155 84.2408 104.999 88.692 104.999 93.3333Z"
                                        fill="white"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip-contact">
                                        <rect width="140" height="140" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>
                            <h3>Crie o Contato</h3>
                            <p>
                                Tenha acesso a todas as informações do seu cliente de forma rápida e prática.
                            </p>
                        </div>
                        <div class="marketing-feature-card">
                            <svg
                                width="100"
                                height="100"
                                viewBox="0 0 140 140"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                aria-hidden="true"
                            >
                                <g clip-path="url(#clip-calendar)">
                                    <path
                                        d="M122.5 11.6667H105V0H93.3333V11.6667H46.6667V0H35V11.6667H17.5C12.8587 11.6667 8.40752 13.5104 5.12563 16.7923C1.84374 20.0742 0 24.5254 0 29.1667L0 140H140V29.1667C140 24.5254 138.156 20.0742 134.874 16.7923C131.592 13.5104 127.141 11.6667 122.5 11.6667ZM11.6667 29.1667C11.6667 27.6196 12.2812 26.1358 13.3752 25.0419C14.4692 23.9479 15.9529 23.3333 17.5 23.3333H122.5C124.047 23.3333 125.531 23.9479 126.625 25.0419C127.719 26.1358 128.333 27.6196 128.333 29.1667V46.6667H11.6667V29.1667ZM11.6667 128.333V58.3333H128.333V128.333H11.6667Z"
                                        fill="white"
                                    />
                                    <path d="M99.1667 75.8339H87.5V87.5006H99.1667V75.8339Z" fill="white" />
                                    <path d="M75.8342 75.8339H64.1675V87.5006H75.8342V75.8339Z" fill="white" />
                                    <path d="M52.4992 75.8339H40.8325V87.5006H52.4992V75.8339Z" fill="white" />
                                    <path d="M99.1667 99.1675H87.5V110.834H99.1667V99.1675Z" fill="white" />
                                    <path d="M75.8342 99.1675H64.1675V110.834H75.8342V99.1675Z" fill="white" />
                                    <path d="M52.4992 99.1675H40.8325V110.834H52.4992V99.1675Z" fill="white" />
                                </g>
                                <defs>
                                    <clipPath id="clip-calendar">
                                        <rect width="140" height="140" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>
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
