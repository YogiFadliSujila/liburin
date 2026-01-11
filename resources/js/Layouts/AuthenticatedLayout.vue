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

const isActive = (routePattern) => {
    return route().current(routePattern);
};

const showMobileSidebar = ref(false);
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
                            <img src="https://img.freepik.com/free-psd/3d-rendering-travel-icon_23-2151695696.jpg?t=st=1768116496~exp=1768120096~hmac=1a85b29300d7ef1c3cee34aec12c9c963445b7c36db14f6f45661aa81205c240&w=1480" class="w-full h-full object-cover rounded-lg">
                        </div>
                        <Link :href="route('dashboard')" class="font-bold text-lg text-text-main-light dark:text-text-main-dark font-display">
                            Liburin
                        </Link>
                    </div>
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

                     <!-- Mobile Profile Icon (Sidebar Trigger) -->
                    <button @click="showMobileSidebar = true" class="md:hidden w-8 h-8 rounded-full bg-gradient-to-tr from-blue-400 to-purple-500 p-0.5 cursor-pointer block">
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
                    </button>
                    
                    <!-- Mobile Sidebar (Slide-over) -->
                    <Teleport to="body">
                        <div>
                            <!-- Backdrop -->
                            <div 
                                v-if="showMobileSidebar"
                                class="fixed inset-0 z-[60] bg-gray-900/50 backdrop-blur-sm transition-opacity"
                                @click="showMobileSidebar = false"
                            ></div>

                            <!-- Sidebar Panel -->
                            <div 
                                class="fixed inset-y-0 right-0 z-[70] w-64 bg-white dark:bg-gray-900 shadow-2xl transform transition-transform duration-300 ease-in-out"
                                :class="showMobileSidebar ? 'translate-x-0' : 'translate-x-full'"
                            >
                                <div class="flex flex-col h-full">
                                    <div class="p-6 border-b border-gray-100 dark:border-gray-800">
                                        <div class="flex items-center justify-between mb-6">
                                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">Menu Profil</span>
                                            <button @click="showMobileSidebar = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                                <span class="material-symbols-rounded">close</span>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-400 to-purple-500 p-0.5">
                                                <div class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                                                    <img 
                                                        v-if="$page.props.auth.user.avatar_url" 
                                                        :src="$page.props.auth.user.avatar_url" 
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <span v-else class="text-xl font-bold text-primary">
                                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $page.props.auth.user.name }}</h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <nav class="flex-1 p-4 space-y-2">
                                        <Link 
                                            :href="route('profile.edit')" 
                                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                                            @click="showMobileSidebar = false"
                                        >
                                            <span class="material-symbols-rounded">person</span>
                                            Edit Profil
                                        </Link>
                                        
                                        <!-- Other potential links could go here -->
                                        <div class="border-t border-gray-100 dark:border-gray-800 my-4"></div>

                                        <Link 
                                            :href="route('logout')" 
                                            method="post" 
                                            as="button"
                                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition"
                                        >
                                            <span class="material-symbols-rounded">logout</span>
                                            Keluar
                                        </Link>
                                    </nav>
                                    
                                    <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                                        <p class="text-xs text-center text-gray-400">
                                            Liburin v1.0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Teleport>
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

        
        <!-- Spacer for Bottom Nav matches height of nav (Mobile Only) -->
    </div>
</template>

<style>
/* Safe area padding for bottom nav on iPhones */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
