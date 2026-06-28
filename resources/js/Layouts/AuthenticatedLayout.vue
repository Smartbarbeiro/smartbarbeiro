<script setup>
import SidebarNavLink from '@/Components/SidebarNavLink.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import PlatformMessagePopups from '@/Components/PlatformMessagePopups.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const isClient = computed(
    () =>
        user.value &&
        !user.value.is_barbershop &&
        !user.value.is_administrator &&
        user.value.primary_barbershop_username,
);

const clientBarbershopProfileUrl = computed(() => {
    if (!user.value?.primary_barbershop_username) {
        return null;
    }

    return route('profile.public', {
        username: user.value.primary_barbershop_username,
    });
});

const homeHref = computed(() => {
    if (user.value?.is_barbershop && user.value?.username) {
        return route('profile.public', { username: user.value.username });
    }

    if (user.value?.primary_barbershop_username) {
        return route('profile.public', {
            username: user.value.primary_barbershop_username,
        });
    }

    return route('dashboard');
});

const isHomeActive = computed(() => {
    if (user.value?.is_barbershop && user.value?.username) {
        return route().current('profile.public');
    }

    if (user.value?.primary_barbershop_username) {
        return route().current('profile.public', {
            username: user.value.primary_barbershop_username,
        });
    }

    return route().current('dashboard');
});

const clientNavItems = computed(() => {
    if (!isClient.value || !clientBarbershopProfileUrl.value) {
        return null;
    }

    const username = user.value.primary_barbershop_username;

    return [
        {
            href: clientBarbershopProfileUrl.value,
            active: route().current('profile.public', { username }),
            icon: 'house',
            label: 'Home',
        },
        {
            href: route('subscriptions.index'),
            active: route().current('subscriptions.index'),
            icon: 'credit-card',
            label: 'Plano',
        },
        {
            href: route('haircuts.index'),
            active: route().current('haircuts.*'),
            icon: 'scissors',
            label: 'Cortes',
        },
        {
            href: route('profile.edit'),
            active: route().current('profile.edit'),
            icon: 'gear',
            label: 'Config',
        },
    ];
});

const navItems = computed(() => {
    if (clientNavItems.value) {
        return clientNavItems.value;
    }

    const items = [
        {
            href: homeHref.value,
            active: isHomeActive.value,
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
            label: 'Sua Barbearia',
        });
    }

    if (user.value?.is_administrator) {
        const insertAt = user.value?.is_barbershop && user.value?.username ? 2 : 1;
        items.splice(insertAt, 0, {
            href: route('admin.users.index'),
            active: route().current('admin.users.*'),
            icon: 'shield-lock',
            label: 'Admin',
        });
        items.splice(insertAt + 1, 0, {
            href: route('admin.acrylic-qr-orders.index'),
            active: route().current('admin.acrylic-qr-orders.*'),
            icon: 'badge-ad',
            label: 'QR acrílico',
        });
        items.splice(insertAt + 2, 0, {
            href: route('admin.messages.index'),
            active: route().current('admin.messages.*'),
            icon: 'megaphone',
            label: 'Mensagens',
        });
        items.splice(insertAt + 3, 0, {
            href: route('admin.platform-plan.edit'),
            active: route().current('admin.platform-plan.*'),
            icon: 'cash-stack',
            label: 'Plano',
        });
    }

    return items;
});

const mobileNavItems = computed(() => {
    if (clientNavItems.value) {
        return clientNavItems.value.map((item) => ({
            ...item,
            label: item.label.toUpperCase(),
        }));
    }

    const currentUser = user.value;
    let barbeariasHref = homeHref.value;
    let barbeariasActive = false;

    if (currentUser?.is_barbershop && currentUser?.username) {
        barbeariasHref = route('profile.public', { username: currentUser.username });
        barbeariasActive = route().current('profile.public');
    } else if (currentUser?.primary_barbershop_username) {
        barbeariasHref = route('profile.public', {
            username: currentUser.primary_barbershop_username,
        });
        barbeariasActive = route().current('profile.public', {
            username: currentUser.primary_barbershop_username,
        });
    } else if (currentUser?.is_administrator) {
        barbeariasHref = route('admin.users.index');
        barbeariasActive = route().current('admin.users.*');
    }

    return [
        {
            href: homeHref.value,
            active: isHomeActive.value,
            icon: 'calendar3',
            label: 'Home',
        },
        {
            href: route('subscriptions.index'),
            active: route().current('subscriptions.index'),
            icon: 'journal-bookmark',
            label: 'Agenda',
        },
        {
            href: barbeariasHref,
            active: barbeariasActive,
            icon: 'scissors',
            label: 'Barbearias',
        },
        {
            href: route('profile.edit'),
            active: route().current('profile.edit'),
            icon: 'gear',
            label: 'Config.',
        },
    ];
});
</script>

<template>
    <div class="app-shell">
        <aside class="icon-sidebar d-none d-lg-flex">
            <Link
                :href="homeHref"
                class="sidebar-logo"
                title="Smart Barbeiro"
            >
                <ApplicationLogo size="md" />
            </Link>

            <nav class="sidebar-nav sidebar-nav-top" aria-label="Menu principal">
                <p class="sidebar-section-title">
                    <span>Menu</span>
                    <i
                        class="bi bi-chevron-right sidebar-section-chevron"
                        aria-hidden="true"
                    ></i>
                </p>

                <SidebarNavLink
                    v-for="item in navItems"
                    :key="item.href"
                    v-bind="item"
                />
            </nav>

            <div
                v-if="user?.is_barbershop"
                class="sidebar-footer"
            >
                <Link
                    :href="route('profile.edit')"
                    class="sidebar-profile"
                >
                    <ProfileAvatar
                        :name="user.name"
                        :photo-url="user.profile_photo_url"
                        size="md"
                    />
                    <span class="sidebar-profile__info min-w-0">
                        <span class="sidebar-profile__label">Barbearia:</span>
                        <span class="sidebar-profile__name">{{ user.name }}</span>
                    </span>
                </Link>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="d-flex align-items-center w-100 gap-3 gap-lg-4">
                    <Link
                        :href="homeHref"
                        class="app-topbar-logo shrink-0 d-lg-none"
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

                        <div
                            v-if="$slots.header"
                            class="app-topbar-title min-w-0"
                        >
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
                v-for="item in mobileNavItems"
                :key="`mobile-${item.href}`"
                v-bind="item"
                variant="bottom"
            />
        </nav>

        <PlatformMessagePopups />
    </div>
</template>
