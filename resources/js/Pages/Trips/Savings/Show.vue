<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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
    showApproveModal.value = true;
};

const cancelPayment = () => {
    if (confirm('Batalkan laporan pembayaran ini?')) {
        router.delete(route('trips.savings.destroy', [props.trip.id, props.savings.id]));
    }
};

const showWaModal = ref(false);
const showApproveModal = ref(false);
const adminPhone = ref(''); // Optional: if we want to target specific admin
const waMessage = ref(`Halo Admin, saya ${props.savings.user.name} sudah melakukan pembayaran sebesar ${formatCurrency(props.savings.amount)} untuk trip "${props.trip.name}". Mohon dicek dan divalidasi. Terima kasih!`);

const sendToWa = () => {
    // Basic sanitization: remove non-numeric chars
    let phone = adminPhone.value.replace(/\D/g, '');
    
    // If phone starts with '0', replace with '62' (ID)
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }

    // Determine URL
    // If phone is present, specific chat. If empty, general wa.me link which might prompt contact picker on mobile
    const baseUrl = phone ? `https://wa.me/${phone}` : 'https://wa.me/';
    
    const url = `${baseUrl}?text=${encodeURIComponent(waMessage.value)}`;
    window.open(url, '_blank');
    showWaModal.value = false;
};

const confirmApprove = () => {
    router.post(route('trips.savings.approve', [props.trip.id, props.savings.id]), {}, {
        onSuccess: () => showApproveModal.value = false,
    });
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

                        <!-- WA Confirmation Button -->
                        <div v-if="isOwner && savings.payment_status === 'pending'">
                            <button
                                @click="showWaModal = true"
                                class="w-full py-3 px-4 bg-green-500 text-white font-semibold rounded-xl shadow hover:bg-green-600 transition flex items-center justify-center gap-2"
                            >
                                <span class="text-xl">📱</span>
                                Konfirmasi via WhatsApp
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

            <!-- WA Modal -->
            <Teleport to="body">
                <div v-if="showWaModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showWaModal = false" />
                        <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:align-middle">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kirim Konfirmasi WhatsApp</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <InputLabel value="Nomor WA Admin (Opsional)" />
                                    <TextInput 
                                        v-model="adminPhone" 
                                        type="tel" 
                                        class="mt-1 block w-full" 
                                        placeholder="628123456789" 
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Hubungi Bendahara: 1. Nabilla: 0895344652484; 2. Alifvya 088220652688; atau kosongkan jika ingin memilih kontak dari daftar kontak WA Anda.</p>
                                </div>
                                <div>
                                    <InputLabel value="Pesan WhatsApp" />
                                    <textarea 
                                        v-model="waMessage" 
                                        rows="4" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="showWaModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</button>
                                <button 
                                    @click="sendToWa" 
                                    class="px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition flex items-center gap-2"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    Kirim ke WhatsApp
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Approve Confirmation Modal -->
            <Teleport to="body">
                <div v-if="showApproveModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showApproveModal = false" />
                        <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:align-middle">
                            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                        <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Konfirmasi Pembayaran</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Apakah Anda yakin ingin menandai pembayaran ini sudah Diterima?
                                                Aksi ini akan mencatat pemasukan ke saldo trip.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3 sm:flex-row-reverse">
                                <button 
                                    type="button" 
                                    @click="confirmApprove" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto"
                                >
                                    Ya, Terima
                                </button>
                                <button 
                                    type="button" 
                                    @click="showApproveModal = false" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>
