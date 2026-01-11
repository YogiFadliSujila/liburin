<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    trip: Object,
    expenses: Array,
    byCategory: Object,
    categories: Object,
    isAdmin: Boolean,
});

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    category: 'food',
    description: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    receipt_image: null,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getCategoryIcon = (category) => {
    const icons = {
        transport: '🚗',
        food: '🍽️',
        accommodation: '🏨',
        ticket: '🎟️',
        shopping: '🛍️',
        other: '📦',
    };
    return icons[category] || '📦';
};

const submit = () => {
    if (editingId.value) {
        form.post(route('trips.expenses.update', [props.trip.id, editingId.value]), {
            _method: 'PATCH',
            forceFormData: true,
            onSuccess: () => {
                showForm.value = false;
                editingId.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('trips.expenses.store', props.trip.id), {
            forceFormData: true,
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
};

const editExpense = (expense) => {
    editingId.value = expense.id;
    form.category = expense.category;
    form.description = expense.description;
    form.amount = expense.amount;
    form.expense_date = expense.expense_date;
    showForm.value = true;
};

const deleteExpense = (expense) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')) {
        router.delete(route('trips.expenses.destroy', [props.trip.id, expense.id]));
    }
};

const cancelEdit = () => {
    showForm.value = false;
    editingId.value = null;
    form.reset();
};
</script>

<template>
    <Head :title="`Pengeluaran - ${trip.name}`" />

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
                        💸 Pengeluaran
                    </h2>
                </div>
                <button
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-red-500 to-pink-500 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all hover:from-red-600 hover:to-pink-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Catat Pengeluaran
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Cards -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(trip.total_expenses) }}</p>
                            </div>

                        </div>
                    </div>
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Tabungan</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(trip.total_savings) }}</p>
                            </div>

                        </div>
                    </div>
                    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm border border-gray-100 dark:border-gray-700 sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Saldo Tersisa</p>
                                <p 
                                    :class="[trip.remaining_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400', 'text-2xl font-bold']"
                                >
                                    {{ formatCurrency(trip.remaining_balance) }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div v-if="Object.keys(byCategory).length > 0" class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Per Kategori</h3>
                    <div class="flex flex-wrap gap-4">
                        <div v-for="(total, category) in byCategory" :key="category" class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span>{{ getCategoryIcon(category) }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ categories[category] }}</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Expense List -->
                <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Riwayat Pengeluaran</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="expense in expenses" :key="expense.id" class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-center gap-4">
                                <span class="text-2xl">{{ getCategoryIcon(expense.category) }}</span>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ expense.description }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ expense.expense_date }} • {{ expense.recorder.name }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(expense.amount) }}</span>
                                <div class="flex items-center gap-2">
                                    <button @click="editExpense(expense)" class="text-gray-400 hover:text-indigo-500">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteExpense(expense)" class="text-gray-400 hover:text-red-500">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="expenses.length === 0" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p>Belum ada pengeluaran tercatat</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cancelEdit" />
                    <div class="inline-block transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <form @submit.prevent="submit">
                            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ editingId ? 'Edit Pengeluaran' : 'Catat Pengeluaran' }}
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <InputLabel for="category" value="Kategori" />
                                    <select
                                        id="category"
                                        v-model="form.category"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option v-for="(label, value) in categories" :key="value" :value="value">
                                            {{ getCategoryIcon(value) }} {{ label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.category" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="description" value="Deskripsi" />
                                    <TextInput id="description" v-model="form.description" type="text" class="mt-1 block w-full" placeholder="contoh: Makan siang di restoran" required />
                                    <InputError :message="form.errors.description" class="mt-2" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="amount" value="Jumlah" />
                                        <TextInput id="amount" v-model="form.amount" type="number" class="mt-1 block w-full" min="0" required />
                                        <InputError :message="form.errors.amount" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="expense_date" value="Tanggal" />
                                        <TextInput id="expense_date" v-model="form.expense_date" type="date" class="mt-1 block w-full" required />
                                        <InputError :message="form.errors.expense_date" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
                                <button type="button" @click="cancelEdit" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                                    Batal
                                </button>
                                <PrimaryButton :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
