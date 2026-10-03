<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const companyName = computed(() => authUser.value?.company?.name || authUser.value?.company?.name_company || '');
const unreadNotificationsCount = computed(() => page.props.auth?.unreadNotificationsCount || 0);
const appName = computed(() => page.props.appName || 'HRIS Company');
const flash = computed(() => page.props.flash || {});

// Toast Notification State & Auto-Dismiss Logic
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
let toastTimer = null;
let startTime = 0;
let remainingTime = 4000;
const progress = ref(100);
let progressInterval = null;

const startProgress = (duration) => {
    progress.value = 100;
    if (progressInterval) clearInterval(progressInterval);
    const intervalTime = 50;
    const step = (intervalTime / duration) * 100;
    progressInterval = setInterval(() => {
        progress.value = Math.max(0, progress.value - step);
        if (progress.value <= 0) {
            clearInterval(progressInterval);
        }
    }, intervalTime);
};

const triggerToast = (msg, type = 'success') => {
    if (!msg) return;
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    remainingTime = 4000;
    startTime = Date.now();

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, remainingTime);

    startProgress(remainingTime);
};

const dismissToast = () => {
    showToast.value = false;
    if (toastTimer) clearTimeout(toastTimer);
    if (progressInterval) clearInterval(progressInterval);
};

const pauseToast = () => {
    if (toastTimer) {
        clearTimeout(toastTimer);
        remainingTime = Math.max(500, remainingTime - (Date.now() - startTime));
    }
    if (progressInterval) clearInterval(progressInterval);
};

const resumeToast = () => {
    if (!showToast.value || remainingTime <= 0) return;
    startTime = Date.now();
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, remainingTime);
    startProgress(remainingTime);
};

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            triggerToast(newFlash.success, 'success');
        } else if (newFlash?.error) {
            triggerToast(newFlash.error, 'error');
        } else if (newFlash?.warning) {
            triggerToast(newFlash.warning, 'warning');
        } else if (newFlash?.info) {
            triggerToast(newFlash.info, 'info');
        }
    },
    { deep: true, immediate: true }
);

const sidebarOpen = ref(false);
const desktopSidebarOpen = ref(true);
const isUserDropdownOpen = ref(false);
const userDropdownRef = ref(null);

const handleGlobalClick = (event) => {
    if (isUserDropdownOpen.value && userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
        isUserDropdownOpen.value = false;
    }
};

const handleGlobalKeydown = (event) => {
    if (event.key === 'Escape' && isUserDropdownOpen.value) {
        isUserDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleGlobalClick);
    document.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    if (toastTimer) clearTimeout(toastTimer);
    if (progressInterval) clearInterval(progressInterval);
    document.removeEventListener('click', handleGlobalClick);
    document.removeEventListener('keydown', handleGlobalKeydown);
});

// Accordion states for owner sidebar
const accordions = ref({
    karyawan: false,
    perusahaan: false,
    penjadwalan: false,
    kehadiran: false,
    cuti: false,
    lembur: false,
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

const logout = () => {
    if (authUser.value?.role === 'admin') {
        router.post(route('admin.logout'));
    } else {
        router.post(route('logout'));
    }
};
</script>

<template>
    <div class="h-screen flex overflow-hidden font-sans antialiased bg-slate-50 text-slate-800">
        <!-- Toast / Flash Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-x-8 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transform ease-in duration-200 transition"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-8 opacity-0"
        >
            <div
                v-if="showToast"
                @mouseenter="pauseToast"
                @mouseleave="resumeToast"
                class="fixed top-20 right-5 z-[9999] max-w-sm w-full sm:w-[380px] bg-white shadow-2xl shadow-slate-900/10 rounded-2xl border border-slate-200/90 overflow-hidden transition-all pointer-events-auto"
            >
                <div class="p-4 flex items-start gap-3">
                    <!-- Icon -->
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                        :class="{
                            'bg-emerald-100 text-emerald-600': toastType === 'success',
                            'bg-rose-100 text-rose-600': toastType === 'error',
                            'bg-amber-100 text-amber-600': toastType === 'warning',
                            'bg-sky-100 text-sky-600': toastType === 'info',
                        }"
                    >
                        <!-- Success Checkmark -->
                        <svg v-if="toastType === 'success'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <!-- Error Exclamation -->
                        <svg v-else-if="toastType === 'error'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <!-- Warning Triangle -->
                        <svg v-else-if="toastType === 'warning'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        <!-- Info -->
                        <svg v-else class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p
                            class="text-xs font-bold uppercase tracking-wider mb-0.5"
                            :class="{
                                'text-emerald-700': toastType === 'success',
                                'text-rose-700': toastType === 'error',
                                'text-amber-700': toastType === 'warning',
                                'text-sky-700': toastType === 'info',
                            }"
                        >
                            {{ toastType === 'success' ? 'Berhasil' : (toastType === 'error' ? 'Gagal' : (toastType === 'warning' ? 'Perhatian' : 'Informasi')) }}
                        </p>
                        <p class="text-sm font-medium text-slate-700 leading-snug">
                            {{ toastMessage }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="dismissToast"
                        class="p-1 -mr-1 -mt-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition cursor-pointer"
                        title="Tutup Notifikasi"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Progress Bar Indicator -->
                <div class="h-1 w-full bg-slate-100/80">
                    <div
                        class="h-full transition-all duration-75"
                        :style="{ width: `${progress}%` }"
                        :class="{
                            'bg-emerald-500': toastType === 'success',
                            'bg-rose-500': toastType === 'error',
                            'bg-amber-500': toastType === 'warning',
                            'bg-sky-500': toastType === 'info',
                        }"
                    />
                </div>
            </div>
        </transition>

        <!-- Sidebar Overlay (Mobile) -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden transition-opacity"
        />

        <!-- Sidebar - Light Theme with Subtle Gradient (1:1 with owner.blade.php) -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                desktopSidebarOpen ? 'lg:translate-x-0 lg:w-64 lg:static' : 'lg:-translate-x-full lg:w-0 lg:absolute lg:border-r-0',
            ]"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-50 via-white to-slate-50 border-r border-slate-200/80 transform transition-all duration-300 ease-in-out flex flex-col shadow-lg shadow-slate-200/50 overflow-hidden"
        >
            <!-- Logo Brand -->
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
                <Link :href="authUser?.role === 'admin' ? route('admin.dashboard') : (authUser?.role === 'employee' ? route('employee.dashboard') : route('owner.dashboard'))" class="flex items-center gap-3">
                    <img src="/images/logo/logo.png" alt="Logo" class="w-10 h-10 object-contain rounded-xl shrink-0" />
                    <span class="text-lg font-bold bg-gradient-to-r from-emerald-600 to-emerald-500 bg-clip-text text-transparent truncate">
                        {{ authUser?.company?.name || appName }}
                    </span>
                </Link>

                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="sidebar-nav flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <!-- OWNER ROLE MENU -->
                <template v-if="authUser?.role === 'owner'">
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
                            <Link :href="route('owner.management.schedules.swap-personal.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.swap-personal.*') }">Tukar Jadwal Pribadi</Link>
                            <Link :href="route('owner.management.schedules.swap-team.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.swap-team.*') }">Tukar Jadwal Tim</Link>
                            <Link :href="route('owner.management.schedules.work.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.schedules.work.*') }">Jadwal Kerja</Link>
                        </div>
                    </div>

                    <!-- Kehadiran (Settings) -->
                    <div>
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
                            <Link :href="route('owner.management.attendance.settings.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.attendance.settings.*') }">Pengaturan Presensi</Link>
                        </div>
                    </div>

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
                                <span>Cuti & Izin</span>
                            </span>
                            <svg :class="accordions.cuti ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="accordions.cuti" class="mt-1 sidebar-submenu-light">
                            <Link :href="route('owner.management.leaves.pending.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.pending.*') }">Menunggu Persetujuan</Link>
                            <Link :href="route('owner.management.leaves.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.index') }">Riwayat Cuti</Link>
                            <Link :href="route('owner.management.leaves.balance.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.leaves.balance.*') }">Kuota & Saldo</Link>
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
                            <Link :href="route('owner.management.overtimes.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.management.overtimes.index') }">Riwayat Lembur</Link>
                        </div>
                    </div>

                    <!-- Section Label: KEUANGAN -->
                    <div class="!mt-6 !mb-2 px-3">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Keuangan</span>
                    </div>

                    <!-- Penggajian -->
                    <div>
                        <button
                            type="button"
                            @click="toggleAccordion('penggajian')"
                            class="sidebar-link-light w-full justify-between"
                            :class="{ 'active-parent': route().current('owner.finance.payrolls.*') }"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>Penggajian</span>
                            </span>
                            <svg :class="accordions.penggajian ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="accordions.penggajian" class="mt-1 sidebar-submenu-light">
                            <Link :href="route('owner.finance.payrolls.master.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.payrolls.master.*') }">Master Gaji</Link>
                            <Link :href="route('owner.finance.payrolls.index')" class="sidebar-sublink-light" :class="{ 'active': route().current('owner.finance.payrolls.index') }">Data Penggajian</Link>
                        </div>
                    </div>

                    <!-- Pajak & BPJS -->
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

                    <!-- THR -->
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

                    <!-- Pinjaman -->
                    <Link :href="route('owner.finance.loans.index')" class="sidebar-link-light" :class="{ 'active': route().current('owner.finance.loans.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Pinjaman</span>
                    </Link>

                    <!-- Reimbursement -->
                    <Link :href="route('owner.finance.reimbursements.index')" class="sidebar-link-light" :class="{ 'active': route().current('owner.finance.reimbursements.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                        <span>Reimbursement</span>
                    </Link>

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
                </template>

                <!-- EMPLOYEE ROLE MENU -->
                <template v-else-if="authUser?.role === 'employee'">
                    <Link :href="route('employee.dashboard')" class="sidebar-link-light" :class="{ 'active': route().current('employee.dashboard') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </Link>
                    <Link :href="route('employee.attendances.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.attendances.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Absensi Saya</span>
                    </Link>
                    <Link :href="route('employee.schedules.work.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.schedules.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Jadwal & Shift</span>
                    </Link>
                    <Link :href="route('employee.leaves.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.leaves.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Cuti & Izin</span>
                    </Link>
                    <Link :href="route('employee.overtimes.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.overtimes.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Lembur</span>
                    </Link>
                    <Link :href="route('employee.payrolls.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.payrolls.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Slip Gaji</span>
                    </Link>
                    <Link :href="route('employee.reimbursements.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.reimbursements.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                        <span>Reimbursement</span>
                    </Link>
                    <Link :href="route('employee.loans.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.loans.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Pinjaman</span>
                    </Link>
                    <Link :href="route('employee.profile.index')" class="sidebar-link-light" :class="{ 'active': route().current('employee.profile.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Profil Saya</span>
                    </Link>
                </template>

                <!-- ADMIN ROLE MENU -->
                <template v-else-if="authUser?.role === 'admin'">
                    <Link :href="route('admin.dashboard')" class="sidebar-link-light" :class="{ 'active': route().current('admin.dashboard') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </Link>
                    <Link :href="route('admin.companies.index')" class="sidebar-link-light" :class="{ 'active': route().current('admin.companies.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Perusahaan</span>
                    </Link>
                    <Link :href="route('admin.plans.index')" class="sidebar-link-light" :class="{ 'active': route().current('admin.plans.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Paket Langganan</span>
                    </Link>
                    <Link :href="route('admin.transactions.index')" class="sidebar-link-light" :class="{ 'active': route().current('admin.transactions.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Transaksi</span>
                    </Link>
                    <Link :href="route('admin.settings.index')" class="sidebar-link-light" :class="{ 'active': route().current('admin.settings.*') }">
                        <svg class="sidebar-icon-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                        <span>Pengaturan Platform</span>
                    </Link>
                </template>
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
                        <p class="text-[11px] text-slate-500 capitalize truncate">{{ authUser?.role }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-100/70 relative">
            <!-- Top Header (1:1 with owner.blade.php) -->
            <header class="h-16 flex-shrink-0 bg-white/70 backdrop-blur-md border-b border-slate-200/60 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30 shadow-xs transition-all duration-300">
                <!-- Left: Toggle & Breadcrumb -->
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden text-slate-500 hover:text-slate-700 p-1 rounded-lg cursor-pointer"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="desktopSidebarOpen = !desktopSidebarOpen"
                        class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                        title="Toggle Sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>

                    <div class="flex items-center gap-2 text-sm text-slate-700 font-medium">
                        <slot name="header">
                            <span>Dashboard</span>
                        </slot>
                    </div>
                </div>

                <!-- Right: Company Badge + Notifications + Profile (1:1 with Blade) -->
                <div class="flex items-center gap-3">
                    <!-- Company Name Badge -->
                    <div
                        v-if="companyName"
                        class="hidden md:flex items-center gap-2 px-3 py-1.5 border border-red-100 bg-red-100 rounded-lg"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="text-sm font-medium text-red-700">{{ companyName }}</span>
                    </div>

                    <!-- Notifications -->
                    <Link
                        v-if="authUser?.role === 'owner'"
                        :href="route('owner.notifications.index')"
                        class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                        title="Notifikasi"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            v-if="unreadNotificationsCount > 0"
                            class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full"
                        ></span>
                    </Link>

                    <!-- User Profile Dropdown -->
                    <div ref="userDropdownRef" class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer select-none"
                        >
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-semibold overflow-hidden shadow-xs"
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
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-slate-900 leading-tight">{{ authUser?.name || 'Company User' }}</p>
                                <p class="text-xs text-slate-500 capitalize leading-tight mt-0.5">{{ authUser?.role || 'HRD' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu Panel (Exact Blade classes & width w-72) -->
                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-1"
                        >
                            <div
                                v-if="isUserDropdownOpen"
                                class="dropdown-menu w-72 right-0 z-50 text-left origin-top-right"
                            >
                                <!-- User Info Header -->
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <p class="text-sm font-semibold text-slate-900">{{ authUser?.name || 'Company User' }}</p>
                                    <p class="text-xs text-slate-500">{{ authUser?.email || '' }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            v-if="companyName"
                                            class="inline-flex items-center px-2 py-0.5 bg-red-50 text-red-700 text-xs font-medium rounded"
                                        >
                                            {{ companyName }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-medium rounded capitalize">
                                            {{ authUser?.role || 'Owner' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Menu Links -->
                                <div class="py-1">
                                    <Link
                                        v-if="authUser?.role === 'owner'"
                                        :href="route('owner.my-profile.index')"
                                        @click="isUserDropdownOpen = false"
                                        class="dropdown-item"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Profil Saya</span>
                                    </Link>
                                    <Link
                                        v-else-if="authUser?.role === 'employee'"
                                        :href="route('employee.profile.index')"
                                        @click="isUserDropdownOpen = false"
                                        class="dropdown-item"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Profil Saya</span>
                                    </Link>
                                </div>

                                <div class="dropdown-divider"></div>

                                <div class="py-1">
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="dropdown-item dropdown-item-danger w-full text-left cursor-pointer"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-scroll p-4 sm:p-6 lg:p-8 space-y-6">
                <slot />
            </main>
        </div>
    </div>
</template>
