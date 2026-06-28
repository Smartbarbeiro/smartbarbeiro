<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
    size: {
        type: [Number, String],
        default: 32,
    },
});
</script>

<template>
    <svg
        class="dashboard-page-icon"
        :width="size"
        :height="size"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <!-- Painel -->
        <template v-if="name === 'dashboard'">
            <rect x="5" y="5" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5" />
            <rect x="18" y="5" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5" />
            <rect x="5" y="18" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5" />
            <rect x="18" y="18" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5" />
        </template>

        <!-- Clientes / assinaturas -->
        <template v-else-if="name === 'clients'">
            <circle cx="12" cy="11" r="3.5" stroke="currentColor" stroke-width="1.5" />
            <path d="M5 24.5C5.8 20.5 8.5 18.5 12 18.5C15.5 18.5 18.2 20.5 19 24.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <circle cx="21.5" cy="12" r="2.75" stroke="currentColor" stroke-width="1.5" />
            <path d="M17 22.5C17.6 20.2 19.2 19 21.5 19C23.8 19 25.4 20.2 26 22.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'subscriptions'">
            <rect x="6" y="9" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5" />
            <path d="M6 14H26" stroke="currentColor" stroke-width="1.5" />
            <path d="M10 19H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'plan'">
            <rect x="6" y="8" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
            <path d="M6 13H26" stroke="currentColor" stroke-width="1.5" />
            <path d="M10 18H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <!-- Perfil -->
        <template v-else-if="name === 'profile'">
            <circle cx="16" cy="11" r="4.5" stroke="currentColor" stroke-width="1.5" />
            <path d="M7 25.5C8.5 21 11.8 18.5 16 18.5C20.2 18.5 23.5 21 25 25.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'profile-info'">
            <rect x="7" y="6" width="18" height="20" rx="2" stroke="currentColor" stroke-width="1.5" />
            <circle cx="16" cy="13" r="3" stroke="currentColor" stroke-width="1.5" />
            <path d="M11 21H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <!-- Cortes -->
        <template v-else-if="name === 'haircuts'">
            <circle cx="11" cy="11" r="4" stroke="currentColor" stroke-width="1.5" />
            <path d="M15 7L25 5L23 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M9 15L7 25L17 23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </template>

        <template v-else-if="name === 'upload'">
            <rect x="6" y="8" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
            <circle cx="13" cy="14" r="2.5" stroke="currentColor" stroke-width="1.5" />
            <path d="M6 22L12 16L16 19L22 13L26 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </template>

        <template v-else-if="name === 'gallery'">
            <rect x="5" y="7" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5" />
            <rect x="15" y="13" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5" />
        </template>

        <!-- Barbearia -->
        <template v-else-if="name === 'barbershop'">
            <path d="M6 14V26H26V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M10 14V10C10 7.5 12 6 16 6C20 6 22 7.5 22 10V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M16 18V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <!-- Admin -->
        <template v-else-if="name === 'admin'">
            <path d="M16 5L26 10V17C26 22 21.5 25.5 16 27C10.5 25.5 6 22 6 17V10L16 5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M12 16L15 19L21 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </template>

        <template v-else-if="name === 'users'">
            <circle cx="11" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
            <circle cx="21" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
            <path d="M5 24C6.2 21 8.3 19.5 11 19.5C13.7 19.5 15.8 21 17 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M15 24C16.2 21 18.3 19.5 21 19.5C23.7 19.5 25.8 21 27 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'user-edit'">
            <circle cx="13" cy="12" r="3.5" stroke="currentColor" stroke-width="1.5" />
            <path d="M6 24.5C7 21 9.5 19 13 19C14.5 19 15.9 19.4 17 20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M21 21L27 27" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M24 21L27 24L24 27" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </template>

        <!-- QR acrílico -->
        <template v-else-if="name === 'qr-acrylic'">
            <rect x="7" y="7" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" />
            <rect x="17" y="7" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" />
            <rect x="7" y="17" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5" />
            <path d="M19 19H23M23 19V23M19 23H23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <!-- Mensagens -->
        <template v-else-if="name === 'messages'">
            <path d="M6 8H26V20C26 21.1 25.1 22 24 22H12L6 26V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M10 13H22M10 17H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'broadcast'">
            <path d="M8 14V18L12 20V12L8 14Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M12 12C16 12 19 14 19 16C19 18 16 20 12 20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M20 10C23 11.5 25 13.5 25 16C25 18.5 23 20.5 20 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <!-- Plano plataforma (moedas) -->
        <template v-else-if="name === 'platform-plan'">
            <ellipse cx="16" cy="21" rx="9" ry="3" stroke="currentColor" stroke-width="1.5" />
            <path d="M7 21V17C7 15.3 11 14 16 14C21 14 25 15.3 25 17V21" stroke="currentColor" stroke-width="1.5" />
            <ellipse cx="16" cy="17" rx="9" ry="3" stroke="currentColor" stroke-width="1.5" />
            <path d="M7 17V13C7 11.3 11 10 16 10C21 10 25 11.3 25 13V17" stroke="currentColor" stroke-width="1.5" />
        </template>

        <template v-else-if="name === 'service-plans'">
            <path d="M8 10H24L22 24H10L8 10Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M8 14H24" stroke="currentColor" stroke-width="1.5" />
            <path d="M12 18H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'payment'">
            <rect x="5" y="9" width="22" height="14" rx="2" stroke="currentColor" stroke-width="1.5" />
            <path d="M5 14H27" stroke="currentColor" stroke-width="1.5" />
            <path d="M9 19H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'subscribers'">
            <path d="M8 8H24V24H8V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M12 13H20M12 17H20M12 21H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'subscribe'">
            <rect x="7" y="6" width="18" height="20" rx="2" stroke="currentColor" stroke-width="1.5" />
            <path d="M11 12H21M11 16H21M11 20H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'status'">
            <circle cx="16" cy="16" r="10" stroke="currentColor" stroke-width="1.5" />
            <path d="M11 16L14.5 19.5L21 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </template>

        <template v-else-if="name === 'delete-account'">
            <path d="M10 10H22L21 26H11L10 10Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            <path d="M8 10H24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M13 10V8C13 7 14 6 16 6C18 6 19 7 19 8V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'search'">
            <circle cx="14" cy="14" r="6" stroke="currentColor" stroke-width="1.5" />
            <path d="M18.5 18.5L24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>

        <template v-else-if="name === 'empty'">
            <rect x="8" y="10" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5" />
            <path d="M8 14H24" stroke="currentColor" stroke-width="1.5" />
        </template>

        <!-- fallback -->
        <template v-else>
            <circle cx="16" cy="16" r="10" stroke="currentColor" stroke-width="1.5" />
            <path d="M16 11V16L19 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </template>
    </svg>
</template>
