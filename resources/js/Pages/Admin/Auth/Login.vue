<script setup>
import { ref, nextTick } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const showPassword = ref(false);
const passwordInput = ref(null);
const isShaking = ref(false);
const dismissedError = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    dismissedError.value = false;
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
        onError: () => {
            isShaking.value = true;
            setTimeout(() => {
                isShaking.value = false;
            }, 600);
            nextTick(() => {
                passwordInput.value?.focus();
            });
        },
    });
};

const fillDemoAdmin = () => {
    form.clearErrors();
    dismissedError.value = false;
    form.email = 'admin@gmail.com';
    form.password = 'password123';
};
</script>

<template>
    <Head title="Platform Master Login - Super Admin" />

    <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans antialiased text-slate-100">
        <!-- Ambient Glowing Background - Orange/Amber Theme -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-gradient-to-br from-orange-600/20 via-amber-600/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] bg-gradient-to-tl from-amber-600/20 via-orange-500/10 to-transparent rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-orange-500/5 rounded-full blur-3xl"></div>

            <!-- Subtle Grid Overlay -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-rule=\'evenodd\'%3E%3Cpath d=\'M0 40L40 0H20L0 20M40 40V20L20 40\'/%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <!-- Header Brand -->
            <div class="text-center mb-8">
                <Link href="/" class="inline-flex flex-col items-center gap-3 group">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-orange-600 via-amber-600 to-amber-500 p-0.5 shadow-xl shadow-orange-600/20 group-hover:scale-105 transition-transform duration-300">
                        <div class="w-full h-full bg-slate-900 rounded-[14px] flex items-center justify-center p-2.5">
                            <img src="/images/logo/logo.png" alt="Logo" class="w-full h-full object-contain" />
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center justify-center gap-2">
                            <span>Platform Master</span>
                            <span class="px-2 py-0.5 text-xs font-extrabold rounded-md bg-gradient-to-r from-orange-500 to-amber-500 text-slate-950 uppercase tracking-wider">
                                Root
                            </span>
                        </h1>
                        <p class="text-xs font-medium text-amber-400/90 mt-1 uppercase tracking-widest">
                            Super Administrator Console
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Card Box -->
            <div
                class="bg-slate-900/85 backdrop-blur-xl rounded-3xl p-6 sm:p-9 border border-orange-500/20 shadow-2xl shadow-slate-950/80 transition-all"
                :class="isShaking ? 'animate-shake' : ''"
            >
                <!-- Warning / Security Notice Badge -->
                <div class="mb-6 p-3 rounded-xl bg-orange-950/40 border border-orange-500/30 text-orange-200 text-xs font-medium flex items-center justify-between gap-2 shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-400 animate-ping"></span>
                        <span>Portal Khusus Super Administrator</span>
                    </div>
                    <span class="text-[10px] text-orange-400/70 uppercase tracking-wider font-mono">SECURE-SSL</span>
                </div>

                <!-- Error Alert -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-2 scale-95"
                >
                    <div
                        v-if="(form.errors.email || form.errors.password || $page.props.errors?.email || $page.props.errors?.password) && !dismissedError"
                        class="mb-6 p-3.5 bg-rose-950/60 border border-rose-500/40 rounded-2xl text-rose-200 text-xs shadow-xs flex items-start gap-3"
                    >
                        <div class="w-7 h-7 rounded-lg bg-rose-900/80 text-rose-300 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-rose-300 text-xs uppercase tracking-wider mb-0.5">Akses Ditolak</p>
                            <p class="text-rose-200 leading-relaxed">
                                {{ form.errors.email || form.errors.password || $page.props.errors?.email || $page.props.errors?.password }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="dismissedError = true"
                            class="p-1 text-rose-400 hover:text-rose-200 rounded-lg transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </transition>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="admin-email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Email Administrator
                        </label>
                        <div class="relative">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <svg class="w-5 h-5 text-orange-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input
                                id="admin-email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                placeholder="admin@gmail.com"
                                class="w-full bg-slate-800/90 text-white placeholder-slate-500 rounded-xl border border-slate-700/80 pl-11 pr-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition-all outline-none"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="admin-password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                Kata Sandi Master
                            </label>
                        </div>
                        <div class="relative">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                                <svg class="w-5 h-5 text-orange-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                ref="passwordInput"
                                id="admin-password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="••••••••"
                                class="w-full bg-slate-800/90 text-white placeholder-slate-500 rounded-xl border border-slate-700/80 pl-11 pr-11 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition-all outline-none"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-amber-400 transition-colors cursor-pointer"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-orange-500 focus:ring-orange-500/20 focus:ring-offset-slate-900"
                            />
                            <span class="text-xs text-slate-300 font-medium">Ingat sesi ini</span>
                        </label>
                        <span class="text-[11px] text-amber-400/80 font-mono">IP Tracked</span>
                    </div>

                    <!-- Submit Button - Bold Orange/Amber Gradient -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 rounded-xl font-bold text-white bg-gradient-to-r from-orange-600 via-amber-600 to-amber-500 hover:from-orange-500 hover:via-amber-500 hover:to-amber-400 focus:ring-2 focus:ring-amber-500/30 shadow-lg shadow-orange-600/30 flex items-center justify-center gap-2 transition-all duration-200 cursor-pointer disabled:opacity-60 active:scale-[0.99]"
                        >
                            <svg v-if="!form.processing" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Memverifikasi Kredensial...' : 'Masuk ke Platform Master' }}</span>
                        </button>
                    </div>

                    <!-- Quick Demo Fill -->
                    <div class="pt-2 border-t border-slate-800">
                        <button
                            type="button"
                            @click="fillDemoAdmin"
                            class="w-full py-2 px-3 rounded-lg text-xs font-semibold text-amber-400/90 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Gunakan Akun Demo Admin (admin@gmail.com)</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Links -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-3">
                <Link :href="route('login')" class="hover:text-amber-400 transition-colors flex items-center gap-1.5 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Login Tenant (Owner & Karyawan)</span>
                </Link>

                <Link href="/" class="hover:text-slate-300 transition-colors font-medium">
                    Halaman Depan Publik &rarr;
                </Link>
            </div>

            <div class="mt-6 text-center text-[11px] text-slate-600 font-mono">
                &copy; {{ new Date().getFullYear() }} Platform Master Console. All rights reserved.
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    15%, 45%, 75% {
        transform: translateX(-6px);
    }
    30%, 60%, 90% {
        transform: translateX(6px);
    }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>
