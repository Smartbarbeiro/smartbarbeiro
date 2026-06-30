<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="barbershop-empty-dashboard">
        <img
            src="/images/barbearia-empty-state.png"
            alt="Ilustração de uma barbearia"
            class="barbershop-empty-dashboard__icon"
            width="560"
            height="420"
        />

        <p class="barbershop-empty-dashboard__message mb-0">
            Sua barbearia ainda não tem assinantes...
        </p>

        <Link
            :href="`${route('profile.edit')}#planos-de-servico`"
            class="btn btn-dark barbershop-empty-dashboard__action-btn"
        >
            <i class="bi bi-scissors me-2" aria-hidden="true"></i>
            Crie seus planos
        </Link>
    </section>
</template>
