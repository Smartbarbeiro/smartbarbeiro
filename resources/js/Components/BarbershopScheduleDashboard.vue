<script setup>
import BarbershopScheduleDayCard from '@/Components/BarbershopScheduleDayCard.vue';
import ProfileAvatar from '@/Components/ProfileAvatar.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    schedule: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="barbershop-dashboard">
        <section class="barbershop-dashboard-hero">
            <ProfileAvatar
                :name="usePage().props.auth.user.name"
                :photo-url="usePage().props.auth.user.profile_photo_url"
                size="lg"
            />

            <div class="barbershop-dashboard-hero__info min-w-0">
                <h2 class="barbershop-dashboard-hero__name mb-0">
                    {{ usePage().props.auth.user.name }}
                </h2>
                <p
                    v-if="usePage().props.auth.user.username"
                    class="barbershop-dashboard-hero__meta mb-0"
                >
                    @{{ usePage().props.auth.user.username }}
                </p>
            </div>
        </section>

        <section class="barbershop-dashboard-intro">
            <div
                class="d-flex flex-wrap align-items-center justify-content-between gap-3"
            >
                <p class="barbershop-dashboard-greeting mb-0">
                    <strong>{{ schedule.greeting }}</strong>
                    {{ schedule.formatted_date }}
                </p>

                <Link
                    :href="route('agenda.index')"
                    class="btn btn-dark btn-sm"
                >
                    <i class="bi bi-journal-bookmark me-2"></i>
                    Abrir agenda
                </Link>
            </div>
        </section>

        <section class="barbershop-dashboard-today">
            <BarbershopScheduleDayCard :day="schedule.today" compact />
        </section>

        <section class="barbershop-dashboard-upcoming">
            <div class="barbershop-dashboard-upcoming__header">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                <span>recorrências</span>
            </div>

            <div class="barbershop-schedule-swiper">
                <BarbershopScheduleDayCard
                    v-for="day in schedule.upcoming_days"
                    :key="day.date"
                    :day="day"
                />
            </div>
        </section>
    </div>
</template>
