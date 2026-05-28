<script setup>
import SidebarNavLink from '@/Components/SidebarNavLink.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navItems = computed(() => {
    const items = [
        {
            href: route('dashboard'),
            active: route().current('dashboard'),
            icon: 'speedometer2',
            label: 'Painel',
        },
        {
            href: route('subscriptions.index'),
            active: route().current('subscriptions.index'),
            icon: 'credit-card',
            label: user.value?.is_barbershop ? 'Clientes' : 'Assinaturas',
        },
        {
            href: route('profile.edit'),
            active: route().current('profile.edit'),
            icon: 'person-gear',
            label: 'Perfil',
        },
    ];

    if (user.value?.is_barbershop && user.value?.username) {
        items.splice(1, 0, {
            href: route('profile.public', { username: user.value.username }),
            active: route().current('profile.public'),
            icon: 'shop',
            label: 'Barbearia',
        });
    }

    if (user.value?.is_administrator) {
        items.splice(user.value?.is_barbershop && user.value?.username ? 2 : 1, 0, {
            href: route('admin.users.index'),
            active: route().current('admin.*'),
            icon: 'shield-lock',
            label: 'Admin',
        });
    }

    return items;
});
</script>

<template>
    <div class="app-shell">
        <aside class="icon-sidebar d-none d-lg-flex">
            <nav class="sidebar-nav sidebar-nav-top">
                <SidebarNavLink
                    v-for="item in navItems"
                    :key="item.href"
                    v-bind="item"
                />
            </nav>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="d-flex align-items-center w-100 gap-3 gap-lg-4">
                    <Link
                        :href="route('dashboard')"
                        class="app-topbar-logo shrink-0"
                        title="Smart Barbeiro"
                    >
                        <ApplicationLogo size="md" />
                    </Link>

                    <div class="app-topbar-heading d-flex align-items-center gap-3 min-w-0">
                        <div class="dropdown shrink-0">
                            <button
                                class="app-topbar-avatar-btn border-0 bg-transparent p-0"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                :aria-label="`Conta de ${user?.name ?? 'usuário'}`"
                            >
                                <ProfileAvatar
                                    :name="user?.name ?? 'Usuário'"
                                    :photo-url="user?.profile_photo_url"
                                    size="md"
                                />
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li class="px-3 py-2 border-bottom border-secondary-subtle">
                                    <div class="fw-semibold">{{ user?.name }}</div>
                                    <div class="small text-secondary">{{ user?.email }}</div>
                                </li>
                                <li>
                                    <Link :href="route('profile.edit')" class="dropdown-item">
                                        <i class="bi bi-person me-2"></i>Perfil
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('subscriptions.index')" class="dropdown-item">
                                        <i class="bi bi-credit-card me-2"></i>{{
                                            user?.is_barbershop
                                                ? 'Clientes'
                                                : 'Minhas assinaturas'
                                        }}
                                    </Link>
                                </li>
                                <li><hr class="dropdown-divider" /></li>
                                <li>
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="dropdown-item"
                                    >
                                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div v-if="$slots.header" class="app-topbar-title min-w-0">
                            <slot name="header" />
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-content">
                <slot />
            </main>
        </div>

        <nav class="mobile-bottom-nav d-lg-none" aria-label="Menu principal">
            <SidebarNavLink
                v-for="item in navItems"
                :key="`mobile-${item.href}`"
                v-bind="item"
                variant="bottom"
            />

            <div class="dropdown dropup mobile-bottom-account">
                <button
                    class="mobile-bottom-link border-0 bg-transparent"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Menu da conta"
                >
                    <ProfileAvatar
                        :name="user?.name ?? 'Usuário'"
                        :photo-url="user?.profile_photo_url"
                        size="sm"
                    />
                    <span class="mobile-bottom-label">Conta</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark mb-2">
                    <li class="px-3 py-2 border-bottom border-secondary-subtle">
                        <div class="fw-semibold">{{ user?.name }}</div>
                        <div class="small text-secondary">{{ user?.email }}</div>
                    </li>
                    <li>
                        <Link :href="route('logout')" method="post" as="button" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</template>
