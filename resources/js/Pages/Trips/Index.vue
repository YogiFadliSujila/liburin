<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    trips: {
        type: Array,
        default: () => [],
    },
    invitations: {
        type: Array,
        default: () => [],
    },
});

const handleAccept = (tripId) => {
    router.post(route('trips.members.accept', tripId), {}, {
        onSuccess: () => {
            // Toast handled by flash message
        },
    });
};

const handleDecline = (tripId) => {
    if (confirm('Apakah Anda yakin ingin menolak undangan ini?')) {
        router.post(route('trips.members.decline', tripId));
    }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getStatusColor = (status) => {
    const colors = {
        planning: 'bg-white/20 text-white',
        saving: 'bg-blue-500 text-white',
        ongoing: 'bg-green-500 text-white',
        completed: 'bg-gray-500 text-white',
        cancelled: 'bg-red-500 text-white',
    };
    return colors[status] || colors.planning;
};

const getStatusLabel = (status) => {
    const labels = {
        planning: 'Planning',
        saving: 'Menabung',
        ongoing: 'Berlangsung',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Trips Saya" />

    <AuthenticatedLayout>
        <!-- User Welcome Section -->
        <div class="mb-8">
            <div class="flex items-baseline justify-between mb-1">
                <h2 class="text-2xl font-bold text-text-main-light dark:text-text-main-dark font-display">
                    Hi, {{ $page.props.auth.user.name.split(' ')[0] }} 👋
                </h2>
                <span class="text-xs font-medium px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-full">
                    Member
                </span>
            </div>
            <p class="text-text-sub-light dark:text-text-sub-dark text-sm">
                Siap untuk petualangan berikutnya?
            </p>
        </div>

        <!-- Pending Invitations -->
        <div v-if="invitations.length > 0" class="mb-10">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                <span>📩</span> Undangan Pending
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="invite in invitations" :key="invite.id" class="flex flex-col sm:flex-row items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <div class="flex items-center gap-4 mb-3 sm:mb-0">
                        <div class="h-12 w-12 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-2xl">
                            📍
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ invite.name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Diundang oleh <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ invite.creator.name }}</span>
                                <span v-if="invite.is_admin" class="ml-2 px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px] font-bold">ADMIN</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button 
                            @click="handleDecline(invite.id)"
                            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 rounded-lg transition"
                        >
                            Tolak
                        </button>
                        <button 
                            @click="handleAccept(invite.id)"
                            class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition"
                        >
                            Terima
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trips Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🌴</span>
                <h3 class="text-xl font-bold text-text-main-light dark:text-text-main-dark font-display">Trips Saya</h3>
            </div>
            <!-- View Toggle (Visual Only for now) -->
            <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                <button class="p-1.5 bg-white dark:bg-gray-700 rounded shadow-sm text-primary">
                    <span class="material-symbols-rounded text-sm">grid_view</span>
                </button>
                <button class="p-1.5 text-text-sub-light dark:text-text-sub-dark hover:bg-white/50 dark:hover:bg-gray-700/50 rounded transition-colors">
                    <span class="material-symbols-rounded text-sm">list</span>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="trips.length === 0" class="text-center py-12 bg-white dark:bg-card-dark rounded-2xl border border-gray-100 dark:border-gray-800 shadow-soft">
            <div class="mb-4">
                <span class="text-4xl">✨</span>
            </div>
            <h3 class="text-lg font-bold text-text-main-light dark:text-text-main-dark mb-2">Belum ada trip</h3>
            <p class="text-text-sub-light dark:text-text-sub-dark text-sm mb-6 px-6">
                Yuk mulai rencanakan liburan seru bareng teman-temanmu sekarang!
            </p>
            <Link 
                :href="route('trips.create')"
                class="inline-flex items-center bg-primary hover:bg-primary-light text-white font-medium py-2.5 px-6 rounded-xl transition-all shadow-glow"
            >
                Buat Trip Pertama
            </Link>
        </div>

        <!-- Trips List -->
        <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 pb-20">
            <Link 
                v-for="trip in trips" 
                :key="trip.id" 
                :href="route('trips.show', trip.id)"
                class="block h-full"
            >
                <article class="group bg-card-light dark:bg-card-dark rounded-2xl shadow-soft dark:shadow-none border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-lg transition-all duration-300 h-full flex flex-col">
                    <!-- Cover Image Section -->
                    <div class="relative h-48 w-full overflow-hidden shrink-0">
                        <div class="absolute inset-0 group-hover:scale-105 transition-transform duration-500"></div>
                        <img 
                            v-if="trip.cover_image"
                            :src="`/storage/${trip.cover_image}`" 
                            :alt="trip.name" 
                            class="absolute inset-0 w-full h-full object-cover  opacity-100 transition-transform duration-500 group-hover:scale-105" 
                        />
                        <img 
                            v-else
                            src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Default Cover"
                            class="absolute inset-0 w-full h-full object-cover opacity-50 transition-transform duration-500 group-hover:scale-105"
                        />
                        
                        <!-- Badges -->
                        <div class="absolute top-3 left-3" v-if="trip.is_admin">
                            <span class="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-md shadow-sm">Admin</span>
                        </div>
                        <div class="absolute top-3 right-3">
                            <span 
                                :class="[
                                    getStatusColor(trip.status),
                                    'backdrop-blur-md text-xs font-medium px-2 py-1 rounded-full border border-white/30 shadow-sm'
                                ]"
                            >
                                {{ getStatusLabel(trip.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-text-main-light dark:text-text-main-dark mb-1 group-hover:text-primary transition-colors line-clamp-1">
                            {{ trip.name }}
                        </h3>
                        
                        <div class="flex items-center text-text-sub-light dark:text-text-sub-dark text-xs mb-5">
                            <span class="material-symbols-rounded text-sm mr-1">location_on</span>
                            <span class="line-clamp-1">{{ trip.destination }}</span>
                        </div>

                        <!-- Savings Progress -->
                        <div class="mb-6 mt-auto">
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-xs font-medium text-text-sub-light dark:text-text-sub-dark">Progress Tabungan</span>
                                <span class="text-sm font-bold text-primary">{{ Math.round(trip.savings_progress) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="bg-primary h-2 rounded-full transition-all duration-500" :style="{ width: `${trip.savings_progress}%` }"></div>
                            </div>
                            <div class="flex justify-between mt-2 text-xs">
                                <span class="text-text-sub-light dark:text-text-sub-dark">{{ formatCurrency(trip.total_savings) }}</span>
                                <span class="font-medium text-text-main-light dark:text-text-main-dark">{{ formatCurrency(trip.target_amount) }}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center text-text-sub-light dark:text-text-sub-dark text-xs">
                                <span class="material-symbols-rounded text-sm mr-1.5">calendar_month</span>
                                {{ formatDate(trip.start_date) }}
                            </div>
                            <div class="flex items-center text-text-sub-light dark:text-text-sub-dark text-xs">
                                <span class="material-symbols-rounded text-sm mr-1.5">group</span>
                                {{ trip.members_count }} anggota
                            </div>
                        </div>
                    </div>
                </article>
            </Link>
        </div>

        <!-- Floating Action Button -->
        <div class="fixed bottom-24 right-4 z-40">
            <Link 
                :href="route('trips.create')"
                class="bg-primary hover:bg-primary-light text-white rounded-full p-4 shadow-glow flex items-center justify-center transition-all transform hover:scale-105 active:scale-95 group"
            >
                <span class="material-symbols-rounded text-2xl mr-2">add</span>
                <span class="font-semibold text-sm pr-1">Buat Trip Baru</span>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
