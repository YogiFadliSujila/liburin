<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
    fullWidth: {
        type: Boolean,
        default: false,
    },
});

const showingNavigationDropdown = ref(false);
const page = usePage();

// Helper to check active route
const isActive = (routePattern) => {
    return route().current(routePattern);
};
</script>

<template>
    <div class="min-h-screen bg-background-light dark:bg-background-dark transition-colors duration-300 antialiased font-body pb-20 md:pb-0">
        <!-- Sticky Header (Desktop & Mobile) -->
        <header class="sticky top-0 z-50 bg-card-light/90 dark:bg-card-dark/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Left: Logo & Desktop Nav -->
                <div class="flex items-center gap-8">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-glow">
                            <span class="material-symbols-rounded text-xl">travel_explore</span>
                        </div>
                        <Link :href="route('dashboard')" class="font-bold text-lg text-text-main-light dark:text-text-main-dark font-display">
                            TripMaster
                        </Link>
                    </div>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden md:flex space-x-6">
                        <NavLink :href="route('dashboard')" :active="isActive('dashboard')">
                            Dashboard
                        </NavLink>
                        <NavLink :href="route('trips.index')" :active="isActive('trips.*')">
                            Trips
                        </NavLink>
                         <NavLink href="#" :active="false">
                            Savings
                        </NavLink>
                    </nav>
                </div>

                <!-- Right: Profile & Notifications -->
                <div class="flex items-center space-x-4">
                    <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <span class="material-symbols-rounded text-text-sub-light dark:text-text-sub-dark">notifications</span>
                    </button>
                    
                    <!-- Desktop Dropdown -->
                    <div class="hidden md:block relative">
                         <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 focus:outline-none">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-400 to-purple-500 p-0.5 cursor-pointer">
                                        <div class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                                            <img 
                                                v-if="$page.props.auth.user.avatar_url" 
                                                :src="$page.props.auth.user.avatar_url" 
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else class="text-xs font-bold text-primary">
                                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-text-main-light dark:text-text-main-dark hidden lg:block">
                                        {{ $page.props.auth.user.name }}
                                    </span>
                                    <span class="material-symbols-rounded text-text-sub-light text-sm">expand_more</span>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button"> Log Out </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                     <!-- Mobile Profile Icon (Direct Link) -->
                    <Link :href="route('profile.edit')" class="md:hidden w-8 h-8 rounded-full bg-gradient-to-tr from-blue-400 to-purple-500 p-0.5 cursor-pointer block">
                         <div class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                             <img 
                                v-if="$page.props.auth.user.avatar_url" 
                                :src="$page.props.auth.user.avatar_url" 
                                class="w-full h-full object-cover"
                             />
                             <span v-else class="text-xs font-bold text-primary">
                                 {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                             </span>
                         </div>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main :class="[fullWidth ? '' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8', 'pt-6']">
            <!-- Header Slot (Page Title usually) -->
            <div v-if="$slots.header" :class="[fullWidth ? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' : '', 'mb-6']">
                <slot name="header" />
            </div>

            <!-- Main Slot -->
            <slot />
        </main>

        <!-- Bottom Navigation (Mobile Only) -->
        <nav class="md:hidden fixed bottom-0 left-0 w-full bg-card-light dark:bg-card-dark border-t border-gray-200 dark:border-gray-800 pb-safe pt-2 z-50">
            <div class="flex justify-around items-center h-16 max-w-md mx-auto">
                <Link :href="route('dashboard')" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                    <span 
                        class="material-symbols-rounded text-2xl transition-colors"
                        :class="isActive('dashboard') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >dashboard</span>
                    <span 
                        class="text-[10px] font-medium transition-colors"
                        :class="isActive('dashboard') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >Dashboard</span>
                </Link>

                <Link :href="route('trips.index')" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                    <span 
                        class="material-symbols-rounded text-2xl transition-colors"
                        :class="isActive('trips.*') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >travel_explore</span>
                    <span 
                        class="text-[10px] font-medium transition-colors"
                        :class="isActive('trips.*') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >Trips</span>
                </Link>

                <Link href="#" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                    <span class="material-symbols-rounded text-2xl text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary transition-colors">account_balance_wallet</span>
                    <span class="text-[10px] font-medium text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary transition-colors">Savings</span>
                </Link>

                <Link :href="route('profile.edit')" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                    <span 
                        class="material-symbols-rounded text-2xl transition-colors"
                        :class="isActive('profile.edit') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >person</span>
                    <span 
                        class="text-[10px] font-medium transition-colors"
                        :class="isActive('profile.edit') ? 'text-primary' : 'text-text-sub-light dark:text-text-sub-dark group-hover:text-primary dark:group-hover:text-primary'"
                    >Profile</span>
                </Link>
            </div>
        </nav>
        
        <!-- Spacer for Bottom Nav matches height of nav (Mobile Only) -->
        <div class="md:hidden h-6 w-full bg-card-light dark:bg-card-dark fixed bottom-0 z-50"></div>
    </div>
</template>

<style>
/* Safe area padding for bottom nav on iPhones */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
