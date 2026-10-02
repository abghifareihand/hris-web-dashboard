<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    support_email: props.settings.support_email || '',
    support_phone: props.settings.support_phone || '',
    trial_days: props.settings.trial_days || 14,
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Global Platform" />

    <AppLayout>
        <div class="space-y-6 max-w-4xl">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Platform SaaS</h1>
                <p class="text-sm text-slate-500 mt-1">Konfigurasi operasional global sistem Frans HRIS, kontak support, dan parameter lisensi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left: Form Settings -->
                <div class="md:col-span-2 space-y-6">
                    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Konfigurasi Dukungan & Layanan</h2>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Aplikasi Platform
                            </label>
                            <input
                                type="text"
                                :value="settings.app_name"
                                disabled
                                class="w-full text-sm rounded-xl border-slate-200 bg-slate-50 text-slate-500 cursor-not-allowed"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Dikonfigurasikan melalui environment platform.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Layanan Dukungan (Support) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                v-model="form.support_email"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                required
                            />
                            <p v-if="form.errors.support_email" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.support_email }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Hotline WhatsApp / Telepon CS <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.support_phone"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                required
                            />
                            <p v-if="form.errors.support_phone" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.support_phone }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Durasi Masa Percobaan / Free Trial (Hari) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="90"
                                v-model="form.trial_days"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 transition-colors"
                                required
                            />
                            <p v-if="form.errors.trial_days" class="text-xs text-rose-500 mt-1">
                                {{ form.errors.trial_days }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-orange-500/20 active:scale-[0.99] disabled:opacity-50"
                            >
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Pengaturan</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right: System Diagnostics Info -->
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Informasi Server & Stack</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Versi Frans HRIS:</span>
                                <span class="font-bold text-slate-800">{{ settings.system_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Framework:</span>
                                <span class="font-bold text-slate-800">Laravel {{ settings.laravel_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">PHP Runtime:</span>
                                <span class="font-bold text-slate-800">PHP {{ settings.php_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-50">
                                <span class="text-slate-500">Frontend Stack:</span>
                                <span class="font-bold text-slate-800">Inertia.js + Vue 3</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Database Driver:</span>
                                <span class="font-bold text-slate-800">MySQL / InnoDB</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold text-orange-950">Multi-Tenant Engine Status</span>
                        </div>
                        <p class="text-xs text-orange-800 leading-relaxed">
                            Setiap tenant perusahaan terisolasi dengan relasi aman berdasarkan <code>company_id</code>. Seluruh data transaksi, karyawan, dan presensi terlindungi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
