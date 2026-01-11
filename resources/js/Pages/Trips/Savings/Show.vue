<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    trip: Object,
    savings: Object,
    isOwner: Boolean,
    isAdmin: Boolean,
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
        pending: 'bg-amber-100 text-amber-700',
        success: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
        expired: 'bg-gray-100 text-gray-700',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Menunggu Konfirmasi',
        success: 'Diterima Admin',
        failed: 'Ditolak',
    };
    return labels[status] || status;
};

const approvePayment = () => {
    if (confirm('Konfirmasi pembayaran ini sebagai LUNAS?')) {
        router.post(route('trips.savings.approve', [props.trip.id, props.savings.id]));
    }
};

const cancelPayment = () => {
    if (confirm('Batalkan laporan pembayaran ini?')) {
        router.delete(route('trips.savings.destroy', [props.trip.id, props.savings.id]));
    }
};
</script>

<template>
    <Head title="Detail Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('trips.savings.index', trip.id)"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Detail Pembayaran
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-lg px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg overflow-hidden">
                    <!-- Status Header -->
                    <div 
                        :class="[
                            savings.payment_status === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' :
                            savings.payment_status === 'pending' ? 'bg-gradient-to-r from-amber-500 to-orange-600' :
                            'bg-gradient-to-r from-gray-500 to-gray-600',
                            'p-6 text-white text-center'
                        ]"
                    >
                        <div class="mb-3">
                            <svg v-if="savings.payment_status === 'success'" class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="savings.payment_status === 'pending'" class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold">{{ getStatusLabel(savings.payment_status) }}</h3>
                        <p class="text-3xl font-bold mt-2">{{ formatCurrency(savings.amount) }}</p>
                    </div>

                    <!-- Payment Details -->
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Metode</span>
                            <span class="text-gray-900 dark:text-gray-100 capitalize">Manual / Cash</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Dibuat</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ savings.created_at }}</span>
                        </div>
                        <div v-if="savings.paid_at" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Dibayar/Diterima</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ savings.paid_at }}</span>
                        </div>
                         <div v-if="savings.notes" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Catatan</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ savings.notes }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-6 bg-gray-50 dark:bg-gray-700/50 space-y-3">
                        <!-- Admin Approve Button -->
                        <div v-if="isAdmin && savings.payment_status === 'pending'">
                            <button
                                @click="approvePayment"
                                class="w-full py-3 px-4 bg-green-600 text-white font-semibold rounded-xl shadow hover:bg-green-700 transition flex items-center justify-center gap-2"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Konfirmasi Pembayaran Diterima
                            </button>
                        </div>

                        <!-- User Cancel Button -->
                        <div v-if="isOwner && savings.payment_status === 'pending'">
                            <button
                                @click="cancelPayment"
                                class="w-full py-3 px-4 bg-white border border-red-200 text-red-600 font-semibold rounded-xl hover:bg-red-50 transition"
                            >
                                Batalkan Laporan
                            </button>
                        </div>

                        <Link
                            :href="route('trips.savings.index', trip.id)"
                            class="block w-full text-center py-2 text-indigo-600 dark:text-indigo-400 font-medium hover:underline"
                        >
                            Kembali ke Daftar Tabungan
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
