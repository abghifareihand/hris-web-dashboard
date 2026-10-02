<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    hasProfile: Boolean,
    employee: Object,
    stats: Object,
    todayAttendance: Object,
    todaySchedule: Object,
    announcements: Array,
    recentRequests: Array,
});

const todayDateFormatted = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
        case 'disetujui':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'pending':
        case 'menunggu':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'rejected':
        case 'ditolak':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Employee Dashboard" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-800 p-6 sm:p-8 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-emerald-100 text-xs font-medium backdrop-blur-xs mb-3">
                        <span>Portal Karyawan</span>
                        <span>•</span>
                        <span>{{ todayDateFormatted }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                        Halo, {{ employee?.name || $page.props.auth?.user?.name }}! 👋
                    </h1>
                    <p class="text-emerald-100 text-sm mt-1 max-w-xl">
                        {{ employee?.position?.name || 'Karyawan' }} di {{ employee?.branch?.name || 'Perusahaan' }} • {{ employee?.division?.name || 'Operasional' }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('employee.attendances.index')"
                        class="px-4 py-2.5 bg-white text-emerald-700 hover:bg-emerald-50 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Presensi Sekarang</span>
                    </Link>
                </div>
            </div>

            <!-- 4 Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- 1. Kehadiran -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kehadiran Bulan Ini</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.present_days || 0 }} <span class="text-xs font-normal text-slate-500">/ {{ stats?.scheduled_days || 22 }} Hari</span>
                        </div>
                        <div class="text-[11px] font-semibold text-emerald-600 mt-0.5">
                            {{ stats?.attendance_rate || 100 }}% tingkat kehadiran
                        </div>
                    </div>
                </div>

                <!-- 2. Sisa Cuti -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sisa Saldo Cuti</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.leave_balance ?? 12 }} <span class="text-xs font-normal text-slate-500">Hari</span>
                        </div>
                        <Link :href="route('employee.leaves.index')" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 mt-0.5 block">
                            Ajukan cuti &rarr;
                        </Link>
                    </div>
                </div>

                <!-- 3. Total Lembur -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lembur Bulan Ini</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.overtime_hours || 0 }} <span class="text-xs font-normal text-slate-500">Jam</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Disetujui manajemen</div>
                    </div>
                </div>

                <!-- 4. Pengajuan Aktif -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Permohonan Aktif</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.pending_requests || 0 }} <span class="text-xs font-normal text-slate-500">Menunggu</span>
                        </div>
                        <div class="text-[11px] text-amber-600 font-semibold mt-0.5">Status verifikasi</div>
                    </div>
                </div>
            </div>

            <!-- Main Content 2-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column (8 cols): Today Status + Announcements -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Today Presensi & Shift Box -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="font-bold text-slate-900 text-sm">Status Presensi Hari Ini</h3>
                            <span class="text-xs text-slate-400">{{ todayDateFormatted }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Clock In/Out Status -->
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-3">
                                <div>
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Presensi Harian</span>
                                    <div class="text-base font-bold text-slate-800 mt-0.5">
                                        {{ todayAttendance?.clock_in_time ? 'Anda Sudah Absen Masuk' : 'Belum Absen Masuk' }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200/60 text-xs">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Clock In</span>
                                        <span class="font-bold text-slate-800">{{ todayAttendance?.clock_in_time ? todayAttendance.clock_in_time.slice(0, 5) + ' WIB' : '--:--' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Clock Out</span>
                                        <span class="font-bold text-slate-800">{{ todayAttendance?.clock_out_time ? todayAttendance.clock_out_time.slice(0, 5) + ' WIB' : '--:--' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Shift Info -->
                            <div class="p-4 bg-sky-50/60 rounded-xl border border-sky-100 space-y-3">
                                <div>
                                    <span class="text-[11px] font-semibold text-sky-600 uppercase tracking-wider block">Jadwal Shift Kerja</span>
                                    <div class="text-base font-bold text-sky-950 mt-0.5">
                                        {{ todaySchedule?.shift?.name || 'Shift Reguler' }}
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-sky-200/50 text-xs">
                                    <span class="text-sky-600 block text-[11px]">Jam Kerja Terjadwal:</span>
                                    <span class="font-bold text-sky-900">
                                        {{ todaySchedule?.shift?.clock_in ? todaySchedule.shift.clock_in.slice(0, 5) : '08:00' }} - {{ todaySchedule?.shift?.clock_out ? todaySchedule.shift.clock_out.slice(0, 5) : '17:00' }} WIB
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-bold text-slate-900 text-sm">Pengumuman Terbaru</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div
                                v-for="ann in announcements"
                                :key="ann.id"
                                class="p-4 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors"
                            >
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800">
                                        Pengumuman
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ new Date(ann.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm">{{ ann.title }}</h4>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">{{ ann.content }}</p>
                            </div>
                            <div v-if="!announcements || announcements.length === 0" class="text-center py-8 text-xs text-slate-400">
                                Tidak ada pengumuman aktif saat ini.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (4 cols): Recent Requests + Quick Links -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Recent Requests -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-900 text-sm">Pengajuan Terakhir Anda</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div
                                v-for="(req, idx) in recentRequests"
                                :key="idx"
                                class="p-3 rounded-lg border border-slate-100 bg-slate-50/75 flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="overflow-hidden">
                                    <div class="font-semibold text-slate-800 truncate">{{ req.title }}</div>
                                    <div class="text-[11px] text-slate-400">{{ req.detail }}</div>
                                </div>
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0', getStatusBadge(req.status)]">
                                    {{ req.status }}
                                </span>
                            </div>
                            <div v-if="!recentRequests || recentRequests.length === 0" class="text-center py-6 text-xs text-slate-400">
                                Belum ada pengajuan aktif.
                            </div>
                        </div>
                    </div>

                    <!-- Quick Shortcuts Card -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6 space-y-3">
                        <h3 class="font-bold text-slate-900 text-sm mb-3">Akses Cepat Karyawan</h3>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <Link
                                :href="route('employee.leaves.index')"
                                class="p-3 rounded-xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50/40 transition-colors text-center font-semibold text-slate-700 block"
                            >
                                🌴 Form Cuti
                            </Link>
                            <Link
                                :href="route('employee.overtimes.index')"
                                class="p-3 rounded-xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50/40 transition-colors text-center font-semibold text-slate-700 block"
                            >
                                ⏰ Form Lembur
                            </Link>
                            <Link
                                :href="route('employee.reimbursements.index')"
                                class="p-3 rounded-xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50/40 transition-colors text-center font-semibold text-slate-700 block"
                            >
                                🧾 Klaim Biaya
                            </Link>
                            <Link
                                :href="route('employee.payrolls.index')"
                                class="p-3 rounded-xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50/40 transition-colors text-center font-semibold text-slate-700 block"
                            >
                                💵 Slip Gaji
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
