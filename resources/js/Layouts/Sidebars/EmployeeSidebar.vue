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
        default: 'HRIS Company',
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
                <div class="min-w-0">
                    <span class="block text-base font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate">
                        {{ companyName || appName }}
                    </span>
                    <span class="inline-flex items-center text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200/60 uppercase tracking-wider">
                        Portal Karyawan
                    </span>
                </div>
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

            <!-- Absensi Saya -->
            <Link :href="route('employee.attendances.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.attendances.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Absensi Saya</span>
            </Link>

            <!-- Jadwal & Shift -->
            <Link :href="route('employee.schedules.work.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.schedules.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Jadwal & Shift</span>
            </Link>

            <!-- Section Label: PENGAJUAN -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Pengajuan</span>
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
                <span>Reimbursement</span>
            </Link>

            <!-- Pinjaman -->
            <Link :href="route('employee.loans.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.loans.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Pinjaman</span>
            </Link>

            <!-- Section Label: FINANSIAL & PROFIL -->
            <div class="!mt-6 !mb-2 px-3">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Finansial & Akun</span>
            </div>

            <!-- Slip Gaji -->
            <Link :href="route('employee.payrolls.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.payrolls.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>Slip Gaji</span>
            </Link>

            <!-- Profil Saya -->
            <Link :href="route('employee.profile.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.profile.*') }">
                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profil Saya</span>
            </Link>
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
                    <span v-else>{{ authUser?.name?.charAt(0).toUpperCase() || 'K' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ authUser?.name }}</p>
                    <p class="text-[11px] text-slate-500 capitalize truncate">
                        {{ authUser?.employee?.position || 'Karyawan' }}
                    </p>
                </div>
            </div>
        </div>
    </aside>
</template>
