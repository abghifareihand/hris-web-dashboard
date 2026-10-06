<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    sidebarOpen: Boolean,
    desktopSidebarOpen: {
        type: Boolean,
        default: true,
    },
    authUser: Object,
    companyName: String,
    appName: {
        type: String,
        default: 'HRIS Company',
    },
});

defineEmits(['close']);

// Accordion states for owner sidebar
const accordions = ref({
    karyawan: false,
    perusahaan: false,
    penjadwalan: false,
    kehadiran: false,
    cuti: false,
    lembur: false,
    klaimBiaya: false,
    pinjaman: false,
    penggajian: false,
    pajak: false,
    thr: false,
    laporanKehadiran: false,
    laporanKinerja: false,
    pengumuman: false,
});

// Auto-expand active group
const initAccordions = () => {
    if (route().current('owner.management.employees.*')) accordions.value.karyawan = true;
    if (route().current('owner.management.company.*')) accordions.value.perusahaan = true;
    if (route().current('owner.management.schedules.*')) accordions.value.penjadwalan = true;
    if (route().current('owner.management.attendance.*')) accordions.value.kehadiran = true;
    if (route().current('owner.management.leaves.*')) accordions.value.cuti = true;
    if (route().current('owner.management.overtimes.*')) accordions.value.lembur = true;
    if (route().current('owner.finance.reimbursements.*')) accordions.value.klaimBiaya = true;
    if (route().current('owner.finance.loans.*')) accordions.value.pinjaman = true;
    if (route().current('owner.finance.payrolls.*')) accordions.value.penggajian = true;
    if (route().current('owner.finance.taxes.*')) accordions.value.pajak = true;
    if (route().current('owner.finance.thr.*')) accordions.value.thr = true;
    if (route().current('owner.reports.attendances.*')) accordions.value.laporanKehadiran = true;
    if (route().current('owner.reports.performances.*')) accordions.value.laporanKinerja = true;
    if (route().current('owner.announcements.*')) accordions.value.pengumuman = true;
};
initAccordions();

const toggleAccordion = (key) => {
    accordions.value[key] = !accordions.value[key];
};
</script>

<template>
    <!-- Sidebar - Owner Light Emerald Theme (1:1 with owner.blade.php) -->
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            desktopSidebarOpen ? 'lg:translate-x-0 lg:w-64 lg:static' : 'lg:-translate-x-full lg:w-0 lg:absolute lg:border-r-0',
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-r border-slate-200/80 transform transition-all duration-300 ease-in-out flex flex-col shadow-lg shadow-slate-200/50 overflow-hidden"
    >
        <!-- Logo Brand -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
            <Link :href="route('owner.dashboard')" class="flex items-center gap-3">
                <img src="/images/logo/logo.png" alt="Logo" class="w-10 h-10 object-contain rounded-xl shrink-0" />
                <span class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate">
                    {{ companyName || appName }}
                </span>
            </Link>

            <button @click="$emit('close')" class="lg:hidden text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="sidebar-nav flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <!-- Dashboard -->
            <Link
                :href="route('owner.dashboard')"
                class="sidebar-link-light"
                :class="{ 'active': route().current('owner.dashboard') }"
            >
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Dashboard</span>
            </Link>

            <!-- Section Label: MANAJEMEN -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Manajemen</span>
            </div>

            <!-- Karyawan -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('karyawan')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.employees.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Karyawan</span>
                    </span>
                    <svg :class="accordions.karyawan ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.karyawan" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.employees.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.employees.pending.*') }">Menunggu Persetujuan</Link>
                    <Link :href="route('owner.management.employees.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.employees.index') }">Daftar Karyawan</Link>
                    <Link :href="route('owner.management.employees.create')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.employees.create') }">Tambah Karyawan</Link>
                </div>
            </div>

            <!-- Perusahaan -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('perusahaan')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.company.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Perusahaan</span>
                    </span>
                    <svg :class="accordions.perusahaan ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.perusahaan" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.company.profile.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.company.profile.*') }">Informasi Perusahaan</Link>
                    <Link :href="route('owner.management.company.branches.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.company.branches.*') }">Cabang</Link>
                    <Link :href="route('owner.management.company.divisions.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.company.divisions.*') }">Divisi</Link>
                    <Link :href="route('owner.management.company.positions.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.company.positions.*') }">Jabatan</Link>
                </div>
            </div>

            <!-- Penjadwalan -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('penjadwalan')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.schedules.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Penjadwalan</span>
                    </span>
                    <svg :class="accordions.penjadwalan ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.penjadwalan" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.schedules.shifts.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.shifts.*') }">Shift Kerja</Link>
                    <Link :href="route('owner.management.schedules.holidays.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.holidays.*') }">Hari Libur</Link>
                    <Link :href="route('owner.management.schedules.work.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.work.*') }">Jadwal Kerja</Link>
                    <Link :href="route('owner.management.schedules.swap-personal.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.swap-personal.*') }">Tukar Jadwal Pribadi</Link>
                    <Link :href="route('owner.management.schedules.swap-team.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.swap-team.*') }">Tukar Jadwal Tim</Link>
                </div>
            </div>

            <!-- Kehadiran -->
            <!-- <div>
                <button
                    type="button"
                    @click="toggleAccordion('kehadiran')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.attendance.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Kehadiran</span>
                    </span>
                    <svg :class="accordions.kehadiran ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.kehadiran" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.attendance.settings.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.attendance.settings.*') }">Pengaturan Absensi</Link>
                </div>
            </div> -->

            <!-- Cuti & Izin -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('cuti')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.leaves.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Cuti</span>
                    </span>
                    <svg :class="accordions.cuti ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.cuti" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.leaves.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.pending.*') }">Menunggu Persetujuan</Link>
                    <Link :href="route('owner.management.leaves.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.index') }">Data Cuti</Link>
                    <Link :href="route('owner.management.leaves.balance.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.balance.*') }">Sisa Cuti</Link>
                    <Link :href="route('owner.management.leaves.categories.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.categories.*') }">Kategori Cuti</Link>
                </div>
            </div>

            <!-- Lembur -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('lembur')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.management.overtimes.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Lembur</span>
                    </span>
                    <svg :class="accordions.lembur ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.lembur" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.management.overtimes.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.overtimes.pending.*') }">Menunggu Persetujuan</Link>
                    <Link :href="route('owner.management.overtimes.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.overtimes.index') }">Data Lembur</Link>
                </div>
            </div>

            <!-- Section Label: KEUANGAN -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Keuangan</span>
            </div>

            <!-- 1. Klaim Biaya (Reimbursement) -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('klaimBiaya')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.finance.reimbursements.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Klaim Biaya</span>
                    </span>
                    <svg :class="accordions.klaimBiaya ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.klaimBiaya" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.finance.reimbursements.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.reimbursements.pending.*') }">Menunggu Persetujuan</Link>
                    <Link :href="route('owner.finance.reimbursements.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.reimbursements.index') || route().current('owner.finance.reimbursements.create') }">Data Klaim Biaya</Link>
                </div>
            </div>

            <!-- 2. Pinjaman (Loans) -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('pinjaman')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.finance.loans.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Pinjaman</span>
                    </span>
                    <svg :class="accordions.pinjaman ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.pinjaman" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.finance.loans.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.loans.pending.*') }">Menunggu Persetujuan</Link>
                    <Link :href="route('owner.finance.loans.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.loans.index') || route().current('owner.finance.loans.show') || route().current('owner.finance.loans.create') }">Data Pinjaman</Link>
                </div>
            </div>

            <!-- 3. Penggajian (Payrolls) -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('penggajian')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.finance.payrolls.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Penggajian</span>
                    </span>
                    <svg :class="accordions.penggajian ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.penggajian" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.finance.payrolls.master.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.payrolls.master.*') }">Master Gaji</Link>
                    <Link :href="route('owner.finance.payrolls.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.payrolls.index') || route().current('owner.finance.payrolls.show') || route().current('owner.finance.payrolls.item*') }">Data Penggajian</Link>
                </div>
            </div>

            <!-- 4. Pajak & BPJS (Taxes) -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('pajak')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.finance.taxes.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Pajak & BPJS</span>
                    </span>
                    <svg :class="accordions.pajak ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.pajak" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.finance.taxes.pph21')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.taxes.pph21*') }">PPh 21</Link>
                    <Link :href="route('owner.finance.taxes.bpjs-tk')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.taxes.bpjs-tk*') }">BPJS Ketenagakerjaan</Link>
                    <Link :href="route('owner.finance.taxes.bpjs-kes')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.taxes.bpjs-kes*') }">BPJS Kesehatan</Link>
                </div>
            </div>

            <!-- 5. THR -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('thr')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.finance.thr.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>THR</span>
                    </span>
                    <svg :class="accordions.thr ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.thr" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.finance.thr.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.thr.index') || route().current('owner.finance.thr.create') }">Data THR</Link>
                    <Link :href="route('owner.finance.thr.settings.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.thr.settings.*') }">Pengaturan</Link>
                </div>
            </div>

            <!-- Section Label: LAPORAN -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Laporan</span>
            </div>

            <!-- Laporan Kehadiran -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('laporanKehadiran')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.reports.attendances.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Kehadiran</span>
                    </span>
                    <svg :class="accordions.laporanKehadiran ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.laporanKehadiran" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.reports.attendances.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.attendances.index') }">Laporan Kehadiran</Link>
                    <Link :href="route('owner.reports.attendances.recap.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.attendances.recap.*') }">Rekapitulasi</Link>
                    <Link :href="route('owner.reports.attendances.overtime-recap.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.attendances.overtime-recap.*') }">Lembur</Link>
                    <Link :href="route('owner.reports.attendances.rate.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.attendances.rate.*') }">Tingkat Kehadiran</Link>
                </div>
            </div>

            <!-- Laporan Kinerja -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('laporanKinerja')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.reports.performances.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Kinerja</span>
                    </span>
                    <svg :class="accordions.laporanKinerja ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.laporanKinerja" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.reports.performances.daily.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.performances.daily.*') }">Laporan Harian</Link>
                    <Link :href="route('owner.reports.performances.employee.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.performances.employee.*') }">Karyawan</Link>
                    <Link :href="route('owner.reports.performances.branch.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.reports.performances.branch.*') }">Cabang</Link>
                </div>
            </div>

            <!-- Section Label: KOMUNIKASI -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Komunikasi</span>
            </div>

            <!-- Pengumuman -->
            <div>
                <button
                    type="button"
                    @click="toggleAccordion('pengumuman')"
                    class="sidebar-link-light w-full justify-between"
                    :class="{ 'active-parent': route().current('owner.announcements.*') }"
                >
                    <span class="flex items-center gap-3">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        <span>Pengumuman</span>
                    </span>
                    <svg :class="accordions.pengumuman ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div v-show="accordions.pengumuman" class="mt-1 sidebar-submenu-light">
                    <Link :href="route('owner.announcements.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.announcements.index') }">Data Pengumuman</Link>
                    <Link :href="route('owner.announcements.create')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.announcements.create') }">Tambah Pengumuman</Link>
                </div>
            </div>
        </nav>

        <!-- Sidebar Bottom User Section -->
        <div class="p-3 border-t border-slate-100 bg-white/50 shrink-0">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-50 border border-slate-100">
                <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold overflow-hidden shadow-xs shrink-0"
                    :class="authUser?.avatar ? 'bg-white border border-slate-200' : 'bg-emerald-600 text-white'"
                >
                    <img
                        v-if="authUser?.avatar"
                        :src="'/storage/' + authUser.avatar"
                        :alt="authUser.name"
                        class="w-full h-full object-cover"
                    />
                    <span v-else>{{ authUser?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ authUser?.name }}</p>
                    <p class="text-[11px] text-slate-500 capitalize truncate">Owner / HRD</p>
                </div>
            </div>
        </div>
    </aside>
</template>
