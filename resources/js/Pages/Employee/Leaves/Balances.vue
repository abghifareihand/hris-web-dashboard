<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

const props = defineProps({
    balances: Array,
});
</script>

<template>
    <Head title="Sisa Saldo Cuti" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Cuti & Izin Kerja</h1>
                    <p class="text-sm text-slate-500 mt-1">Rincian sisa kuota dan pemakaian hak cuti Anda pada tahun berjalan.</p>
                </div>
                <Link
                    :href="route('employee.leaves.index')"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Cuti Baru
                </Link>
            </div>

            <!-- Leave Sub-Navigation Tabs -->
            <div class="flex items-center border-b border-slate-200">
                <nav class="flex gap-6 -mb-px">
                    <Link
                        :href="route('employee.leaves.index')"
                        class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors"
                    >
                        Riwayat & Pengajuan Cuti
                    </Link>
                    <Link
                        :href="route('employee.leaves.balances')"
                        class="pb-3 text-sm font-semibold border-b-2 border-sky-600 text-sky-600 transition-colors"
                    >
                        Sisa Kuota / Saldo Cuti
                    </Link>
                </nav>
            </div>

            <!-- Empty State -->
            <div v-if="!balances || balances.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-xs">
                <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900">Belum ada data kuota cuti</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Pengaturan kuota cuti tahunan Anda belum dialokasikan oleh bagian HRD atau manajemen perusahaan.</p>
            </div>

            <!-- Balances Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="item in balances"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between hover:border-sky-300 transition-colors group"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700">
                                Kategori Cuti
                            </span>
                            <span class="text-xs font-medium text-slate-400">
                                Hak: {{ item.leave_category?.days_count || (item.balance + (item.used || 0)) }} Hari
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-sky-600 transition-colors">
                            {{ item.leave_category?.name || 'Cuti Tahunan' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                            Hak istirahat kerja yang dialokasikan untuk posisi dan masa kerja Anda.
                        </p>

                        <!-- Progress Bar -->
                        <div class="mt-5 space-y-1.5">
                            <div class="flex justify-between text-xs text-slate-600 font-medium">
                                <span>Terpakai: {{ item.used || 0 }} hari</span>
                                <span class="text-sky-600 font-bold">Sisa: {{ item.balance }} hari</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                <div
                                    class="bg-sky-500 h-2.5 rounded-full transition-all duration-500"
                                    :style="{
                                        width: `${Math.min(100, Math.max(5, (item.balance / Math.max(1, (item.balance + (item.used || 0)))) * 100))}%`
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Sisa Saldo</span>
                            <span class="text-xl font-extrabold text-slate-900">{{ item.balance }} <span class="text-xs font-medium text-slate-500">Hari</span></span>
                        </div>
                        <Link
                            :href="route('employee.leaves.index')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-sky-50 text-slate-700 hover:text-sky-700 text-xs font-semibold rounded-xl border border-slate-200/80 hover:border-sky-200 transition-colors"
                        >
                            Ajukan Cuti
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>
