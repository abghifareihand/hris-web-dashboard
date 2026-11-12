<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    position: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`Detail Jabatan - ${position.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3.5 sm:gap-4">
                <Link
                    :href="route('owner.management.company.positions.index')"
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5 cursor-pointer"
                    title="Kembali ke Daftar Jabatan"
                >
                    <svg
                        class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ position.name }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Detail hak akses cabang, divisi, serta daftar karyawan yang mengemban jabatan ini.
                    </p>
                </div>
            </div>

            <Link :href="route('owner.management.company.positions.edit', position.id)">
                <Button variant="primary">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                    </svg>
                    Edit Jabatan
                </Button>
            </Link>
        </div>

        <!-- 3 Stats Quick Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- 1. Hak Akses Cabang -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Hak Akses Cabang</span>
                    <span class="text-xl font-bold text-slate-800">
                        {{ position.branches?.length ? `${position.branches.length} Cabang` : 'Tidak ada akses' }}
                    </span>
                </div>
            </div>

            <!-- 2. Hak Akses Divisi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Hak Akses Divisi</span>
                    <span class="text-xl font-bold text-slate-800">
                        {{ position.divisions?.length ? `${position.divisions.length} Divisi` : 'Tidak ada akses' }}
                    </span>
                </div>
            </div>

            <!-- 3. Total Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Total Pemegang Jabatan</span>
                    <span class="text-xl font-bold text-slate-800">{{ position.employees_count || 0 }} Karyawan</span>
                </div>
            </div>
        </div>

        <!-- Detail Hak Akses Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card Hak Akses Cabang -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Daftar Hak Akses Cabang
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Cabang kantor yang dapat diakses oleh jabatan ini.
                        </p>
                    </div>
                    <span
                        v-if="position.branches && position.branches.length > 0"
                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200"
                    >
                        {{ position.branches.length }} Cabang
                    </span>
                    <span v-else class="text-xs text-slate-400 italic">
                        Tidak ada akses
                    </span>
                </div>
                <div class="p-6 flex-1">
                    <div v-if="position.branches && position.branches.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="branch in position.branches"
                            :key="branch.id"
                            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-sky-50 border border-sky-200 text-sky-900 shadow-2xs"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></div>
                            <span class="text-sm font-semibold truncate">{{ branch.name }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-8 px-4 text-center rounded-xl bg-slate-50/70 border border-dashed border-slate-200"
                    >
                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="text-sm font-medium text-slate-600">Tidak ada akses cabang</p>
                        <p class="text-xs text-slate-400 mt-0.5">Jabatan ini tidak diberikan akses ke cabang mana pun.</p>
                    </div>
                </div>
            </div>

            <!-- Card Hak Akses Divisi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">
                            Daftar Hak Akses Divisi
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Divisi kerja yang dapat diakses oleh jabatan ini.
                        </p>
                    </div>
                    <span
                        v-if="position.divisions && position.divisions.length > 0"
                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >
                        {{ position.divisions.length }} Divisi
                    </span>
                    <span v-else class="text-xs text-slate-400 italic">
                        Tidak ada akses
                    </span>
                </div>
                <div class="p-6 flex-1">
                    <div v-if="position.divisions && position.divisions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="division in position.divisions"
                            :key="division.id"
                            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-2xs"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></div>
                            <span class="text-sm font-semibold truncate">{{ division.name }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-8 px-4 text-center rounded-xl bg-slate-50/70 border border-dashed border-slate-200"
                    >
                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="text-sm font-medium text-slate-600">Tidak ada akses divisi</p>
                        <p class="text-xs text-slate-400 mt-0.5">Jabatan ini tidak diberikan akses ke divisi mana pun.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Karyawan Pemegang Jabatan -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-800">
                    Karyawan dengan Jabatan Ini
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Daftar karyawan aktif yang saat ini menjabat sebagai {{ position.name }}.
                </p>
            </div>

            <div v-if="position.employees && position.employees.length > 0" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Karyawan</th>
                            <th class="px-6 py-3">NIP</th>
                            <th class="px-6 py-3">Cabang</th>
                            <th class="px-6 py-3">Divisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr
                            v-for="emp in position.employees"
                            :key="emp.id"
                            class="hover:bg-slate-50/60 transition"
                        >
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="emp.user?.avatar"
                                        :src="`/storage/${emp.user.avatar}`"
                                        :alt="emp.name"
                                        class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0"
                                    />
                                    <div
                                        v-else
                                        class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0"
                                    >
                                        {{ emp.name?.charAt(0)?.toUpperCase() || '?' }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ emp.name }}</div>
                                        <div class="text-xs text-slate-400">{{ emp.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600 font-mono text-xs">
                                {{ emp.nip || '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ emp.branch?.name || '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ emp.division?.name || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-10 text-center">
                <p class="text-sm font-medium text-slate-500">Belum ada karyawan</p>
                <p class="text-xs text-slate-400 mt-1">Saat ini belum ada karyawan yang terdaftar dengan jabatan {{ position.name }}.</p>
            </div>
        </div>
    </div>
</template>

