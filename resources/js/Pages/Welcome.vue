<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />
    
    <div class="relative flex min-h-screen w-full flex-col bg-background-light dark:bg-gray-900 overflow-hidden font-display text-slate-900 antialiased selection:bg-primary/20 selection:text-primary">
        <!-- Background Gradient Effects -->
        <div class="absolute top-[-20%] left-[-10%] h-[500px] w-[500px] rounded-full bg-blue-500/10 blur-[100px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-5%] h-[400px] w-[400px] rounded-full bg-blue-400/10 blur-[80px] pointer-events-none"></div>

        <!-- Main Content Container -->
        <div class="flex flex-col h-full w-full max-w-md mx-auto p-6 z-10 my-auto justify-center">
            
            <!-- Top Section: Brand & Hero -->
            <div class="flex flex-1 flex-col items-center justify-center gap-8 mb-10">
                <!-- Logo Container -->
                <div class="relative group">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-400 opacity-30 blur group-hover:opacity-50 transition duration-1000"></div>
                    <div class="relative flex h-32 w-32 items-center justify-center rounded-2xl bg-white dark:bg-slate-800 shadow-xl overflow-hidden ring-1 ring-black/5 dark:ring-white/10">
                        <div class="bg-center bg-no-repeat bg-cover h-full w-full opacity-90" style='background-image: url("https://img.freepik.com/free-psd/3d-rendering-travel-icon_23-2151695696.jpg?t=st=1768116496~exp=1768120096~hmac=1a85b29300d7ef1c3cee34aec12c9c963445b7c36db14f6f45661aa81205c240&w=1480");'>
                            <div class="absolute inset-0 bg-blue-600/20 mix-blend-overlay"></div>
                        </div>
                    </div>
                </div>

                <!-- Text Branding -->
                <div class="flex flex-col items-center gap-2">
                    <h1 class="text-slate-900 dark:text-white text-4xl font-extrabold tracking-tight">Liburin</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-lg font-medium text-center leading-relaxed">
                        Your journey, organized.<br/>
                        <span class="text-sm font-normal opacity-80">Plan, track, and enjoy your trips effortlessly.</span>
                    </p>
                </div>
            </div>

            <!-- Bottom Section: Actions -->
            <div class="flex flex-col gap-4 w-full">
                <!-- Sign In Button (Primary) -->
                <Link 
                    :href="route('login')"
                    class="relative flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] transition-all duration-200 shadow-lg shadow-indigo-500/25"
                >
                    <span class="text-white text-base font-bold leading-normal tracking-wide">Sign In</span>
                </Link>

                <!-- Create Account Button (Secondary/Outlined look) -->
                <Link 
                    :href="route('register')"
                    v-if="canRegister"
                    class="relative flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-700 hover:border-blue-500/30 dark:hover:border-blue-500/30 active:scale-[0.98] transition-all duration-200"
                >
                    <span class="text-slate-900 dark:text-white text-base font-bold leading-normal tracking-wide">Create Account</span>
                </Link>

                <!-- Meta Text / Footer -->
                <div class="mt-4">
                    <p class="text-slate-400 dark:text-slate-500 text-xs font-medium leading-normal text-center">
                        By continuing, you agree to our 
                        <a class="underline hover:text-blue-500 transition-colors" href="#">Terms of Service</a> &amp; 
                        <a class="underline hover:text-blue-500 transition-colors" href="#">Privacy Policy</a>.
                    </p>
                </div>
            </div>

            <!-- Safe area spacer for iOS home indicator -->
            <div class="h-2 w-full"></div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Noto+Sans:wght@400;500;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0');

.font-display {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
