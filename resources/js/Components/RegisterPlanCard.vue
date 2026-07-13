<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const emit = defineEmits(['start']);

defineProps({
    platformPlan: {
        type: Object,
        default: null,
    },
});

const bullets = [
    'Fidelize clientes em segundos',
    'Garanta renda garantida',
    'Aumente a frequência dos clientes',
    'Mantenha sua cadeira sempre ocupada',
];

const cardsContainer = ref(null);
const cardEl = ref(null);
const overlayEl = ref(null);

let resizeObserver = null;
let hoverMediaQuery = null;
let overlayCard = null;

const supportsHoverMask = () =>
    window.matchMedia('(hover: hover) and (pointer: fine)').matches;

const applyOverlayMask = (event) => {
    const overlay = overlayEl.value;
    const container = cardsContainer.value;

    if (!overlay || !container || !supportsHoverMask()) {
        return;
    }

    const rect = container.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
        hideOverlayMask();
        return;
    }

    overlay.style.setProperty('--opacity', '1');
    overlay.style.setProperty('--x', `${x}px`);
    overlay.style.setProperty('--y', `${y}px`);
};

const hideOverlayMask = () => {
    overlayEl.value?.style.setProperty('--opacity', '0');
};

const createOverlayCta = (overlayCardEl, ctaEl) => {
    const overlayCta = document.createElement('div');
    overlayCta.className = 'cta';
    overlayCta.textContent = ctaEl.textContent;
    overlayCta.setAttribute('aria-hidden', 'true');
    overlayCardEl.append(overlayCta);
};

const bindHoverMask = () => {
    const container = cardsContainer.value;

    if (!container || !supportsHoverMask()) {
        hideOverlayMask();
        return;
    }

    container.addEventListener('pointermove', applyOverlayMask);
    container.addEventListener('pointerleave', hideOverlayMask);
};

const unbindHoverMask = () => {
    cardsContainer.value?.removeEventListener('pointermove', applyOverlayMask);
    cardsContainer.value?.removeEventListener('pointerleave', hideOverlayMask);
    hideOverlayMask();
};

const handleHoverCapabilityChange = () => {
    unbindHoverMask();
    bindHoverMask();
};

onMounted(() => {
    const card = cardEl.value;
    const overlay = overlayEl.value;
    const container = cardsContainer.value;

    if (!card || !overlay || !container) {
        return;
    }

    overlayCard = document.createElement('div');
    overlayCard.className = 'card register-plan-card';
    createOverlayCta(overlayCard, card.querySelector('.card__cta'));
    overlay.append(overlayCard);

    resizeObserver = new ResizeObserver((entries) => {
        entries.forEach((entry) => {
            const width =
                entry.borderBoxSize?.[0]?.inlineSize ?? entry.contentRect.width;
            const height =
                entry.borderBoxSize?.[0]?.blockSize ?? entry.contentRect.height;

            overlayCard.style.width = `${width}px`;
            overlayCard.style.height = `${height}px`;
        });
    });

    resizeObserver.observe(card);

    hoverMediaQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
    hoverMediaQuery.addEventListener('change', handleHoverCapabilityChange);
    bindHoverMask();
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    hoverMediaQuery?.removeEventListener('change', handleHoverCapabilityChange);
    unbindHoverMask();
});
</script>

<template>
    <div ref="cardsContainer" class="register-plan-cards cards">
        <div class="cards__inner">
            <div ref="cardEl" class="card register-plan-card">
                <h2 class="card__heading">Plano Único</h2>

                <p v-if="platformPlan" class="card__price">
                    {{ platformPlan.formatted_price }}
                    <span class="card__price-period">/ mês</span>
                </p>

                <p
                    v-if="platformPlan?.trial_days"
                    class="card__trial text-success mb-3"
                >
                    {{ platformPlan.trial_days }} dias grátis
                </p>

                <ul role="list" class="card__bullets flow">
                    <li v-for="bullet in bullets" :key="bullet">
                        {{ bullet }}
                    </li>
                </ul>

                <button
                    type="button"
                    class="card__cta cta"
                    @click="emit('start')"
                >
                    Receba um QR-code em acrílico grátis
                </button>
            </div>
        </div>

        <div ref="overlayEl" class="overlay cards__inner" aria-hidden="true"></div>
    </div>
</template>
