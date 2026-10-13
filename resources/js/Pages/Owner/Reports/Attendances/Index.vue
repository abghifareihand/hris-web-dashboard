<script setup>
import { reactive, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePickerRange from '@/Components/UI/DatePickerRange.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    attendances: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const getTodayStr = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const defaultToday = getTodayStr();
const isAllDatesInitial = props.filters?.all_dates == '1' || props.filters?.all_dates === 1;

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    status: props.filters?.status || '',
    start_date: isAllDatesInitial ? '' : (props.filters?.start_date !== undefined && props.filters?.start_date !== null ? props.filters.start_date : defaultToday),
    end_date: isAllDatesInitial ? '' : (props.filters?.end_date !== undefined && props.filters?.end_date !== null ? props.filters.end_date : defaultToday),
});

watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        const isAll = newFilters.all_dates == '1' || newFilters.all_dates === 1;
        if (isAll) {
            filters.start_date = '';
            filters.end_date = '';
        } else {
            if (newFilters.start_date !== undefined && newFilters.start_date !== null) {
                filters.start_date = newFilters.start_date;
            }
            if (newFilters.end_date !== undefined && newFilters.end_date !== null) {
                filters.end_date = newFilters.end_date;
            }
        }
        if (newFilters.search !== undefined) filters.search = newFilters.search || '';
        if (newFilters.branch_id !== undefined) filters.branch_id = newFilters.branch_id || '';
        if (newFilters.division_id !== undefined) filters.division_id = newFilters.division_id || '';
        if (newFilters.status !== undefined) filters.status = newFilters.status || '';
    },
    { deep: true }
);

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'very_good', label: 'Sangat Baik' },
    { value: 'good', label: 'Baik' },
    { value: 'fair', label: 'Cukup' },
    { value: 'poor', label: 'Kurang' },
    { value: 'present', label: 'Hadir' },
    { value: 'late', label: 'Terlambat' },
    { value: 'clocked_in', label: 'Sedang Bekerja' },
    { value: 'leave', label: 'Cuti / Izin' },
    { value: 'alpha', label: 'Alpha' },
];

const applyFilters = () => {
    const isAllDates = !filters.start_date && !filters.end_date;

    router.get(
        route('owner.reports.attendances.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            status: filters.status || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
            all_dates: isAllDates ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const { debouncedFn: debouncedSearch } = useDebounce(() => {
    applyFilters();
}, 350);

const onSearchInput = (val) => {
    filters.search = val;
    debouncedSearch();
};

const onFilterChange = () => {
    applyFilters();
};

const onDateRangeChange = (range) => {
    if (range && typeof range === 'object') {
        if ('startDate' in range) filters.start_date = range.startDate || '';
        if ('endDate' in range) filters.end_date = range.endDate || '';
    }
    applyFilters();
};

const resetFilters = () => {
    filters.search = '';
    filters.branch_id = '';
    filters.division_id = '';
    filters.status = '';
    filters.start_date = defaultToday;
    filters.end_date = defaultToday;
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.status ||
        (filters.start_date && filters.start_date !== defaultToday) ||
        (filters.end_date && filters.end_date !== defaultToday) ||
        (!filters.start_date && !filters.end_date)
    );
};

const getStatusInfo = (status, label) => {
    const s = String(status || '').toLowerCase();
    if (s === 'very_good') {
        return { label: label || 'Sangat Baik', variant: 'success' };
    }
    if (s === 'good' || s === 'present' || s === 'hadir') {
        return { label: label || (s === 'good' ? 'Baik' : 'Hadir'), variant: 'primary' };
    }
    if (s === 'fair' || s === 'late' || s === 'terlambat') {
        return { label: label || (s === 'fair' ? 'Cukup' : 'Terlambat'), variant: 'warning' };
    }
    if (s === 'poor' || s === 'alpha' || s === 'absent') {
        return { label: label || (s === 'poor' ? 'Kurang' : 'Alpha'), variant: 'danger' };
    }
    if (s === 'leave' || s === 'cuti' || s === 'izin') {
        return { label: label || 'Cuti / Izin', variant: 'info' };
    }
    if (s === 'sick' || s === 'sakit') {
        return { label: label || 'Sakit', variant: 'warning' };
    }
    if (s === 'clocked_in') {
        return { label: label || 'Sedang Bekerja', variant: 'info' };
    }
    return { label: label || status || '-', variant: 'secondary' };
};

const formatTime = (timeStr) => {
    if (!timeStr) return '-';
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(timeStr)) {
        return timeStr.slice(0, 5);
    }
    if (typeof timeStr === 'string' && (timeStr.includes('T') || timeStr.includes(' '))) {
        const parts = timeStr.split(/[T ]/);
        if (parts[1]) {
            return parts[1].slice(0, 5);
        }
    }
    try {
        const d = new Date(timeStr);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':');
    } catch {
        return '-';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

</script>

<template>
    <Head title="Absensi Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Absensi Karyawan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola data dan informasi absensi harian karyawan perusahaan.
                </p>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Employees/Index) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Absensi
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
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>Reset</span>
                    </Button>
                </transition>
            </div>

            <div class="p-6 space-y-4">
                <!-- Row 1: Cabang, Divisi, Status, Periode Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Cabang Filter -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map(b => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi Filter -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Status Kehadiran Filter -->
                    <div>
                        <Select
                            label="Status Kehadiran"
                            v-model="filters.status"
                            :options="statusOptions"
                            all-label="Semua Status"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Periode Tanggal Filter (DatePickerRange) -->
                    <div>
                        <DatePickerRange
                            label="Periode Tanggal"
                            v-model:startDate="filters.start_date"
                            v-model:endDate="filters.end_date"
                            placeholder="Pilih Rentang Tanggal"
                            @change="onDateRangeChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Search field below divider -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari Karyawan"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama karyawan atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- Attendance Table (1:1 Style dengan Employees/Index) -->
        <DataTable
            :headers="[
                'Karyawan',
                'Shift',
                'Absen Masuk',
                'Absen Keluar',
                'Status',
                'Tanggal',
                '',
            ]"
        >
            <template v-if="attendances.data && attendances.data.length > 0">
                <tr
                    v-for="att in attendances.data"
                    :key="att.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Karyawan (Avatar, Nama, Divisi & Cabang) -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="att.employee?.user?.avatar"
                                :src="att.employee.user.avatar"
                                :alt="att.employee?.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{ att.employee?.name ? att.employee.name.charAt(0).toUpperCase() : '?' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate text-sm">
                                    {{ att.employee?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
                                    <span>{{ att.employee?.division?.name || 'Tanpa Divisi' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span>{{ att.employee?.branch?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Shift -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="font-medium text-slate-700 text-sm">
                            {{ att.shift?.name || 'Reguler' }}
                        </span>
                    </td>

                    <!-- Absen Masuk -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-baseline gap-1">
                            <span class="font-semibold text-slate-800 text-sm">
                                {{ formatTime(att.clock_in_time) }}
                            </span>
                            <span v-if="formatTime(att.clock_in_time) !== '-'" class="text-[11px] text-slate-400 font-medium">WIB</span>
                        </div>
                    </td>

                    <!-- Absen Keluar -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-baseline gap-1">
                            <span class="font-semibold text-slate-800 text-sm">
                                {{ formatTime(att.clock_out_time) }}
                            </span>
                            <span v-if="formatTime(att.clock_out_time) !== '-'" class="text-[11px] text-slate-400 font-medium">WIB</span>
                        </div>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge
                            :variant="getStatusInfo(att.attendance_status, att.status_label).variant"
                            size="sm"
                            class="font-medium capitalize"
                        >
                            {{ getStatusInfo(att.attendance_status, att.status_label).label }}
                        </Badge>
                    </td>

                    <!-- Tanggal -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600">
                        {{ formatDate(att.date) }}
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <Link
                            :href="route('owner.reports.attendances.show', att.id)"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                            title="Lihat Detail Absensi"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>
                    </td>
                </tr>
            </template>

            <template v-else>
                <TableEmpty
                    :colspan="7"
                    title="Tidak ada data absensi"
                    message="Belum ada data absensi karyawan yang ditemukan."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="attendances" />
    </div>
</template>
