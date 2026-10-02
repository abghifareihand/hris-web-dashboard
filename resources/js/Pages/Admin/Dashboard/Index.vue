<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    recentCompanies: Array,
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20 mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                        Platform Super Administrator
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ringkasan Sistem Multi-Tenant</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Monitoring seluruh klien perusahaan, pertumbuhan tenant, dan utilisasi SaaS Frans HRIS.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.companies.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-orange-500/20 active:scale-[0.99]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Daftarkan Klien Baru
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Companies -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Perusahaan Klien</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ stats.total_companies }}</p>
                        <p class="text-xs text-orange-600 font-medium mt-1">Tenant Aktif</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <!-- Total Employees -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Karyawan</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ stats.total_employees }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ stats.active_employees }} Aktif Bekerja</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Subscription Count -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Langganan Aktif</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1.5">{{ stats.active_subscriptions }}</p>
                        <p class="text-xs text-sky-600 font-medium mt-1">SLA 99.9% Online</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </div>

                <!-- Revenue Estimate -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Est. MRR SaaS</p>
                        <p class="text-2xl font-black text-slate-900 mt-1.5">{{ formatRupiah(stats.monthly_revenue) }}</p>
                        <p class="text-xs text-amber-600 font-medium mt-1">Estimasi Bulanan</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Registered Companies Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Perusahaan Klien Terbaru</h2>
                        <p class="text-xs text-slate-500 mt-0.5">5 Perusahaan yang baru terdaftar pada platform SaaS</p>
                    </div>
                    <Link
                        :href="route('admin.companies.index')"
                        class="text-xs font-semibold text-orange-600 hover:text-orange-700 transition"
                    >
                        Lihat Semua Perusahaan &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Nama Perusahaan</th>
                                <th class="px-5 py-3.5">Pemilik (Owner)</th>
                                <th class="px-5 py-3.5">Tipe Bisnis</th>
                                <th class="px-5 py-3.5">Kota / Lokasi</th>
                                <th class="px-5 py-3.5 text-right">Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!recentCompanies || recentCompanies.length === 0">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400 text-xs">
                                    Belum ada data perusahaan terdaftar.
                                </td>
                            </tr>
                            <tr v-for="comp in recentCompanies" :key="comp.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs">
                                            {{ comp.name?.charAt(0) || 'C' }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ comp.name }}</p>
                                            <p class="text-xs text-slate-400">{{ comp.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-medium text-slate-800">{{ comp.owner?.name || 'Owner' }}</p>
                                    <p class="text-xs text-slate-400">{{ comp.owner?.email }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ comp.business_type || 'Umum' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ comp.city || '-' }}, {{ comp.province || '-' }}
                                </td>
                                <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(comp.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
