<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Chart from 'chart.js/auto';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    totalEmployees: { type: Number, default: 0 },
    newEmployeesThisMonth: { type: Number, default: 0 },
    todayPresentCount: { type: Number, default: 0 },
    attendanceRate: { type: Number, default: 0 },
    pendingLeavesCount: { type: Number, default: 0 },
    formattedPayroll: { type: String, default: 'Rp 0' },
    chartData: { type: Object, default: () => ({}) },
    stats30Days: { type: Object, default: () => ({}) },
    todayAttendanceBreakdown: { type: Object, default: () => ({}) },
    recentClockIns: { type: Array, default: () => [] },
    recentEmployees: { type: Array, default: () => [] },
    birthdaysToday: { type: Array, default: () => [] },
    leavesToday: { type: Array, default: () => [] },
    pendingApprovals: { type: Object, default: () => ({}) },
    totalPendingApprovals: { type: Number, default: 0 },
    activeAnnouncements: { type: Array, default: () => [] },
});

const attendanceCanvas = ref(null);
const departmentCanvas = ref(null);
const payrollCanvas = ref(null);
const leaveCanvas = ref(null);
const lateCanvas = ref(null);

onMounted(() => {
    // 1. Attendance Chart (Line)
    if (attendanceCanvas.value && props.chartData?.attendance) {
        new Chart(attendanceCanvas.value, {
            type: 'line',
            data: {
                labels: props.chartData.attendance.labels || [],
                datasets: [
                    {
                        label: 'Hadir',
                        data: props.chartData.attendance.data_hadir || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Terlambat',
                        data: props.chartData.attendance.data_terlambat || [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 8 },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }

    // 2. Department Chart (Doughnut)
    if (departmentCanvas.value && props.chartData?.department) {
        new Chart(departmentCanvas.value, {
            type: 'doughnut',
            data: {
                labels: props.chartData.department.labels || [],
                datasets: [
                    {
                        data: props.chartData.department.data || [],
                        backgroundColor: props.chartData.department.colors || ['#10b981'],
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 6, font: { size: 11 } },
                    },
                },
                cutout: '70%',
            },
        });
    }

    // 3. Payroll Chart (Bar)
    if (payrollCanvas.value && props.chartData?.payroll) {
        new Chart(payrollCanvas.value, {
            type: 'bar',
            data: {
                labels: props.chartData.payroll.labels || [],
                datasets: [
                    {
                        label: 'Gaji (Juta Rp)',
                        data: props.chartData.payroll.data || [],
                        backgroundColor: '#059669',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => 'Rp ' + v + 'M',
                        },
                    },
                },
            },
        });
    }

    // 4. Leave Chart (Doughnut)
    if (leaveCanvas.value && props.chartData?.leave) {
        new Chart(leaveCanvas.value, {
            type: 'doughnut',
            data: {
                labels: props.chartData.leave.labels || [],
                datasets: [
                    {
                        data: props.chartData.leave.data || [],
                        backgroundColor: props.chartData.leave.colors || ['#10b981', '#f59e0b'],
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 6, font: { size: 11 } },
                    },
                },
                cutout: '70%',
            },
        });
    }

    // 5. Late Chart (Doughnut)
    if (lateCanvas.value && props.chartData?.late) {
        new Chart(lateCanvas.value, {
            type: 'doughnut',
            data: {
                labels: props.chartData.late.labels || [],
                datasets: [
                    {
                        data: props.chartData.late.data || [],
                        backgroundColor: props.chartData.late.colors || ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 6, font: { size: 11 } },
                    },
                },
                cutout: '65%',
            },
        });
    }
});
</script>

<template>
    <Head title="Dashboard - HRIS Web App" />

    <div class="space-y-6">
        <!-- Action Required Banner (Menunggu Persetujuan) -->
        <div
            v-if="totalPendingApprovals > 0"
            class="p-4 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm animate-pulse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-950">Menunggu Persetujuan Anda</h3>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Terdapat <span class="font-bold">{{ totalPendingApprovals }} pengajuan</span> yang memerlukan tinjauan dan konfirmasi Anda segera.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <Link
                    v-if="pendingApprovals.employees > 0"
                    :href="route('owner.management.employees.pending.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/50 text-amber-900 border border-amber-300 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                >
                    <span>Pendaftar Karyawan</span>
                    <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]">{{ pendingApprovals.employees }}</span>
                </Link>
                <Link
                    v-if="pendingApprovals.leaves > 0"
                    :href="route('owner.management.leaves.pending.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/50 text-amber-900 border border-amber-300 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                >
                    <span>Cuti</span>
                    <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]">{{ pendingApprovals.leaves }}</span>
                </Link>
                <Link
                    v-if="pendingApprovals.overtimes > 0"
                    :href="route('owner.management.overtimes.pending.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/50 text-amber-900 border border-amber-300 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                >
                    <span>Lembur</span>
                    <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]">{{ pendingApprovals.overtimes }}</span>
                </Link>
                <Link
                    v-if="pendingApprovals.reimbursements > 0"
                    :href="route('owner.finance.reimbursements.pending.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/50 text-amber-900 border border-amber-300 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                >
                    <span>Klaim Biaya</span>
                    <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]">{{ pendingApprovals.reimbursements }}</span>
                </Link>
                <Link
                    v-if="pendingApprovals.loans > 0"
                    :href="route('owner.finance.loans.pending.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-amber-100/50 text-amber-900 border border-amber-300 rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                >
                    <span>Pinjaman</span>
                    <span class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]">{{ pendingApprovals.loans }}</span>
                </Link>
            </div>
        </div>

        <!-- Stats Grid (4 Kartu Utama) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Karyawan -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Total Karyawan</p>
                        <p class="text-2xl font-bold text-slate-900">{{ totalEmployees }} <span class="text-sm font-normal text-slate-400">Orang</span></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 text-primary-600 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <span>+{{ newEmployeesThisMonth }} bergabung bulan ini</span>
                </div>
            </div>

            <!-- Hadir Hari Ini -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Hadir Hari Ini</p>
                        <p class="text-2xl font-bold text-slate-900">{{ todayPresentCount }} <span class="text-sm font-normal text-slate-400">/ {{ totalEmployees }}</span></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-1.5 rounded-full" :style="{ width: Math.min(100, attendanceRate) + '%' }"></div>
                    </div>
                    <span class="text-xs font-semibold text-slate-600">{{ attendanceRate }}%</span>
                </div>
            </div>

            <!-- Cuti Pending -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pengajuan Cuti</p>
                        <p class="text-2xl font-bold text-slate-900">{{ pendingLeavesCount }} <span class="text-sm font-normal text-slate-400">Permintaan</span></p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-xs font-medium">
                    <Link v-if="pendingLeavesCount > 0" :href="route('owner.management.leaves.index')" class="text-amber-600 hover:text-amber-700 flex items-center gap-1 font-semibold">
                        <span>Tinjau persetujuan cuti</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                    <span v-else class="text-slate-400">Semua pengajuan telah diproses</span>
                </div>
            </div>

            <!-- Total Gaji -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Estimasi Payroll</p>
                        <p class="text-2xl font-bold text-slate-900">{{ formattedPayroll }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 text-xs text-slate-500 flex items-center justify-between">
                    <span>Periode Berjalan</span>
                    <Link :href="route('owner.finance.payrolls.index')" class="text-primary-600 hover:text-primary-700 font-semibold">Detail &rarr;</Link>
                </div>
            </div>
        </div>

        <!-- Statistik 30 Hari Terakhir Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-0.5">Rata-rata Jam Kerja/Hari</p>
                    <p class="text-xl font-bold text-slate-900">{{ stats30Days.avg_work_hours || '8.0' }} <span class="text-sm font-normal text-slate-500">Jam / Hari</span></p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-0.5">Total Overtime (30 Hari)</p>
                    <p class="text-xl font-bold text-slate-900">{{ stats30Days.total_overtime || 0 }} <span class="text-sm font-normal text-slate-500">Jam Disetujui</span></p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-0.5">Keterlambatan (30 Hari)</p>
                    <p class="text-xl font-bold text-slate-900">{{ stats30Days.total_lateness || 0 }} <span class="text-sm font-normal text-slate-500">Kejadian</span></p>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Attendance & Department -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900">Trend Kehadiran (14 Hari Terakhir)</h3>
                    <span class="text-xs font-medium text-slate-400">Absensi Riil Harian</span>
                </div>
                <div style="height: 280px;">
                    <canvas ref="attendanceCanvas"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900">Karyawan per Divisi</h3>
                    <span class="text-xs font-medium text-slate-400">{{ chartData?.department?.labels?.length || 0 }} Divisi</span>
                </div>
                <div style="height: 280px; position: relative;">
                    <canvas ref="departmentCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Payroll & Leave -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900">Trend Pengeluaran Gaji (6 Bulan Terakhir)</h3>
                    <span class="text-xs font-medium text-slate-400">Dalam Juta Rupiah</span>
                </div>
                <div style="height: 280px;">
                    <canvas ref="payrollCanvas"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900">Statistik Cuti Tahun Ini</h3>
                    <span class="text-xs font-medium text-slate-400">Berdasarkan Kategori</span>
                </div>
                <div style="height: 280px; position: relative;">
                    <canvas ref="leaveCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Main Content 2-Column Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Attendance Overview -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">Kehadiran Hari Ini</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Ringkasan status absensi tim</p>
                        </div>
                        <Link :href="route('owner.reports.attendances.index')" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Laporan Rekap &rarr;</Link>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                            <div class="text-center p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
                                <p class="text-2xl font-bold text-emerald-700">{{ todayAttendanceBreakdown?.present || 0 }}</p>
                                <p class="text-xs font-medium text-emerald-800 mt-1">Tepat Waktu</p>
                            </div>
                            <div class="text-center p-4 bg-amber-50 border border-amber-100 rounded-xl">
                                <p class="text-2xl font-bold text-amber-700">{{ todayAttendanceBreakdown?.late || 0 }}</p>
                                <p class="text-xs font-medium text-amber-800 mt-1">Terlambat</p>
                            </div>
                            <div class="text-center p-4 bg-blue-50 border border-blue-100 rounded-xl">
                                <p class="text-2xl font-bold text-blue-700">{{ todayAttendanceBreakdown?.leave || 0 }}</p>
                                <p class="text-xs font-medium text-blue-800 mt-1">Izin / Cuti</p>
                            </div>
                            <div class="text-center p-4 bg-rose-50 border border-rose-100 rounded-xl">
                                <p class="text-2xl font-bold text-rose-700">{{ todayAttendanceBreakdown?.alpha || 0 }}</p>
                                <p class="text-xs font-medium text-rose-800 mt-1">Tanpa Keterangan</p>
                            </div>
                        </div>

                        <!-- Recent Clock-ins -->
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-slate-900 text-sm">Clock-in Terbaru Hari Ini</h4>
                            <span class="text-xs text-slate-400">5 Terakhir</span>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="(att, idx) in recentClockIns"
                                :key="idx"
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-xl hover:bg-slate-100/80 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary-100 text-primary-700 rounded-xl flex items-center justify-center font-bold text-sm shrink-0">
                                        {{ (att.employee?.name || 'K').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ att.employee?.name || 'Karyawan' }}</p>
                                        <p class="text-xs text-slate-500">{{ att.employee?.division?.name || 'Umum' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 text-sm">{{ att.clock_in_time ? att.clock_in_time.substring(0, 5) : '-' }} WIB</p>
                                    <span
                                        v-if="att.late_minutes > 0"
                                        class="inline-flex items-center px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-semibold rounded-full"
                                    >
                                        Terlambat {{ att.late_minutes }}m
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-semibold rounded-full"
                                    >
                                        Tepat Waktu
                                    </span>
                                </div>
                            </div>
                            <p v-if="recentClockIns.length === 0" class="text-xs text-slate-400 text-center py-4">
                                Belum ada aktivitas clock-in tercatat hari ini.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Employees -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">Karyawan Terbaru Bergabung</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Anggota tim yang baru ditambahkan ke sistem</p>
                        </div>
                        <Link :href="route('owner.management.employees.index')" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Lihat Semua Karyawan &rarr;</Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Divisi</th>
                                    <th>Tanggal Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(emp, idx) in recentEmployees" :key="idx">
                                    <td class="font-bold text-slate-900">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-[10px] font-bold">
                                                {{ (emp.name || 'K').charAt(0).toUpperCase() }}
                                            </div>
                                            <span>{{ emp.name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-slate-600">{{ emp.division?.name || 'Umum' }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ emp.joined_at ? new Date(emp.joined_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Baru' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentEmployees.length === 0">
                                    <td colspan="3" class="text-center text-slate-400 py-4">Belum ada karyawan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column (1/3) -->
            <div class="space-y-6">
                <!-- Statistik Keterlambatan (30 Hari) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="font-bold text-slate-900">Distribusi Keterlambatan (30 Hari)</h3>
                    </div>
                    <div class="p-6">
                        <div style="height: 220px; position: relative;">
                            <canvas ref="lateCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Pengumuman Aktif Perusahaan Widget -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <h3 class="font-bold text-slate-900 text-sm">Pengumuman Sedang Tayang</h3>
                        </div>
                        <Link :href="route('owner.announcements.index')" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Semua &rarr;</Link>
                    </div>
                    <div class="p-4 space-y-3">
                        <Link
                            v-for="(ann, idx) in activeAnnouncements"
                            :key="idx"
                            :href="route('owner.announcements.show', ann.id)"
                            class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 transition-all border border-slate-100 group"
                        >
                            <div v-if="ann.image_url" class="w-12 h-12 rounded-lg overflow-hidden border border-slate-200 shrink-0 bg-slate-100 shadow-2xs">
                                <img :src="ann.image_url" :alt="ann.title" class="w-full h-full object-cover">
                            </div>
                            <div v-else class="w-12 h-12 rounded-lg border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                <svg class="w-6 h-6 text-slate-300 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 group-hover:text-primary-600 transition-colors line-clamp-1">{{ ann.title }}</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ ann.content?.replace(/<[^>]*>?/gm, '') }}</p>
                                <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-400">
                                    <span v-if="ann.branch" class="text-blue-600 font-semibold">{{ ann.branch.name }}</span>
                                </div>
                            </div>
                        </Link>
                        <p v-if="activeAnnouncements.length === 0" class="text-xs text-slate-400 text-center py-3">
                            Tidak ada pengumuman aktif hari ini.
                        </p>
                    </div>
                </div>

                <!-- Birthday Today -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Ulang Tahun Hari Ini</h3>
                        <span class="text-xs font-semibold px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full">{{ birthdaysToday.length }}</span>
                    </div>
                    <div class="p-4 space-y-3">
                        <div
                            v-for="(b, idx) in birthdaysToday"
                            :key="idx"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-amber-50/50 border border-amber-100/70"
                        >
                            <div class="w-10 h-10 bg-amber-100 text-amber-800 rounded-xl flex items-center justify-center font-bold text-sm shrink-0">
                                {{ (b.name || 'K').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-900 text-xs truncate">{{ b.name }}</p>
                                <p class="text-[11px] text-slate-500">{{ b.division?.name || 'Karyawan' }}</p>
                            </div>
                            <span class="text-xl" title="Selamat Ulang Tahun!">🎂</span>
                        </div>
                        <p v-if="birthdaysToday.length === 0" class="text-xs text-slate-400 text-center py-3">
                            Tidak ada karyawan yang berulang tahun hari ini.
                        </p>
                    </div>
                </div>

                <!-- Employees on Leave Today -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Karyawan Cuti Hari Ini</h3>
                        <span class="text-xs font-semibold px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-full">{{ leavesToday.length }}</span>
                    </div>
                    <div class="p-4 space-y-3">
                        <div
                            v-for="(leave, idx) in leavesToday"
                            :key="idx"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-indigo-50/50 border border-indigo-100/70"
                        >
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-800 rounded-xl flex items-center justify-center font-bold text-sm shrink-0">
                                {{ (leave.employee?.name || 'K').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-900 text-xs truncate">{{ leave.employee?.name || 'Karyawan' }}</p>
                                <p class="text-[11px] text-slate-500">{{ leave.employee?.division?.name || 'Divisi' }}</p>
                            </div>
                            <span class="badge badge-info text-[10px]">
                                {{ leave.leave_category?.name || 'Cuti' }}
                            </span>
                        </div>
                        <p v-if="leavesToday.length === 0" class="text-xs text-slate-400 text-center py-3">
                            Tidak ada karyawan yang cuti hari ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
