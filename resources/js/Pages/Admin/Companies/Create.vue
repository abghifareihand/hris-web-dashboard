<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    company_name: '',
    business_type: '',
    province: '',
    city: '',
    phone: '',
    name: '',
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('admin.companies.store'));
};
</script>

<template>
    <Head title="Daftarkan Perusahaan Baru" />

    <AppLayout>
        <div class="w-full space-y-6">
            <!-- Header Section -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.companies.index')"
                    class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition shadow-xs"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftarkan Perusahaan Klien Baru</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Buat entitas perusahaan tenant dan akun pemilik pertama.</p>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Company Information Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Informasi Perusahaan Klien</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Perusahaan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.company_name"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="PT Contoh Teknologi Nusantara"
                                required
                            />
                            <p v-if="form.errors.company_name" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.company_name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Sektor / Tipe Bisnis <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.business_type"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="Teknologi, Ritel, F&B, Jasa"
                                required
                            />
                            <p v-if="form.errors.business_type" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.business_type }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Provinsi <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.province"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="DKI Jakarta"
                                required
                            />
                            <p v-if="form.errors.province" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.province }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Kota / Kabupaten <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.city"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="Jakarta Selatan"
                                required
                            />
                            <p v-if="form.errors.city" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.city }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor Telepon Kantor / Hotline <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.phone"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                            placeholder="021-12345678"
                            required
                        />
                        <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.phone }}
                        </p>
                    </div>
                </div>

                <!-- Owner Account Information Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Akun Pemilik Utama (Company Owner)</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Pemilik <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="Nama lengkap pemilik"
                                required
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Login Utama <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                v-model="form.email"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                placeholder="owner@perusahaan.com"
                                required
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Awal <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="password"
                            v-model="form.password"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                            placeholder="Minimal 8 karakter"
                            required
                        />
                        <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>
                </div>

                <!-- Submit Button Group -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('admin.companies.index')"
                        class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-orange-500/20 active:scale-[0.99] disabled:opacity-50"
                    >
                        <span v-if="form.processing">Mendaftarkan...</span>
                        <span v-else>Daftarkan Perusahaan</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
