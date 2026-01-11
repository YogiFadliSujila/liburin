<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    trip: Object,
    members: Array,
    isAdmin: Boolean,
});

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
    user_id: props.isAdmin ? '' : null, // Selected user for admin
});

const remaining = computed(() => props.trip.remaining);

const submit = () => {
    form.post(route('trips.savings.store', props.trip.id));
};

const quickAmounts = [50000, 100000, 200000, 500000, 1000000];

const setAmount = (amount) => {
    form.amount = amount;
};
</script>

<template>
    <Head title="Catat Pembayaran" />

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
                    {{ isAdmin ? 'Catat Pembayaran (Admin)' : 'Setor Tabungan' }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Header & Balance Info -->
                        <div class="mb-8 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-white shadow-lg">
                            <h3 class="text-lg font-medium opacity-90">Target Tabungan</h3>
                            <p class="text-3xl font-bold mt-1">
                                {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trip.target_amount) }}
                            </p>
                            <div class="mt-4 flex justify-between text-sm opacity-80">
                                <span>Terkumpul: {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(trip.total_savings) }}</span>
                                <span>Sisa: {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(remaining) }}</span>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Helper Text for Non-Admin -->
                             <div v-if="!isAdmin" class="p-4 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm mb-4">
                                Silakan masukkan nominal yang Anda bayarkan. Laporan ini akan berstatus <strong>Pending</strong> sampai dikonfirmasi oleh Admin.
                            </div>

                            <!-- Admin: Select Member -->
                            <div v-if="isAdmin">
                                <InputLabel for="user_id" value="Identitas Penabung (Anggota)" />
                                <select 
                                    id="user_id" 
                                    v-model="form.user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    required
                                >
                                    <option value="" disabled>Pilih anggota yang membayar</option>
                                    <option v-for="member in members" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.user_id" class="mt-2" />
                            </div>

                            <!-- Amount -->
                            <div>
                                <InputLabel for="amount" value="Nominal Pembayaran (Rp)" />
                                <TextInput
                                    id="amount"
                                    type="number"
                                    class="mt-1 block w-full text-lg font-semibold"
                                    v-model="form.amount"
                                    required
                                    min="1000"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.amount" class="mt-2" />

                                <!-- Quick Amounts -->
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button 
                                        type="button"
                                        v-for="amt in quickAmounts" 
                                        :key="amt"
                                        @click="setAmount(amt)"
                                        class="rounded-full border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                    >
                                        {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amt) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Payment Date -->
                            <div>
                                <InputLabel for="payment_date" value="Tanggal Pembayaran" />
                                <TextInput
                                    id="payment_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.payment_date"
                                    required
                                />
                                <InputError :message="form.errors.payment_date" class="mt-2" />
                            </div>

                            <!-- Notes -->
                            <div>
                                <InputLabel for="notes" value="Catatan (Opsional)" />
                                <TextInput
                                    id="notes"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.notes"
                                    placeholder="Contoh: Transfer via BCA"
                                />
                                <InputError :message="form.errors.notes" class="mt-2" />
                            </div>

                            <!-- Submit -->
                            <div class="pt-4">
                                <PrimaryButton
                                    class="w-full justify-center py-3 text-base"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    {{ isAdmin ? 'Simpan Pembayaran' : 'Kirim Laporan Pembayaran' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
