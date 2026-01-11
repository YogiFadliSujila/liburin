<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    trip: Object,
});

const form = useForm({
    name: props.trip.name,
    description: props.trip.description || '',
    destination: props.trip.destination,
    start_date: props.trip.start_date,
    end_date: props.trip.end_date,
    target_amount: props.trip.target_amount,
    status: props.trip.status,
    cover_image: null,
});

const showDeleteModal = ref(false);

const submit = () => {
    form.post(route('trips.update', props.trip.id), {
        forceFormData: true,
        _method: 'PATCH',
    });
};

const handleImageChange = (e) => {
    form.cover_image = e.target.files[0];
};

const deleteTrip = () => {
    router.delete(route('trips.destroy', props.trip.id));
};

const statusOptions = [
    { value: 'planning', label: 'Planning', color: 'bg-blue-500' },
    { value: 'saving', label: 'Menabung', color: 'bg-amber-500' },
    { value: 'ongoing', label: 'Berlangsung', color: 'bg-green-500' },
    { value: 'completed', label: 'Selesai', color: 'bg-gray-500' },
    { value: 'cancelled', label: 'Dibatalkan', color: 'bg-red-500' },
];
</script>

<template>
    <Head :title="`Edit ${trip.name}`" />

    <AuthenticatedLayout>
        <template #header>
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
                    ✏️ Edit Trip
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Trip Name -->
                        <div>
                            <InputLabel for="name" value="Nama Trip" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Destination -->
                        <div>
                            <InputLabel for="destination" value="Destinasi" />
                            <TextInput
                                id="destination"
                                v-model="form.destination"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.destination" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Deskripsi (Opsional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_date" value="Tanggal Mulai" />
                                <TextInput
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.start_date" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="end_date" value="Tanggal Selesai" />
                                <TextInput
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.end_date" class="mt-2" />
                            </div>
                        </div>

                        <!-- Target Amount -->
                        <div>
                            <InputLabel for="target_amount" value="Target Tabungan" />
                            <div class="relative mt-1">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">Rp</span>
                                <TextInput
                                    id="target_amount"
                                    v-model="form.target_amount"
                                    type="number"
                                    class="block w-full pl-10"
                                    min="0"
                                    step="1000"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.target_amount" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image (Opsional)" />
                            <div class="mt-1">
                                <div v-if="trip.cover_image && !form.cover_image" class="mb-3 relative rounded-lg overflow-hidden">
                                    <img :src="trip.cover_image" class="w-full h-32 object-cover" />
                                </div>
                                <label
                                    for="cover_image"
                                    class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-4 transition hover:border-indigo-500"
                                >
                                    <div class="text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ form.cover_image ? form.cover_image.name : 'Ganti gambar cover' }}
                                        </p>
                                    </div>
                                    <input
                                        id="cover_image"
                                        type="file"
                                        class="sr-only"
                                        accept="image/*"
                                        @change="handleImageChange"
                                    />
                                </label>
                            </div>
                            <InputError :message="form.errors.cover_image" class="mt-2" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                @click="showDeleteModal = true"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Hapus Trip
                            </button>
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('trips.show', trip.id)"
                                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900"
                                >
                                    Batal
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false" />
                    <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Hapus Trip</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Apakah Anda yakin ingin menghapus trip "{{ trip.name }}"? Semua data termasuk tabungan dan itinerary akan dihapus secara permanen.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <DangerButton @click="deleteTrip" class="w-full sm:ml-3 sm:w-auto">
                                Ya, Hapus
                            </DangerButton>
                            <button
                                type="button"
                                @click="showDeleteModal = false"
                                class="mt-3 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
