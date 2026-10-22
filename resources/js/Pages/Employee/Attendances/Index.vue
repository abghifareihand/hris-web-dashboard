<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    employee: Object,
    attendances: [Object, Array],
    stats: Object,
    todayAttendance: Object,
    todaySchedule: Object,
    month: Number,
    year: Number,
    months: Object,
    years: Array,
    filters: Object,
});

const attendanceList = computed(() => {
    if (Array.isArray(props.attendances)) return props.attendances;
    return props.attendances?.data || [];
});

const currentMonth = new Date().getMonth() + 1;
const currentYear = new Date().getFullYear();

// Filter State matching Owner pattern
const filters = reactive({
    month: props.month || currentMonth,
    year: props.year || currentYear,
    status: props.filters?.status || '',
});

watch(
    () => [props.month, props.year, props.filters],
    () => {
        filters.month = props.month || currentMonth;
        filters.year = props.year || currentYear;
        filters.status = props.filters?.status || '';
    },
    { deep: true }
);

// Convert months and years to Select options format
const monthOptions = computed(() => {
    return Object.entries(props.months || {}).map(([num, name]) => ({
        value: Number(num),
        label: name,
    }));
});

const yearOptions = computed(() => {
    return (props.years || []).map((y) => ({
        value: y,
        label: String(y),
    }));
});

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'present', label: 'Tepat Waktu' },
    { value: 'late', label: 'Terlambat' },
    { value: 'leave', label: 'Izin / Cuti' },
    { value: 'alpha', label: 'Alpha' },
];

const applyFilters = () => {
    router.get(
        route('employee.attendances.index'),
        {
            month: filters.month,
            year: filters.year,
            status: filters.status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const onFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    filters.month = currentMonth;
    filters.year = currentYear;
    filters.status = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.status ||
        filters.month !== currentMonth ||
        filters.year !== currentYear
    );
};

// Modal Detail State
const isDetailModalOpen = ref(false);
const selectedAttendance = ref(null);

const openDetailModal = (att) => {
    selectedAttendance.value = att;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedAttendance.value = null;
};

// Table Headers
const tableHeaders = [
    { label: 'Tanggal & Hari', class: 'min-w-[180px]' },
    { label: 'Shift Kerja', class: 'w-48' },
    { label: 'Absen Masuk', class: 'w-40 text-center' },
    { label: 'Absen Keluar', class: 'w-40 text-center' },
    { label: 'Total Durasi', class: 'w-32 text-center' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-24 text-right' },
];

const getStatusInfo = (status, statusLabel) => {
    const s = String(status || '').toLowerCase();
    if (s === 'present' || s === 'hadir' || s === 'very_good' || s === 'good') {
        return { variant: 'success', label: 'Hadir' };
    }
    if (s === 'late' || s === 'terlambat' || s === 'fair') {
        return { variant: 'warning', label: 'Terlambat' };
    }
    if (s === 'clocked_in') {
        return { variant: 'primary', label: 'Sedang Bekerja' };
    }
    if (s === 'leave' || s === 'cuti' || s === 'sick' || s === 'sakit') {
        return { variant: 'info', label: 'Cuti / Izin' };
    }
    if (s === 'alpha' || s === 'absent' || s === 'poor') {
        return { variant: 'danger', label: 'Alpha' };
    }
    return { variant: 'secondary', label: statusLabel || 'Lainnya' };
};
</script>

<template>
    <Head title="Riwayat Absensi Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Riwayat Absensi Saya</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Catatan dan statistik rekap kehadiran harian Anda di {{ employee?.company?.name || 'Perusahaan' }}.
                </p>
            </div>

            <!-- Notice: Mobile Attendance Info -->
            <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200/80 flex items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-blue-950">Presensi Menggunakan Aplikasi Smartphone</h4>
                        <p class="text-xs text-blue-700 mt-0.5">
                            Absensi masuk dan pulang wajib dilakukan melalui aplikasi mobile resmi menggunakan validasi GPS & Foto Selfie. Halaman ini berfungsi sebagai portal rekapitulasi dan riwayat kehadiran Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics Grid (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Hadir -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kehadiran</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.total_present || 0 }} <span class="text-xs font-normal text-slate-500">/ {{ stats?.scheduled_days || 22 }} Hari</span>
                        </div>
                        <div class="text-[11px] font-semibold text-emerald-600 mt-0.5">
                            {{ stats?.attendance_rate || 0 }}% tingkat kehadiran
                        </div>
                    </div>
                </div>

                <!-- Tepat Waktu -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tepat Waktu</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.total_ontime || 0 }} <span class="text-xs font-normal text-slate-500">Hari</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Disiplin jam kerja
                        </div>
                    </div>
                </div>

                <!-- Terlambat -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Terlambat Masuk</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.total_late || 0 }} <span class="text-xs font-normal text-slate-500">Kejadian</span>
                        </div>
                        <div class="text-[11px] text-amber-600 mt-0.5">
                            Total kerja: {{ stats?.total_work_hours || 0 }} Jam
                        </div>
                    </div>
                </div>

                <!-- Izin / Cuti / Alpha -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Izin, Cuti & Alpha</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">
                            {{ stats?.total_leave || 0 }} <span class="text-xs font-normal text-slate-500">Izin / Cuti</span>
                        </div>
                        <div class="text-[11px] text-rose-600 mt-0.5">
                            {{ stats?.total_alpha || 0 }} hari tanpa keterangan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Data Presensi
                    </h2>

                    <!-- Reset Filter Button -->
                    <transition
                        enter-active-class="transition-opacity duration-150 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <Button
                            v-if="hasActiveFilters()"
                            variant="secondary"
                            size="sm"
                            type="button"
                            @click="resetFilters"
                            class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </Button>
                    </transition>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Bulan -->
                        <div>
                            <Select
                                label="Bulan Presensi"
                                v-model="filters.month"
                                :options="monthOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Tahun -->
                        <div>
                            <Select
                                label="Tahun"
                                v-model="filters.year"
                                :options="yearOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Kehadiran"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Data Table (Standar DataTable Component) -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="attendanceList && attendanceList.length > 0">
                        <tr
                            v-for="att in attendanceList"
                            :key="att.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Tanggal & Hari -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 text-sm">
                                    {{ att.date_formatted }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ att.day_name }}
                                </div>
                            </td>

                            <!-- Shift Kerja -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-medium text-slate-700 text-sm">
                                    {{ att.shift_name }}
                                </span>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ att.shift_clock_in }} - {{ att.shift_clock_out }} WIB
                                </div>
                            </td>

                            <!-- Absen Masuk -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <div v-if="att.clock_in_time" class="inline-flex flex-col items-center">
                                    <div class="flex items-baseline gap-1">
                                        <span class="font-semibold text-slate-800 text-sm font-mono">
                                            {{ att.clock_in_time }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-medium">WIB</span>
                                    </div>
                                    <span
                                        v-if="att.late_minutes > 0"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 mt-0.5"
                                    >
                                        Telat {{ att.late_minutes }}m
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 mt-0.5"
                                    >
                                        Tepat Waktu
                                    </span>
                                </div>
                                <span v-else class="text-xs text-slate-400">-</span>
                            </td>

                            <!-- Absen Keluar -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <div v-if="att.clock_out_time" class="flex items-baseline justify-center gap-1">
                                    <span class="font-semibold text-slate-800 text-sm font-mono">
                                        {{ att.clock_out_time }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-medium">WIB</span>
                                </div>
                                <span
                                    v-else-if="att.clock_in_time"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    Belum Pulang
                                </span>
                                <span v-else class="text-xs text-slate-400">-</span>
                            </td>

                            <!-- Total Durasi -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center text-sm font-semibold text-slate-700">
                                {{ att.work_duration !== '-' ? att.work_duration : '-' }}
                            </td>

                            <!-- Status Presensi -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <Badge :variant="getStatusInfo(att.attendance_status, att.status_label).variant" size="sm">
                                    {{ getStatusInfo(att.attendance_status, att.status_label).label }}
                                </Badge>
                            </td>

                            <!-- Aksi (Detail) -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <button
                                    type="button"
                                    @click="openDetailModal(att)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                    title="Lihat Detail Presensi & Foto Selfie"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <TableEmpty
                        v-else
                        :colspan="7"
                        title="Tidak Ada Catatan Presensi"
                        :message="`Belum ada log data presensi yang tercatat untuk bulan ${months[month]} ${year}.`"
                    />
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="attendances" />
        </div>

        <!-- Detail Attendance Modal -->
        <Modal
            :show="isDetailModalOpen"
            maxWidth="lg"
            @close="closeDetailModal"
        >
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Detail Presensi Karyawan</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedAttendance">
                            {{ selectedAttendance.date_formatted }}
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedAttendance" class="space-y-4">
                <!-- Top Summary Banner -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ selectedAttendance.shift_name }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Jadwal Shift: {{ selectedAttendance.shift_clock_in }} - {{ selectedAttendance.shift_clock_out }} WIB
                        </p>
                    </div>
                    <Badge :variant="getStatusInfo(selectedAttendance.attendance_status, selectedAttendance.status_label).variant">
                        {{ getStatusInfo(selectedAttendance.attendance_status, selectedAttendance.status_label).label }}
                    </Badge>
                </div>

                <!-- Timing Breakdown -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jam Masuk</span>
                        <div class="text-sm font-bold text-slate-900 mt-0.5 font-mono">
                            {{ selectedAttendance.clock_in_time ? `${selectedAttendance.clock_in_time} WIB` : '-' }}
                        </div>
                        <span v-if="selectedAttendance.late_minutes > 0" class="text-[10px] font-semibold text-amber-600 mt-0.5 inline-block">
                            Terlambat {{ selectedAttendance.late_minutes }} menit
                        </span>
                        <span v-else-if="selectedAttendance.clock_in_time" class="text-[10px] font-semibold text-emerald-600 mt-0.5 inline-block">
                            Tepat Waktu
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jam Pulang</span>
                        <div class="text-sm font-bold text-slate-900 mt-0.5 font-mono">
                            {{ selectedAttendance.clock_out_time ? `${selectedAttendance.clock_out_time} WIB` : '-' }}
                        </div>
                        <span class="text-[10px] font-medium text-slate-500 mt-0.5 inline-block">
                            Durasi: {{ selectedAttendance.work_duration }}
                        </span>
                    </div>
                </div>

                <!-- Locations / Address -->
                <div v-if="selectedAttendance.clock_in_address || selectedAttendance.clock_out_address" class="space-y-2 text-xs">
                    <div v-if="selectedAttendance.clock_in_address" class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="font-bold text-slate-600 text-[11px] block mb-0.5">Lokasi Masuk:</span>
                        <span class="text-slate-700">{{ selectedAttendance.clock_in_address }}</span>
                    </div>
                    <div v-if="selectedAttendance.clock_out_address" class="p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="font-bold text-slate-600 text-[11px] block mb-0.5">Lokasi Pulang:</span>
                        <span class="text-slate-700">{{ selectedAttendance.clock_out_address }}</span>
                    </div>
                </div>

                <!-- Notes if any -->
                <div v-if="selectedAttendance.notes" class="p-3 bg-amber-50/60 rounded-xl border border-amber-100 text-xs">
                    <span class="font-bold text-amber-900 block mb-0.5">Catatan Presensi:</span>
                    <p class="text-amber-800 italic">{{ selectedAttendance.notes }}</p>
                </div>

                <!-- Photos Selfie Mobile Section -->
                <div>
                    <h5 class="text-xs font-bold text-slate-900 mb-2">Foto Selfie Presensi (Mobile App)</h5>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Clock In Photo -->
                        <div class="text-center">
                            <div class="w-full h-44 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden flex items-center justify-center shadow-2xs">
                                <img
                                    v-if="selectedAttendance.clock_in_photo"
                                    :src="selectedAttendance.clock_in_photo"
                                    alt="Selfie Masuk"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-center p-3 text-slate-400 text-xs">
                                    <svg class="w-6 h-6 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Tidak ada foto masuk</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-600 mt-1 block">Selfie Clock-In</span>
                        </div>

                        <!-- Clock Out Photo -->
                        <div class="text-center">
                            <div class="w-full h-44 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden flex items-center justify-center shadow-2xs">
                                <img
                                    v-if="selectedAttendance.clock_out_photo"
                                    :src="selectedAttendance.clock_out_photo"
                                    alt="Selfie Pulang"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-center p-3 text-slate-400 text-xs">
                                    <svg class="w-6 h-6 mx-auto mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Tidak ada foto pulang</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-600 mt-1 block">Selfie Clock-Out</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <Button variant="secondary" @click="closeDetailModal">
                        Tutup
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
