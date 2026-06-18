<script setup>
defineProps({
    day: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <article
        v-if="compact"
        class="barbershop-schedule-card barbershop-schedule-card--today"
    >
        <span class="barbershop-schedule-card__icon" aria-hidden="true">
            <i class="bi bi-person"></i>
        </span>
        <p class="barbershop-schedule-card__today-text mb-0">
            <strong>{{ day.expected_cuts }}</strong>
            {{
                day.expected_cuts === 1
                    ? 'Corte esperado para hoje'
                    : 'Cortes esperados para hoje'
            }}
        </p>
    </article>

    <article
        v-else
        class="barbershop-schedule-card"
    >
        <header
            class="barbershop-schedule-card__header"
        >
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            <span>recorrências</span>
        </header>

        <div class="barbershop-schedule-card__date">
            <span class="barbershop-schedule-card__day">{{
                String(day.day).padStart(2, '0')
            }}</span>
            <span class="barbershop-schedule-card__month">{{
                day.month_label
            }}</span>
        </div>

        <div class="barbershop-schedule-card__metric">
            <span class="barbershop-schedule-card__icon" aria-hidden="true">
                <i class="bi bi-person"></i>
            </span>
            <p class="barbershop-schedule-card__metric-text mb-0">
                <strong>{{ day.expected_cuts }}</strong>
                {{ day.expected_cuts === 1 ? 'corte esperado' : 'cortes esperados' }}
            </p>
        </div>

        <div class="barbershop-schedule-card__metric">
            <span class="barbershop-schedule-card__icon" aria-hidden="true">
                <i class="bi bi-person-badge"></i>
            </span>
            <p class="barbershop-schedule-card__metric-text mb-0">
                <strong>{{ day.expected_beards }}</strong>
                {{
                    day.expected_beards === 1
                        ? 'barba esperada'
                        : 'barbas esperadas'
                }}
            </p>
        </div>

        <footer class="barbershop-schedule-card__total">
            <i class="bi bi-scissors" aria-hidden="true"></i>
            <span>
                {{ day.total_services }}
                {{
                    day.total_services === 1
                        ? 'serviço previsto'
                        : 'serviços previstos'
                }}
            </span>
        </footer>
    </article>
</template>
