<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    trip: Object,
    withdrawals: Array,
    isAdmin: Boolean,
    activeMembersCount: Number,
});

const showForm = ref(false);
const showVoteModal = ref(false);
const selectedWithdrawal = ref(null);

const form = useForm({
    amount: '',
    reason: '',
    description: '',
    voting_days: 3,
});

const voteForm = useForm({
    approved: true,
    comment: '',
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
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[status] || colors.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Menunggu Vote',
        approved: 'Disetujui',
        rejected: 'Ditolak',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
    };
    return labels[status] || status;
};

const votesRequired = computed(() => Math.ceil(props.activeMembersCount / 2));

const submitRequest = () => {
    form.post(route('trips.withdrawals.store', props.trip.id), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const openVoteModal = (withdrawal) => {
    selectedWithdrawal.value = withdrawal;
    showVoteModal.value = true;
};

const submitVote = () => {
    voteForm.post(route('trips.withdrawals.vote', [props.trip.id, selectedWithdrawal.value.id]), {
        onSuccess: () => {
            showVoteModal.value = false;
            selectedWithdrawal.value = null;
            voteForm.reset();
        },
    });
};

const cancelWithdrawal = (withdrawal) => {
    if (confirm('Apakah Anda yakin ingin membatalkan permintaan ini?')) {
        router.delete(route('trips.withdrawals.destroy', [props.trip.id, withdrawal.id]));
    }
};

const completeWithdrawal = (withdrawal) => {
    if (confirm('Konfirmasi bahwa dana sudah ditransfer?')) {
        router.post(route('trips.withdrawals.complete', [props.trip.id, withdrawal.id]));
    }
};
</script>

<template>
    <Head :title="`Penarikan - ${trip.name}`" />

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
                        🗳️ Penarikan Dana
                    </h2>
                </div>
                <button
                    v-if="isAdmin"
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all hover:from-purple-700 hover:to-indigo-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Request Penarikan
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Balance Info -->
                <div class="rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm">Saldo Tersedia</p>
                            <p class="text-3xl font-bold">{{ formatCurrency(trip.remaining_balance) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-purple-100 text-sm">Mayoritas Dibutuhkan</p>
                            <p class="text-2xl font-bold">{{ votesRequired }} vote</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Penarikan dana memerlukan persetujuan mayoritas anggota trip ({{ votesRequired }} dari {{ activeMembersCount }} anggota).
                        </p>
                    </div>
                </div>

                <!-- Withdrawals List -->
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Riwayat Permintaan</h3>
                    
                    <div v-for="withdrawal in withdrawals" :key="withdrawal.id" class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span :class="[getStatusColor(withdrawal.status), 'px-2 py-1 rounded-full text-xs font-medium']">
                                        {{ getStatusLabel(withdrawal.status) }}
                                    </span>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-2">{{ formatCurrency(withdrawal.amount) }}</h4>
                                    <p class="text-gray-600 dark:text-gray-300">{{ withdrawal.reason }}</p>
                                    <p v-if="withdrawal.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ withdrawal.description }}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                                    <p>oleh {{ withdrawal.requester.name }}</p>
                                    <p>{{ withdrawal.created_at }}</p>
                                </div>
                            </div>

                            <!-- Voting Progress -->
                            <div v-if="withdrawal.status === 'pending'" class="mb-4">
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-300">
                                        Progress Voting ({{ withdrawal.votes_approve }}/{{ withdrawal.votes_required }} dibutuhkan)
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        Deadline: {{ withdrawal.voting_deadline }}
                                    </span>
                                </div>
                                <div class="h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex">
                                    <div
                                        class="h-full bg-green-500 transition-all"
                                        :style="{ width: `${(withdrawal.votes_approve / withdrawal.votes_required) * 100}%` }"
                                    />
                                    <div
                                        class="h-full bg-red-500 transition-all"
                                        :style="{ width: `${(withdrawal.votes_reject / withdrawal.votes_required) * 100}%` }"
                                    />
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span class="text-green-600">{{ withdrawal.votes_approve }} setuju</span>
                                    <span class="text-red-600">{{ withdrawal.votes_reject }} tolak</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button
                                    v-if="withdrawal.is_voting_open && withdrawal.user_vote === null"
                                    @click="openVoteModal(withdrawal)"
                                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                                >
                                    Berikan Vote
                                </button>
                                <span v-else-if="withdrawal.user_vote !== null" class="text-sm text-gray-500 dark:text-gray-400">
                                    Anda sudah vote: {{ withdrawal.user_vote ? '✅ Setuju' : '❌ Tolak' }}
                                </span>
                                
                                <button
                                    v-if="isAdmin && withdrawal.status === 'approved'"
                                    @click="completeWithdrawal(withdrawal)"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition"
                                >
                                    Tandai Selesai
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="withdrawals.length === 0" class="rounded-xl bg-white dark:bg-gray-800 p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">Belum ada permintaan penarikan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showForm = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <form @submit.prevent="submitRequest">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Request Penarikan Dana</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <InputLabel for="amount" value="Jumlah Penarikan" />
                                    <div class="relative mt-1">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                                        <TextInput id="amount" v-model="form.amount" type="number" class="block w-full pl-10" :max="trip.remaining_balance" min="10000" required />
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Max: {{ formatCurrency(trip.remaining_balance) }}</p>
                                    <InputError :message="form.errors.amount" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="reason" value="Alasan" />
                                    <TextInput id="reason" v-model="form.reason" type="text" class="mt-1 block w-full" placeholder="contoh: Booking hotel" required />
                                    <InputError :message="form.errors.reason" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="description" value="Deskripsi Detail (Opsional)" />
                                    <textarea id="description" v-model="form.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                    <InputError :message="form.errors.description" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="voting_days" value="Durasi Voting (hari)" />
                                    <select id="voting_days" v-model="form.voting_days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option :value="1">1 hari</option>
                                        <option :value="2">2 hari</option>
                                        <option :value="3">3 hari</option>
                                        <option :value="5">5 hari</option>
                                        <option :value="7">7 hari</option>
                                    </select>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="showForm = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</button>
                                <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Mengirim...' : 'Kirim Request' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Vote Modal -->
        <Teleport to="body">
            <div v-if="showVoteModal && selectedWithdrawal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showVoteModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:align-middle">
                        <form @submit.prevent="submitVote">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Vote Penarikan</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="text-center py-2">
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(selectedWithdrawal.amount) }}</p>
                                    <p class="text-gray-600 dark:text-gray-300">{{ selectedWithdrawal.reason }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <label :class="[voteForm.approved ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700', 'flex flex-col items-center p-4 rounded-xl border-2 cursor-pointer transition']">
                                        <input type="radio" :value="true" v-model="voteForm.approved" class="sr-only" />
                                        <span class="text-3xl mb-2">✅</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">Setuju</span>
                                    </label>
                                    <label :class="[!voteForm.approved ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700', 'flex flex-col items-center p-4 rounded-xl border-2 cursor-pointer transition']">
                                        <input type="radio" :value="false" v-model="voteForm.approved" class="sr-only" />
                                        <span class="text-3xl mb-2">❌</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">Tolak</span>
                                    </label>
                                </div>
                                <div>
                                    <InputLabel for="comment" value="Komentar (Opsional)" />
                                    <TextInput id="comment" v-model="voteForm.comment" type="text" class="mt-1 block w-full" placeholder="Alasan vote..." />
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="showVoteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Batal</button>
                                <PrimaryButton :disabled="voteForm.processing">{{ voteForm.processing ? 'Mengirim...' : 'Kirim Vote' }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
