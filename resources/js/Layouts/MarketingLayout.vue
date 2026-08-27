<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useMarketingMobileMenu } from '@/composables/useMarketingMobileMenu';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    activeNav: {
        type: String,
        default: null,
        validator: (value) => [null, 'login', 'register'].includes(value),
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
    showHeaderNav: {
        type: Boolean,
        default: true,
    },
});

const menuId = 'marketingNav';
const page = usePage();

useMarketingMobileMenu(menuId);

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const registerHref = computed(() => route('register'));
const loginHref = computed(() =>
    isAuthenticated.value ? route('dashboard') : route('login'),
);
const loginLabel = computed(() =>
    isAuthenticated.value ? 'Painel' : 'Entrar',
);
const ctaHref = computed(() =>
    isAuthenticated.value ? route('dashboard') : registerHref.value,
);

const sectionHref = (id) => `${route('home')}#${id}`;
</script>

<template>
    <div class="marketing-page">
        <header>
            <nav
                class="navbar navbar-expand-lg navbar-dark marketing-nav"
                :class="{ 'marketing-nav-minimal': !showHeaderNav }"
                aria-label="Navegação principal"
            >
                <div class="container-fluid">
                    <Link
                        class="navbar-brand marketing-brand"
                        :href="route('home')"
                    >
                        <ApplicationLogo size="md" />
                    </Link>

                    <template v-if="showHeaderNav">
                        <button
                            class="navbar-toggler mobile-menu-toggle"
                            type="button"
                            data-bs-toggle="collapse"
                            :data-bs-target="`#${menuId}`"
                            :aria-controls="menuId"
                            aria-expanded="false"
                            aria-label="Abrir menu"
                        >
                            <span class="mobile-menu-toggle-box" aria-hidden="true">
                                <span class="mobile-menu-line"></span>
                                <span class="mobile-menu-line"></span>
                                <span class="mobile-menu-line"></span>
                            </span>
                        </button>

                        <div
                            :id="menuId"
                            class="collapse navbar-collapse mobile-menu-panel"
                        >
                            <ul class="navbar-nav ms-auto mobile-menu-nav">
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        :href="sectionHref('hero1')"
                                    >
                                        Quem Somos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        :href="sectionHref('hero3')"
                                    >
                                        Planos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        :href="sectionHref('contact')"
                                    >
                                        Fale Conosco
                                    </a>
                                </li>
                            </ul>
                            <div class="d-flex gap-2 ms-lg-3 mobile-menu-actions">
                                <Link
                                    :href="ctaHref"
                                    class="btn btn-light btn-sm marketing-nav-btn"
                                    :class="{ active: activeNav === 'register' }"
                                >
                                    Adquira para sua barbearia
                                </Link>
                                <Link
                                    :href="loginHref"
                                    class="btn btn-outline-light btn-sm marketing-nav-btn"
                                    :class="{ active: activeNav === 'login' }"
                                >
                                    {{ loginLabel }}
                                </Link>
                            </div>
                        </div>
                    </template>
                </div>
            </nav>
        </header>

        <main class="marketing-main">
            <slot />
        </main>

        <footer
            v-if="showFooter"
            id="contact"
            class="marketing-footer py-4 mt-1"
        >
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <ApplicationLogo size="md" class="marketing-footer-logo" />
                        <p class="mb-0">
                            <b>CNPJ</b> 18.204.751/0001-95
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <ul class="list-unstyled mb-0">
                            <li>
                                <a :href="sectionHref('hero1')" class="text-decoration-none">
                                    Home
                                </a>
                            </li>
                            <li>
                                <a :href="sectionHref('hero2')" class="text-decoration-none">
                                    Quem Somos
                                </a>
                            </li>
                            <li>
                                <a :href="sectionHref('hero2')" class="text-decoration-none">
                                    Planos
                                </a>
                            </li>
                            <li>
                                <a :href="sectionHref('contact')" class="text-decoration-none">
                                    Fale Conosco
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h5>Contatos</h5>
                        <p class="mb-0">
                            Email: sac@smartbarbearia.com.br<br>
                            Fone: +55 (67) 99800-6035
                        </p>
                    </div>
                </div>
                <hr>
                <div class="text-center text-muted">
                    <p class="mb-0">
                        &copy; {{ new Date().getFullYear() }} Tesora. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
