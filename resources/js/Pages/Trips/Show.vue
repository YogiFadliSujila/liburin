<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    trip: Object,
    members: Array,
    destinations: Array,
    userRole: String,
    isAdmin: Boolean,
});

const activeTab = ref('overview');
const showDestinationModal = ref(false);

const destinationForm = useForm({
    name: '',
    category: 'attraction',
    description: '',
    location: '',
    visit_date: '',
    start_time: '',
    end_time: '',
    estimated_cost: '',
});

const categories = {
    attraction: '🏛️ Atraksi',
    food: '🍽️ Kuliner',
    transport: '🚗 Transportasi',
    accommodation: '🏨 Penginapan',
    other: '📍 Lainnya',
};

const submitDestination = () => {
    destinationForm.post(route('trips.destinations.store', props.trip.id), {
        onSuccess: () => {
            showDestinationModal.value = false;
            destinationForm.reset();
        },
    });
};

const tabs = [
    { id: 'overview', label: 'Overview', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { id: 'itinerary', label: 'Itinerary', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { id: 'finance', label: 'Keuangan', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'members', label: 'Anggota', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
];

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatShortDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
};

const daysUntilTrip = computed(() => {
    const startDate = new Date(props.trip.start_date);
    const today = new Date();
    const diffTime = startDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
});

const tripDuration = computed(() => {
    const start = new Date(props.trip.start_date);
    const end = new Date(props.trip.end_date);
    const diffTime = end - start;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const getStatusColor = (status) => {
    const colors = {
        planning: 'bg-blue-500',
        saving: 'bg-amber-500',
        ongoing: 'bg-green-500',
        completed: 'bg-gray-500',
    };
    return colors[status] || colors.planning;
};

const getCategoryIcon = (category) => {
    const icons = {
        attraction: '🏛️',
        food: '🍽️',
        transport: '🚗',
        accommodation: '🏨',
        other: '📍',
    };
    return icons[category] || icons.other;
};

const groupedDestinations = computed(() => {
    const groups = {};
    props.destinations.forEach(dest => {
        if (!groups[dest.visit_date]) {
            groups[dest.visit_date] = [];
        }
        groups[dest.visit_date].push(dest);
    });
    return groups;
});

const totalEstimatedCost = computed(() => {
    return props.destinations.reduce((sum, dest) => sum + (dest.estimated_cost || 0), 0);
});

const promoteMember = (member) => {
    if (confirm(`Apakah Anda yakin ingin menjadikan ${member.name} sebagai Admin?`)) {
        router.patch(route('trips.members.update', [props.trip.id, member.trip_member_id]), {
            role: 'admin'
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional toast logic here
            }
        });
    }
};
</script>

<template>
    <Head :title="trip.name" />

    <AuthenticatedLayout :full-width="true">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('trips.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            {{ trip.name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ trip.destination }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="isAdmin"
                        :href="route('trips.edit', trip.id)"
                        class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </Link>
                </div>
            </div>
        </template>
        
        <div class="min-h-screen relative">

                <!-- Parallax Hero Image -->
                <div class="fixed inset-x-0 top-16 z-0 h-[30vh] md:h-[40vh]">
                    <div class="absolute inset-0 bg-gradient-to-t from-background-light/80 to-transparent z-10 dark:from-background-dark/80"></div>
                     <img 
                        v-if="trip.cover_image"
                        :src="`/storage/${trip.cover_image}`" 
                        :alt="trip.name" 
                        class="w-full h-full object-cover"
                    />
                     <img 
                        v-else
                        src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80"
                        alt="Default Cover"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Spacer for Parallax -->
                <div class="h-[25vh] md:h-[35vh]"></div>

                <!-- Main Content Sheet -->
                <div class="relative z-10 bg-background-light dark:bg-background-dark rounded-t-3xl shadow-top min-h-screen -mt-6">
                    <!-- Tabs (Sticky) -->
                    <div class="sticky top-16 z-20 bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-md rounded-t-3xl border-b border-gray-100 dark:border-gray-800">
                        <nav class="flex gap-2 overflow-x-auto px-4 py-3 no-scrollbar" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'bg-primary text-white shadow-glow'
                                        : 'bg-white dark:bg-gray-800 text-text-sub-light dark:text-text-sub-dark hover:bg-gray-50 dark:hover:bg-gray-700',
                                    'flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-semibold transition-all duration-300'
                                ]"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                                </svg>
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <div class="px-0 sm:px-0 lg:px-0 py-6">
                        <!-- Moved Hero Info (Now 'Header Info') -->
                        <div class="mb-8 px-4 sm:px-6 lg:px-8">
                             <div class="flex items-start justify-between gap-4 mb-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span :class="[getStatusColor(trip.status), 'px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider text-white']">
                                            {{ trip.status }}
                                        </span>
                                        <span v-if="isAdmin" class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                            Admin
                                        </span>
                                    </div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-text-main-light dark:text-text-main-dark font-display leading-tight mb-2">{{ trip.name }}</h1>
                                    <p class="text-sm text-text-sub-light dark:text-text-sub-dark flex items-center gap-1">
                                        <span class="material-symbols-rounded text-lg">calendar_month</span>
                                        {{ formatDate(trip.start_date) }} - {{ formatDate(trip.end_date) }}
                                    </p>
                                </div>
                             </div>

                             <!-- Linear Progress Bar -->
                            <div class="bg-card-light dark:bg-card-dark p-5 rounded-2xl shadow-soft dark:border dark:border-gray-800">
                                <div class="flex justify-between items-end mb-2">
                                    <div>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark font-medium mb-1">Total Tabungan</p>
                                        <p class="text-lg font-bold text-primary">{{ formatCurrency(trip.total_savings) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-text-sub-light dark:text-text-sub-dark">Target: {{ formatCurrency(trip.target_amount) }}</span>
                                        <p class="text-sm font-bold text-primary">{{ Math.round(trip.savings_progress) }}%</p>
                                    </div>
                                </div>
                                <div class="relative w-full h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div 
                                        class="absolute top-0 left-0 h-full bg-gradient-to-r from-primary to-purple-400 rounded-full transition-all duration-1000 ease-out"
                                        :style="{ width: `${trip.savings_progress}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>



                <!-- Tab Content -->
                <div v-show="activeTab === 'overview'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Terkumpul</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.total_savings) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Target</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.target_amount) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Pengeluaran</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.total_expenses) }}</p>
                        </div>

                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Saldo</span>
                            </div>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(trip.remaining_balance) }}</p>
                        </div>
                    </div>

                    <!-- New Overview Widgets: Itinerary Preview & Members -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Itinerary Preview -->
                        <div class="bg-white dark:bg-card-dark rounded-2xl p-5 shadow-soft border border-gray-100 dark:border-gray-800">
                             <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-text-main-light dark:text-text-main-dark">Rundown Singkat</h3>
                                <button @click="activeTab = 'itinerary'" class="text-xs text-primary font-medium hover:underline">Lihat Semua</button>
                            </div>
                            <div v-if="destinations.length > 0" class="space-y-4">
                                <div v-for="dest in destinations.slice(0, 3)" :key="dest.id" class="flex gap-3 items-start">
                                    <div class="mt-0.5 p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                                        <span class="text-lg">{{ getCategoryIcon(dest.category) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-text-main-light dark:text-text-main-dark line-clamp-1">{{ dest.name }}</p>
                                        <p class="text-xs text-text-sub-light dark:text-text-sub-dark">{{ formatDate(dest.visit_date) }}</p>
                                    </div>
                                </div>
                            </div>
                             <div v-else class="text-center py-6">
                                <p class="text-sm text-text-sub-light dark:text-text-sub-dark italic">Belum ada destinasi</p>
                            </div>
                        </div>

                        <!-- Members Preview -->
                        <div class="bg-white dark:bg-card-dark rounded-2xl p-5 shadow-soft border border-gray-100 dark:border-gray-800">
                             <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-text-main-light dark:text-text-main-dark">Anggota Trip</h3>
                                <button @click="activeTab = 'members'" class="text-xs text-primary font-medium hover:underline">Kelola</button>
                            </div>
                             <div class="flex items-center ml-2">
                                <div class="flex -space-x-3">
                                    <div v-for="member in members.slice(0, 5)" :key="member.id" class="w-10 h-10 rounded-full border-2 border-white dark:border-card-dark bg-gray-200 flex items-center justify-center overflow-hidden" :title="member.name">
                                         <img 
                                            v-if="member.avatar_url" 
                                            :src="member.avatar_url" 
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-xs font-bold text-gray-600">{{ member.name.charAt(0) }}</span>
                                    </div>
                                    <div v-if="members.length > 5" class="w-10 h-10 rounded-full border-2 border-white dark:border-card-dark bg-primary flex items-center justify-center text-white text-xs font-bold z-10">
                                        +{{ members.length - 5 }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex gap-4 text-sm text-text-sub-light dark:text-text-sub-dark">
                                <div>
                                    <span class="block font-bold text-text-main-light dark:text-text-main-dark">{{ members.length }}</span>
                                    <span>Total</span>
                                </div>
                                <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                                    <span class="block font-bold text-green-600">{{ members.filter(m => m.roll === 'admin').length || 1 }}</span>
                                    <span>Admin</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="trip.description" class="rounded-xl bg-white dark:bg-card-dark p-6 shadow-soft border border-gray-100 dark:border-gray-800">
                        <h3 class="text-lg font-bold text-text-main-light dark:text-text-main-dark mb-3">Tentang Trip Ini</h3>
                        <p class="text-text-sub-light dark:text-text-sub-dark leading-relaxed">{{ trip.description }}</p>
                    </div>
                </div>

                <!-- Itinerary Tab -->
                <div v-show="activeTab === 'itinerary'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rundown Perjalanan</h3>
                        <button 
                            v-if="isAdmin" 
                            @click="showDestinationModal = true"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Destinasi
                        </button>
                    </div>

                    <div v-if="destinations.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Belum ada itinerary</h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Mulai susun rencana perjalanan dengan menambah destinasi.</p>
                    </div>

                    <!-- Grouped by Date -->
                    <div v-else class="space-y-6">
                        <div v-for="(dests, date) in groupedDestinations" :key="date" class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ formatDate(date) }}</h4>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="dest in dests" :key="dest.id" class="p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <span class="text-2xl">{{ getCategoryIcon(dest.category) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ dest.name }}</h5>
                                        <p v-if="dest.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ dest.description }}</p>
                                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="dest.start_time" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ dest.start_time }} - {{ dest.end_time || '?' }}
                                            </span>
                                            <span v-if="dest.location" class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                                {{ dest.location }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="dest.estimated_cost" class="text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(dest.estimated_cost) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimated -->
                    <div v-if="destinations.length > 0" class="rounded-xl bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 p-6 border border-indigo-100 dark:border-indigo-800">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Estimasi Biaya</span>
                            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalEstimatedCost) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Finance Tab -->
                <div v-show="activeTab === 'finance'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Terkumpul</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(trip.total_savings) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Target</p>
                            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(trip.target_amount) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pengeluaran</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(trip.total_expenses) }}</p>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Saldo</p>
                            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(trip.remaining_balance) }}</p>
                        </div>
                    </div>

                    <!-- Finance Actions -->
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Link 
                            :href="route('trips.savings.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white hover:from-green-600 hover:to-emerald-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">💰 Tabungan</h4>
                                    <p class="text-sm text-green-100">Lihat & bayar tabungan</p>
                                </div>
                            </div>
                        </Link>

                        <Link 
                            :href="route('trips.expenses.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-red-500 to-pink-600 p-6 text-white hover:from-red-600 hover:to-pink-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">💸 Pengeluaran</h4>
                                    <p class="text-sm text-red-100">Catat pengeluaran trip</p>
                                </div>
                            </div>
                        </Link>

                        <Link 
                            :href="route('trips.withdrawals.index', trip.id)"
                            class="rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 p-6 text-white hover:from-purple-600 hover:to-indigo-700 transition shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white/20 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-lg">🗳️ Penarikan</h4>
                                    <p class="text-sm text-purple-100">Request & voting</p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Progress Bar -->
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-300">Progress Tabungan</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ Math.round(trip.savings_progress) }}%</span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500"
                                :style="{ width: `${trip.savings_progress}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Members Tab -->
                <div v-show="activeTab === 'members'" class="space-y-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">👥 Anggota Trip</h3>
                        <button v-if="isAdmin" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Undang Anggota
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="member in members" :key="member.id" class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg">
                                    {{ member.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ member.name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span 
                                        :class="[
                                            member.role === 'admin' 
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' 
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                            'rounded-full px-2 py-1 text-xs font-medium'
                                        ]"
                                    >
                                        {{ member.role === 'admin' ? 'Admin' : 'Member' }}
                                    </span>
                                    
                                    <button 
                                        v-if="isAdmin && member.role !== 'admin'"
                                        @click="promoteMember(member)"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition"
                                    >
                                        Jadikan Admin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Close Main Padding Div -->
            </div> <!-- Close Content Sheet Div -->

        <!-- Destination Modal -->
        <Teleport to="body">
            <div v-if="showDestinationModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDestinationModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <form @submit.prevent="submitDestination">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tambah Destinasi</h3>
                            </div>
                            <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
                                <div>
                                    <InputLabel for="dest_name" value="Nama Destinasi" />
                                    <TextInput id="dest_name" v-model="destinationForm.name" type="text" class="mt-1 block w-full" placeholder="contoh: Candi Borobudur" required />
                                    <InputError :message="destinationForm.errors.name" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="dest_category" value="Kategori" />
                                    <select id="dest_category" v-model="destinationForm.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                    <InputError :message="destinationForm.errors.category" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="dest_location" value="Lokasi (Opsional)" />
                                    <TextInput id="dest_location" v-model="destinationForm.location" type="text" class="mt-1 block w-full" />
                                    <InputError :message="destinationForm.errors.location" class="mt-2" />
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-1">
                                        <InputLabel for="dest_date" value="Tanggal" />
                                        <TextInput 
                                            id="dest_date" 
                                            v-model="destinationForm.visit_date" 
                                            type="date" 
                                            class="mt-1 block w-full" 
                                            :min="trip.start_date" 
                                            :max="trip.end_date" 
                                            required 
                                        />
                                        <InputError :message="destinationForm.errors.visit_date" class="mt-2 text-xs" />
                                    </div>
                                    <div>
                                        <InputLabel for="dest_start" value="Mulai" />
                                        <TextInput id="dest_start" v-model="destinationForm.start_time" type="time" class="mt-1 block w-full" />
                                    </div>
                                    <div>
                                        <InputLabel for="dest_end" value="Selesai" />
                                        <TextInput id="dest_end" v-model="destinationForm.end_time" type="time" class="mt-1 block w-full" />
                                    </div>
                                </div>
                                <InputError :message="destinationForm.errors.start_time" class="mt-1" />
                                <InputError :message="destinationForm.errors.end_time" class="mt-1" />

                                <div>
                                    <InputLabel for="dest_cost" value="Estimasi Biaya (Opsional)" />
                                    <TextInput id="dest_cost" v-model="destinationForm.estimated_cost" type="number" class="mt-1 block w-full" min="0" />
                                    <InputError :message="destinationForm.errors.estimated_cost" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="dest_desc" value="Deskripsi (Opsional)" />
                                    <textarea id="dest_desc" v-model="destinationForm.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                    <InputError :message="destinationForm.errors.description" class="mt-2" />
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="showDestinationModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</button>
                                <PrimaryButton :disabled="destinationForm.processing">{{ destinationForm.processing ? 'Menyimpan...' : 'Simpan' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
        </div>
    </AuthenticatedLayout>
</template>
