<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const showPassword = ref(false);
const demoTab = ref('company');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const setCredentials = (email, password) => {
    form.email = email;
    form.password = password;
};
</script>

<template>
    <Head title="Masuk - HRIS Web App" />

    <div class="min-h-screen flex font-sans antialiased text-secondary-900 bg-white">
        <!-- Left Panel - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-hero-gradient p-12 flex-col relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-white/3 rounded-full blur-3xl"></div>
                <!-- Grid Pattern -->
                <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col h-full justify-between">
                <!-- Logo -->
                <div>
                    <Link href="/" class="flex items-center gap-3">
                        <img src="/images/logo/logo.png" alt="HRIS Logo" class="w-12 h-12 object-contain rounded-xl bg-white/20 backdrop-blur-sm p-1 shadow" />
                        <span class="text-2xl font-bold text-white tracking-tight">HRIS Web App</span>
                    </Link>
                </div>

                <!-- Main Content - Centered -->
                <div class="my-auto py-8">
                    <div class="max-w-md">
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 mb-6 border border-white/10">
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-white/90 text-sm font-medium">Platform HR #1 di Indonesia</span>
                        </div>

                        <!-- Headline -->
                        <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4 leading-tight">
                            Selamat Datang<br>Kembali!
                        </h1>
                        <p class="text-primary-100 text-lg mb-10 leading-relaxed">
                            Kelola payroll dan HR perusahaan Anda dengan mudah, aman, dan efisien.
                        </p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4 mb-2">
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center border border-white/10">
                                <div class="text-3xl font-bold text-white mb-1">500+</div>
                                <div class="text-primary-200 text-sm">Perusahaan*</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center border border-white/10">
                                <div class="text-3xl font-bold text-white mb-1">50K+</div>
                                <div class="text-primary-200 text-sm">Karyawan*</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center border border-white/10">
                                <div class="text-3xl font-bold text-white mb-1">99%</div>
                                <div class="text-primary-200 text-sm">Uptime*</div>
                            </div>
                        </div>
                        <p class="text-primary-200 text-xs text-center mb-8">*Data ilustrasi</p>

                        <!-- Features List -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white font-medium">Dashboard real-time & analytics</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white font-medium">Payroll otomatis dengan PPh 21 & BPJS</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-white font-medium">Mobile app untuk karyawan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between pt-6 border-t border-white/10">
                    <div class="text-primary-200 text-sm">
                        &copy; {{ new Date().getFullYear() }} HRIS Web App. All rights reserved.
                    </div>
                    <a href="https://adrprogramming.com" target="_blank" class="text-white/60 hover:text-white/90 text-sm transition-colors">
                        Powered by adrprogramming.com
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-white overflow-y-auto">
            <div class="w-full max-w-md py-8">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 text-center">
                    <Link href="/" class="inline-flex items-center gap-2">
                        <img src="/images/logo/logo.png" alt="HRIS Logo" class="w-10 h-10 object-contain rounded-xl shadow-xs" />
                        <span class="text-xl font-bold text-secondary-900">HRIS Web App</span>
                    </Link>
                </div>

                <!-- Header -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl lg:text-3xl font-bold text-secondary-900 mb-2">Masuk ke Akun Anda</h2>
                    <p class="text-secondary-500">Masukkan email dan password untuk melanjutkan</p>
                </div>

                <!-- Error Alert if any -->
                <div v-if="form.errors.email || form.errors.password" class="mb-5 p-3.5 bg-danger-50 border border-danger-200 rounded-xl text-danger-700 text-sm flex items-start gap-2.5">
                    <svg class="w-5 h-5 flex-shrink-0 text-danger-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ form.errors.email || form.errors.password }}</span>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-secondary-700 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input
                                type="email"
                                id="email"
                                v-model="form.email"
                                required
                                autofocus
                                class="form-input w-full rounded-xl border border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                                :class="{ 'border-danger-500': form.errors.email }"
                                style="padding-left: 3rem;"
                                placeholder="nama@perusahaan.com"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-secondary-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                v-model="form.password"
                                required
                                class="form-input w-full rounded-xl border border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                                :class="{ 'border-danger-500': form.errors.password }"
                                style="padding-left: 3rem; padding-right: 3rem;"
                                placeholder="Masukkan password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600 focus:outline-none"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                            />
                            <span class="text-sm text-secondary-600">Ingat saya</span>
                        </label>
                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lupa password?</a>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn btn-primary w-full py-3.5 text-base shadow-sm shadow-primary-500/30 flex items-center justify-center font-semibold disabled:opacity-70"
                    >
                        <svg v-if="!form.processing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Login' }}
                    </button>
                </form>

                <!-- ===== DEMO ACCOUNTS (REMOVE IN PRODUCTION) ===== -->
                <div class="mt-6 p-4 border border-primary-200 bg-primary-50/30 rounded-xl">
                    <div class="flex items-center gap-2 mb-4 text-primary-700 font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Pilih Portal untuk Demo</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button
                            type="button"
                            @click="demoTab = 'company'"
                            :class="demoTab === 'company' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
                            class="py-2 px-4 rounded-lg text-sm font-medium transition-colors"
                        >
                            Company
                        </button>
                        <button
                            type="button"
                            @click="demoTab = 'employee'"
                            :class="demoTab === 'employee' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
                            class="py-2 px-4 rounded-lg text-sm font-medium transition-colors"
                        >
                            Employee
                        </button>
                    </div>

                    <div v-show="demoTab === 'company'" class="space-y-2">
                        <button
                            type="button"
                            @click="setCredentials('hrd@goysmedia.com', 'password123')"
                            class="w-full flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg hover:border-primary-400 hover:shadow-sm transition-all text-left"
                        >
                            <div>
                                <p class="font-medium text-slate-800 text-sm">HR / Owner (Goys)</p>
                                <p class="text-xs text-slate-500">hrd@goysmedia.com</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 bg-primary-100 text-primary-700 rounded-md">Owner</span>
                        </button>
                        <button
                            type="button"
                            @click="setCredentials('hrd@adrproject.com', 'password123')"
                            class="w-full flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg hover:border-primary-400 hover:shadow-sm transition-all text-left"
                        >
                            <div>
                                <p class="font-medium text-slate-800 text-sm">HR / Owner (ADR)</p>
                                <p class="text-xs text-slate-500">hrd@adrproject.com</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 bg-primary-100 text-primary-700 rounded-md">Owner</span>
                        </button>
                    </div>

                    <div v-show="demoTab === 'employee'" class="space-y-2">
                        <button
                            type="button"
                            @click="setCredentials('johndoe@goysmedia.com', 'password123')"
                            class="w-full flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg hover:border-primary-400 hover:shadow-sm transition-all text-left"
                        >
                            <div>
                                <div class="font-semibold text-secondary-900 text-sm">Karyawan (John Doe)</div>
                                <div class="text-xs text-secondary-500">johndoe@goysmedia.com</div>
                            </div>
                            <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="setCredentials('budi@adrproject.com', 'password123')"
                            class="w-full flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg hover:border-primary-400 hover:shadow-sm transition-all text-left"
                        >
                            <div>
                                <div class="font-semibold text-secondary-900 text-sm">Karyawan (Budi Santoso)</div>
                                <div class="text-xs text-secondary-500">budi@adrproject.com</div>
                            </div>
                            <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 text-center">
                        <span class="text-xs text-slate-500">Password untuk semua akun: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono">password123</code></span>
                    </div>
                </div>
                <!-- ===== END DEMO ACCOUNTS ===== -->
            </div>
        </div>
    </div>
</template>
