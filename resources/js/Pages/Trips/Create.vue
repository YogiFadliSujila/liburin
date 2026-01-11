<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    destination: '',
    start_date: '',
    end_date: '',
    target_amount: '',
    cover_image: null,
});

const submit = () => {
    form.post(route('trips.store'), {
        forceFormData: true,
    });
};

const handleImageChange = (e) => {
    form.cover_image = e.target.files[0];
};
</script>

<template>
    <Head title="Buat Trip Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('trips.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    ✈️ Buat Trip Baru
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-lg">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-semibold text-white">Rancang Liburan Impianmu</h3>
                        <p class="text-sm text-indigo-100">Isi detail trip untuk mulai mengumpulkan tabungan bersama</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Trip Name -->
                        <div>
                            <InputLabel for="name" value="Nama Trip" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="contoh: Bali Trip 2026"
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
                                placeholder="contoh: Bali, Indonesia"
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
                                placeholder="Ceritakan tentang trip ini..."
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
                                    placeholder="5000000"
                                    min="0"
                                    step="1000"
                                    required
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Total dana yang perlu dikumpulkan untuk trip ini</p>
                            <InputError :message="form.errors.target_amount" class="mt-2" />
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image (Opsional)" />
                            <div class="mt-1">
                                <label
                                    for="cover_image"
                                    class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 transition hover:border-indigo-500 dark:hover:border-indigo-400"
                                >
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            {{ form.cover_image ? form.cover_image.name : 'Klik untuk upload gambar' }}
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
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link
                                :href="route('trips.index')"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
                            >
                                Batal
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {{ form.processing ? 'Menyimpan...' : 'Buat Trip' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
