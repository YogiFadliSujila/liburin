<script setup>
import { computed } from 'vue';

const props = defineProps({
    toasts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['remove']);

const getToastStyles = (type) => {
    const styles = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-amber-500 text-white',
        info: 'bg-indigo-500 text-white',
    };
    return styles[type] || styles.info;
};

const getIcon = (type) => {
    const icons = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return icons[type] || icons.info;
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
            <transition-group
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-x-8"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-8"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[getToastStyles(toast.type), 'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg']"
                >
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(toast.type)" />
                    </svg>
                    <p class="text-sm font-medium flex-1">{{ toast.message }}</p>
                    <button
                        @click="emit('remove', toast.id)"
                        class="text-white/70 hover:text-white transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition-group>
        </div>
    </Teleport>
</template>
