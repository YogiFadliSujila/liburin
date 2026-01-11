<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-background-light pt-6 sm:justify-center sm:pt-0 dark:bg-background-dark transition-colors duration-300 font-body"
    >
        <div>
            <Link href="/" class="flex flex-col items-center">
                 <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center text-white shadow-glow mb-2">
                    <span class="material-symbols-rounded text-2xl">travel_explore</span>
                </div>
                <h1 class="font-bold text-2xl text-text-main-light dark:text-text-main-dark font-display tracking-tight">TripMaster</h1>
            </Link>
        </div>

        <div
            class="mt-8 w-full overflow-hidden bg-card-light px-8 py-8 shadow-soft sm:max-w-md sm:rounded-2xl dark:bg-card-dark border border-gray-100 dark:border-gray-800"
        >
            <slot />
        </div>
    </div>
</template>
