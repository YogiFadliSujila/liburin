<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    trip: Object,
    savings: Array,
    userContributions: Array,
    isAdmin: Boolean,
    clientKey: String,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Menunggu',
        success: 'Berhasil',
        failed: 'Gagal',
        expired: 'Kedaluwarsa',
    };
    return labels[status] || status;
};

const getMethodIcon = (method) => {
    const icons = {
        snap: '💳',
        qris: '📱',
        bank_transfer: '🏦',
        manual: '✋',
    };
    return icons[method] || '💰';
};
</script>

<template>
    <Head :title="`Tabungan - ${trip.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('trips.show', trip.id)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        💰 Tabungan
                    </h2>
                </div>
                <Link
                    :href="route('trips.savings.create', trip.id)"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all hover:from-green-700 hover:to-emerald-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Bayar Tabungan
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Progress Overview -->
                <div class="rounded-2xl bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white shadow-lg">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold mb-1">Progress Tabungan</h3>
                            <p class="text-green-100 text-sm">{{ trip.name }}</p>
                        </div>
                        <div class="flex items-center gap-8">
                            <div>
                                <p class="text-sm text-green-100">Terkumpul</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(trip.total_savings) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-green-100">Target</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(trip.target_amount) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-green-100">Progress</span>
                            <span class="font-medium">{{ trip.savings_progress }}%</span>
                        </div>
                        <div class="h-3 rounded-full bg-white/20 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-white transition-all duration-500"
                                :style="{ width: `${trip.savings_progress}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- User Contributions -->
                    <div class="lg:col-span-1">
                        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Kontribusi Anggota</h3>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="item in userContributions" :key="item.user.id" class="px-5 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                            {{ item.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.user.name }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(item.total) }}</span>
                                </div>
                                <div v-if="userContributions.length === 0" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada kontribusi
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="lg:col-span-2">
                        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Riwayat Pembayaran</h3>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <Link
                                    v-for="saving in savings"
                                    :key="saving.id"
                                    :href="route('trips.savings.show', [trip.id, saving.id])"
                                    class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                >
                                    <div class="flex items-center gap-4">
                                        <span class="text-2xl">{{ getMethodIcon(saving.payment_method) }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ saving.user.name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ saving.created_at }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(saving.amount) }}</span>
                                        <span :class="[getStatusColor(saving.payment_status), 'px-2 py-1 rounded-full text-xs font-medium']">
                                            {{ getStatusLabel(saving.payment_status) }}
                                        </span>
                                    </div>
                                </Link>
                                <div v-if="savings.length === 0" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p>Belum ada pembayaran</p>
                                    <Link
                                        :href="route('trips.savings.create', trip.id)"
                                        class="inline-flex items-center gap-2 mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        Buat pembayaran pertama
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
