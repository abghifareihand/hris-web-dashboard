<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    attendance: Object,
    schedule: Object,
});

const emp = computed(() => props.attendance?.employee || {});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'present':
        case 'hadir':
            return { label: 'Hadir', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
        case 'late':
        case 'terlambat':
            return { label: 'Terlambat', class: 'bg-amber-100 text-amber-800 border-amber-200' };
        case 'leave':
        case 'cuti':
            return { label: 'Cuti / Izin', class: 'bg-blue-100 text-blue-800 border-blue-200' };
        case 'sick':
        case 'sakit':
            return { label: 'Sakit', class: 'bg-purple-100 text-purple-800 border-purple-200' };
        case 'alpha':
        case 'absent':
            return { label: 'Alpha', class: 'bg-rose-100 text-rose-800 border-rose-200' };
        default:
            return { label: status || '-', class: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};
</script>

<template>
    <Head :title="`Detail Absensi - ${emp.name || 'Karyawan'}`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.reports.attendances.index')"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Detail Kehadiran</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Laporan lengkap absensi harian karyawan.</p>
                    </div>
                </div>
                <Link
                    :href="route('owner.reports.attendances.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                >
                    Kembali
                </Link>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column: Employee Profile Card -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 h-24"></div>
                        <div class="px-6 pb-6 relative">
                            <div class="absolute -top-10 left-6 bg-white p-1 rounded-full shadow-md">
                                <div class="w-18 h-18 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-2xl font-bold">
                                    {{ emp.name ? emp.name.charAt(0).toUpperCase() : '?' }}
                                </div>
                            </div>

                            <div class="flex justify-end pt-3">
                                <span :class="['px-3 py-1 rounded-full text-xs font-bold border shadow-xs', getStatusBadge(attendance.attendance_status).class]">
                                    {{ attendance.status_label || getStatusBadge(attendance.attendance_status).label }}
                                </span>
                            </div>

                            <div class="mt-4">
                                <h2 class="text-lg font-bold text-slate-900">{{ emp.name || 'Karyawan' }}</h2>
                                <p class="text-emerald-600 font-medium text-xs">{{ emp.position?.name || 'Tanpa Jabatan' }}</p>
                            </div>

                            <div class="mt-6 space-y-3.5 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400 w-24">NIP / ID:</span>
                                    <span class="font-semibold text-slate-800">{{ emp.nip || '-' }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400 w-24">Cabang:</span>
                                    <span class="font-semibold text-slate-800">{{ emp.branch?.name || '-' }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400 w-24">Divisi:</span>
                                    <span class="font-semibold text-slate-800">{{ emp.division?.name || '-' }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400 w-24">No. Telp:</span>
                                    <span class="font-semibold text-slate-800">{{ emp.phone_number || '-' }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400 w-24">Tanggal Absen:</span>
                                    <span class="font-semibold text-slate-800">{{ formatDate(attendance.date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shift Info Card -->
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3">
                        <h3 class="font-bold text-slate-800 text-sm">Informasi Shift & Jadwal</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-500">Nama Shift:</span>
                                <span class="font-semibold text-slate-800">{{ attendance.shift?.name || 'Reguler / Bebas' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-500">Jadwal Masuk:</span>
                                <span class="font-semibold text-slate-800">{{ attendance.shift?.clock_in || '08:00' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-500">Jadwal Pulang:</span>
                                <span class="font-semibold text-slate-800">{{ attendance.shift?.clock_out || '17:00' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Keterlambatan:</span>
                                <span :class="attendance.late_minutes > 0 ? 'text-amber-600 font-bold' : 'text-slate-800 font-semibold'">
                                    {{ attendance.late_minutes || 0 }} Menit
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Check-in / Check-out Details -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Clock In Box -->
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-sm">Absen Masuk (Clock In)</h3>
                                </div>
                                <span class="text-lg font-black text-emerald-600 font-mono">
                                    {{ attendance.clock_in_time ? attendance.clock_in_time.slice(0, 5) : '--:--' }}
                                </span>
                            </div>

                            <!-- Photo if available -->
                            <div v-if="attendance.clock_in_photo" class="rounded-lg overflow-hidden border border-slate-200 bg-slate-50 max-h-48 flex items-center justify-center">
                                <img :src="attendance.clock_in_photo" alt="Foto Masuk" class="max-h-48 object-cover w-full" />
                            </div>
                            <div v-else class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-xs text-slate-400">
                                Tidak ada lampiran foto selfie masuk
                            </div>

                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Koordinat Lokasi:</span>
                                    <span class="font-mono text-slate-700">{{ attendance.clock_in_latitude || '-' }}, {{ attendance.clock_in_longitude || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Catatan Masuk:</span>
                                    <span class="text-slate-700 italic">{{ attendance.clock_in_notes || 'Tidak ada catatan' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Clock Out Box -->
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-sm">Absen Pulang (Clock Out)</h3>
                                </div>
                                <span class="text-lg font-black text-blue-600 font-mono">
                                    {{ attendance.clock_out_time ? attendance.clock_out_time.slice(0, 5) : '--:--' }}
                                </span>
                            </div>

                            <!-- Photo if available -->
                            <div v-if="attendance.clock_out_photo" class="rounded-lg overflow-hidden border border-slate-200 bg-slate-50 max-h-48 flex items-center justify-center">
                                <img :src="attendance.clock_out_photo" alt="Foto Pulang" class="max-h-48 object-cover w-full" />
                            </div>
                            <div v-else class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-xs text-slate-400">
                                Tidak ada lampiran foto selfie pulang
                            </div>

                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Koordinat Lokasi:</span>
                                    <span class="font-mono text-slate-700">{{ attendance.clock_out_latitude || '-' }}, {{ attendance.clock_out_longitude || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Catatan Pulang:</span>
                                    <span class="text-slate-700 italic">{{ attendance.clock_out_notes || 'Tidak ada catatan' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Work Duration Summary -->
                    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                        <h3 class="font-bold text-slate-800 text-sm mb-4">Ringkasan Jam Kerja</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <div class="text-xs text-slate-500 font-medium">Total Jam Kerja</div>
                                <div class="text-xl font-bold text-slate-800 mt-1">
                                    {{ attendance.total_work_minutes ? Math.floor(attendance.total_work_minutes / 60) + ' Jam ' + (attendance.total_work_minutes % 60) + ' Mnt' : '-' }}
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <div class="text-xs text-slate-500 font-medium">Pulang Lebih Awal</div>
                                <div class="text-xl font-bold text-slate-800 mt-1">
                                    {{ attendance.early_leaving_minutes || 0 }} Menit
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <div class="text-xs text-slate-500 font-medium">Status Verifikasi</div>
                                <div class="text-sm font-bold text-emerald-600 mt-1">
                                    Terverifikasi Sistem
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
