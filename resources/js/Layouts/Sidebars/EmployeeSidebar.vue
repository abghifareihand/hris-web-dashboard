<script setup>
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
        default: 'ADR HRIS',
    },
});

defineEmits(['close']);
</script>

<template>
    <!-- Sidebar - Employee Light Emerald Theme -->
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            desktopSidebarOpen ? 'lg:translate-x-0 lg:w-64 lg:static' : 'lg:-translate-x-full lg:w-0 lg:absolute lg:border-r-0',
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-r border-slate-200/80 transform transition-all duration-300 ease-in-out flex flex-col shadow-lg shadow-slate-200/50 overflow-hidden"
    >
        <!-- Logo Brand -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
            <Link :href="route('employee.dashboard')" class="flex items-center gap-3">
                <img src="/images/logo/logo.png" alt="Logo" class="w-10 h-10 object-contain rounded-xl shrink-0" />
                <span class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate">
                    {{ appName }}
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
            <!-- Section Label: UTAMA -->
            <div class="!mt-2 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Menu Utama</span>
            </div>

            <!-- Dashboard -->
            <Link :href="route('employee.dashboard')" class="sidebar-link-light" :class="{ 'active': route().current('employee.dashboard') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </Link>

            <!-- Pengumuman Perusahaan -->
            <Link :href="route('employee.announcements.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.announcements.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <span>Pengumuman</span>
            </Link>

            <!-- Riwayat Absensi -->
            <Link :href="route('employee.attendances.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.attendances.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Riwayat Absensi</span>
            </Link>

            <!-- Jadwal Kerja -->
            <Link :href="route('employee.schedules.work.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.schedules.work.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Jadwal Kerja</span>
            </Link>

            <!-- Tukar Shift Pribadi -->
            <Link :href="route('employee.schedules.swap-personal.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.schedules.swap-personal.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Tukar Shift Pribadi</span>
            </Link>

            <!-- Tukar Shift Tim -->
            <Link :href="route('employee.schedules.swap-team.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.schedules.swap-team.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Tukar Shift Tim</span>
            </Link>

            <!-- Section Label: PENGAJUAN & ASPIRASI -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Pengajuan & Aspirasi</span>
            </div>

            <!-- Cuti & Izin -->
            <Link :href="route('employee.leaves.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.leaves.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Cuti & Izin</span>
            </Link>

            <!-- Lembur -->
            <Link :href="route('employee.overtimes.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.overtimes.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Lembur</span>
            </Link>

            <!-- Reimbursement -->
            <Link :href="route('employee.reimbursements.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.reimbursements.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                </svg>
                <span>Klaim Biaya</span>
            </Link>

            <!-- Pinjaman -->
            <Link :href="route('employee.loans.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.loans.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Pinjaman / Kasbon</span>
            </Link>

            <!-- Kritik & Saran -->
            <Link :href="route('employee.feedbacks.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.feedbacks.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Kritik & Saran</span>
            </Link>

            <!-- Section Label: FINANSIAL -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Finansial</span>
            </div>

            <!-- Slip Gaji -->
            <Link :href="route('employee.payrolls.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.payrolls.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Slip Gaji</span>
            </Link>

            <!-- THR -->
            <Link :href="route('employee.thr.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.thr.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>THR</span>
            </Link>

        </nav>
    </aside>
</template>
