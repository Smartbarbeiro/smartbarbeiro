<script setup>
import { usePwaInstall } from '@/Composables/usePwaInstall';

const {
    canShowInstallUi,
    canPromptInstall,
    canShowIosHint,
    promptInstall,
    dismissInstallUi,
} = usePwaInstall();

const install = async () => {
    await promptInstall();
};
</script>

<template>
    <div
        v-if="canShowInstallUi"
        class="install-app-banner"
        role="region"
        aria-label="Instalar aplicativo"
    >
        <div class="install-app-banner__content">
            <div class="install-app-banner__icon" aria-hidden="true">
                <i class="bi bi-phone"></i>
            </div>

            <div class="install-app-banner__text">
                <p class="install-app-banner__title mb-0">
                    Instale o Tesora
                </p>
                <p class="install-app-banner__subtitle mb-0">
                    <template v-if="canShowIosHint">
                        No Safari, toque em
                        <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
                        Compartilhar e depois em
                        <strong>Adicionar à Tela de Início</strong>.
                    </template>
                    <template v-else>
                        Use como app no celular, com ícone na tela inicial.
                    </template>
                </p>
            </div>
        </div>

        <div class="install-app-banner__actions">
            <button
                v-if="canPromptInstall"
                type="button"
                class="btn btn-dark install-app-banner__install-btn"
                @click="install"
            >
                Instalar app
            </button>

            <button
                type="button"
                class="btn btn-link install-app-banner__dismiss-btn"
                @click="dismissInstallUi"
            >
                Agora não
            </button>
        </div>
    </div>
</template>
