<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Checkbox from "@/Components/UI/Checkbox.vue";
import {
    MOBILE_HOME_MENUS,
    MOBILE_APPROVAL_MENUS,
    MOBILE_COMPANY_MENUS,
    WEB_MANAGERIAL_MENUS,
    normalizePermissions,
} from "@/data/positionPermissions";

defineOptions({
    layout: AppLayout,
});

const activePlatformTab = ref("mobile");

const props = defineProps({
    position: {
        type: Object,
        required: true,
    },
});

const permissions = computed(() => {
    return normalizePermissions(props.position.permissions);
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
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
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
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                            {{ position.name }}
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ position.employees_count ?? position.employees?.length ?? 0 }} Karyawan Aktif
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Informasi detail struktur jabatan, cakupan akses cabang/divisi, dan matriks hak akses Mobile & Web.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-auto">
                <Link :href="route('owner.management.company.positions.edit', position.id)">
                    <Button variant="primary" size="md">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Jabatan
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Cakupan Akses Data (Cabang & Divisi) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Akses Cabang -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Akses Cabang</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Cabang yang dapat diakses jabatan ini</p>
                        </div>
                    </div>
                    <span v-if="position.branches && position.branches.length > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                        {{ position.branches.length }} Cabang
                    </span>
                    <span v-else class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                        Tidak ada akses
                    </span>
                </div>
                <div class="p-6 flex-1">
                    <div v-if="position.branches && position.branches.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="branch in position.branches"
                            :key="branch.id"
                            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-800 shadow-2xs"
                        >
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></div>
                            <span class="text-sm font-semibold truncate">{{ branch.name }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-8 px-4 text-center rounded-xl bg-slate-50/70 border border-dashed border-slate-200"
                    >
                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <p class="text-sm font-medium text-slate-600">Tidak ada akses cabang</p>
                        <p class="text-xs text-slate-400 mt-0.5">Jabatan ini tidak diberikan akses ke cabang mana pun.</p>
                    </div>
                </div>
            </div>

            <!-- Akses Divisi -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Akses Divisi</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Divisi yang dapat diakses jabatan ini</p>
                        </div>
                    </div>
                    <span v-if="position.divisions && position.divisions.length > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                        {{ position.divisions.length }} Divisi
                    </span>
                    <span v-else class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                        Tidak ada akses
                    </span>
                </div>
                <div class="p-6 flex-1">
                    <div v-if="position.divisions && position.divisions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="division in position.divisions"
                            :key="division.id"
                            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-800 shadow-2xs"
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

        <!-- Hak Akses Menu - Read Only (2-Tab Platform Architecture) -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <div class="mb-2">
                <h3 class="text-xs font-semibold text-slate-700">
                    Hak Akses Menu & Fitur
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Daftar hak akses menu yang aktif untuk jabatan ini (Read-Only).
                </p>
            </div>

            <!-- Platform Switcher Tabs -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-lg w-fit border border-slate-200/80 mb-5">
                <button
                    type="button"
                    @click="activePlatformTab = 'mobile'"
                    class="flex items-center gap-2 px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors duration-150 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none select-none border"
                    :class="activePlatformTab === 'mobile' ? 'bg-white text-emerald-700 shadow-xs border-slate-200/80 font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent'"
                >
                    <span class="text-base leading-none">📱</span>
                    <span>Mobile App Karyawan</span>
                </button>
                <button
                    type="button"
                    @click="activePlatformTab = 'web'"
                    class="flex items-center gap-2 px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors duration-150 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none select-none border"
                    :class="activePlatformTab === 'web' ? 'bg-white text-emerald-700 shadow-xs border-slate-200/80 font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent'"
                >
                    <span class="text-base leading-none">💻</span>
                    <span>Web Dashboard Karyawan</span>
                </button>
            </div>

            <!-- ============================================================= -->
            <!-- TAB 1: 📱 MOBILE APP KARYAWAN -->
            <!-- ============================================================= -->
            <div v-show="activePlatformTab === 'mobile'" class="space-y-6">
                <!-- Sub-grup 1: Menu Home - Diri Sendiri (ESS) -->
                <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                    1. Menu Home — Fitur Karyawan (12 Menu)
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Grid Home Mobile
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Menu di halaman utama aplikasi karyawan untuk mengurus data pribadi karyawan itu sendiri.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4 min-w-[300px]">Menu Diri Sendiri & Fungsi</th>
                                    <th class="py-3 px-3 text-center w-24">View</th>
                                    <th class="py-3 px-3 text-center w-24">Create</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="item in MOBILE_HOME_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-800 leading-snug">
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            {{ item.desc }}
                                        </div>
                                    </td>
                                    <!-- View Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-home-${item.key}-view`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.view)"
                                                :disabled="true"
                                            />
                                        </div>
                                    </td>
                                    <!-- Create Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="item.hasCreate" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-home-${item.key}-create`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.create)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">
                                            —
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sub-grup 2: Menu Home - Persetujuan Pengajuan (Approval) -->
                <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                    2. Menu Home — Persetujuan & Kelola Karyawan
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Approval & Kelola Karyawan
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Persetujuan pengajuan staf serta pengelolaan presensi, laporan harian, dan kuota sisa cuti karyawan langsung dari Home Mobile.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4 min-w-[300px]">Modul Persetujuan & Kelola Karyawan</th>
                                    <th class="py-3 px-3 text-center w-24">Edit</th>
                                    <th class="py-3 px-3 text-center w-24">Delete</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="item in MOBILE_APPROVAL_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-800 leading-snug">
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            {{ item.desc }}
                                        </div>
                                    </td>
                                    <!-- Approval Edit Checkbox -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-approval-${item.key}-edit`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.edit)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Approval Delete Checkbox -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-approval-${item.key}-delete`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.delete)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sub-grup 3: Tab Perusahaan Mobile -->
                <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                    3. Tab Perusahaan — 12 Menu Kantor di Mobile
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    Bottom Navbar Perusahaan
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Menu operasional kantor yang tampil pada tab 'Perusahaan' di bagian bawah aplikasi karyawan.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4 min-w-[280px]">Menu Tab Perusahaan</th>
                                    <th class="py-3 px-3 text-center w-24">View</th>
                                    <th class="py-3 px-3 text-center w-24">Create</th>
                                    <th class="py-3 px-3 text-center w-24">Edit</th>
                                    <th class="py-3 px-3 text-center w-24">Delete</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="item in MOBILE_COMPANY_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-800 leading-snug">
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            {{ item.desc }}
                                        </div>
                                    </td>
                                    <!-- View Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.view" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-company-${item.key}-view`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.view)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Create Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.create" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-company-${item.key}-create`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.create)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Edit Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-company-${item.key}-edit`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.edit)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Delete Action -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-mobile-company-${item.key}-delete`"
                                                :modelValue="Boolean(permissions.mobile?.[item.key]?.delete)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- TAB 2: 💻 WEB DASHBOARD KARYAWAN -->
            <!-- ============================================================= -->
            <div v-show="activePlatformTab === 'web'" class="space-y-6">
                <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                Hak Akses Web Dashboard (Khusus Manajerial Laptop/PC)
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                Sidebar Web
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Hak akses manajerial saat karyawan dengan jabatan ini login ke Web Dashboard di komputer. Murni untuk operasional perusahaan dan pengawasan tim (tidak ada menu diri sendiri).
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4 min-w-[320px]">Menu Manajerial Web</th>
                                    <th class="py-3 px-3 text-center w-24">View</th>
                                    <th class="py-3 px-3 text-center w-24">Create</th>
                                    <th class="py-3 px-3 text-center w-24">Edit</th>
                                    <th class="py-3 px-3 text-center w-24">Delete</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="item in WEB_MANAGERIAL_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-800 leading-snug">
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            {{ item.desc }}
                                        </div>
                                    </td>
                                    <!-- View -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.view" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-web-${item.key}-view`"
                                                :modelValue="Boolean(permissions.web?.[item.key]?.view)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Create -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.create" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-web-${item.key}-create`"
                                                :modelValue="Boolean(permissions.web?.[item.key]?.create)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Edit -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-web-${item.key}-edit`"
                                                :modelValue="Boolean(permissions.web?.[item.key]?.edit)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                    <!-- Delete -->
                                    <td class="py-3 px-3 text-center align-middle">
                                        <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                            <Checkbox
                                                :id="`show-web-${item.key}-delete`"
                                                :modelValue="Boolean(permissions.web?.[item.key]?.delete)"
                                                :disabled="true"
                                            />
                                        </div>
                                        <div v-else class="text-slate-300 text-center text-xs">—</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Karyawan Pemegang Jabatan -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
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
