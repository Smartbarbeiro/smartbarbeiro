<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="guest-shell">
        <div class="guest-card shadow-lg">
            <div class="text-center mb-4">
                <Link href="/" class="guest-brand text-decoration-none">
                    <ApplicationLogo size="md" />
                </Link>
            </div>
            <slot />
        </div>
    </div>
</template>
