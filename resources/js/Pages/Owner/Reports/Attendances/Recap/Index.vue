<script setup>
import { reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
    employees: {
        type: Object,
        required: true,
    },
    recapData: {
        type: [Array, Object],
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
    positions: {
        type: Array,
        default: () => [],
    },
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    statusLabels: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const getFirstDayOfMonth = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}-01`;
};

const getTodayStr = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const defaultStartDate = getFirstDayOfMonth();
const defaultEndDate = getTodayStr();
const isAllDatesInitial = props.filters?.all_dates == '1' || props.filters?.all_dates === 1;

const filters = reactive({
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    position_id: props.filters?.position_id || '',
    search: props.filters?.search || '',
    start_date: isAllDatesInitial ? '' : (props.filters?.start_date !== undefined && props.filters?.start_date !== null ? props.filters.start_date : defaultStartDate),
    end_date: isAllDatesInitial ? '' : (props.filters?.end_date !== undefined && props.filters?.end_date !== null ? props.filters.end_date : defaultEndDate),
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
        if (newFilters.position_id !== undefined) filters.position_id = newFilters.position_id || '';
    },
    { deep: true }
);

const applyFilters = () => {
    const isAllDates = !filters.start_date && !filters.end_date;

    router.get(
        route('owner.reports.attendances.recap.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            position_id: filters.position_id || undefined,
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
    filters.position_id = '';
    filters.start_date = defaultStartDate;
    filters.end_date = defaultEndDate;
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.position_id ||
        (filters.start_date && filters.start_date !== defaultStartDate) ||
        (filters.end_date && filters.end_date !== defaultEndDate) ||
        (!filters.start_date && !filters.end_date)
    );
};

const getRecapItem = (empId) => {
    if (Array.isArray(props.recapData)) {
        return props.recapData.find(item => item.employee?.id === empId) || {};
    }
    return props.recapData?.[empId] || {};
};

const getEvaluationBadgeVariant = (status) => {
    if (!status) return 'secondary';
    const s = String(status).toLowerCase().trim();
    const vg = String(props.statusLabels?.very_good || 'sangat baik').toLowerCase().trim();
    const g = String(props.statusLabels?.good || 'baik').toLowerCase().trim();
    const f = String(props.statusLabels?.fair || 'cukup').toLowerCase().trim();
    const p = String(props.statusLabels?.poor || 'kurang').toLowerCase().trim();

    if (s === vg || s.includes('sangat baik') || s.includes('very good')) return 'success';
    if (s === g || s === 'baik' || s === 'good') return 'primary';
    if (s === f || s === 'cukup' || s === 'fair') return 'warning';
    if (s === p || s === 'kurang' || s === 'poor') return 'danger';
    return 'secondary';
};

const tableHeaders = [
    'Karyawan',
    { label: 'Jadwal Kerja', class: 'text-center whitespace-nowrap' },
    { label: 'Kehadiran', class: 'text-center whitespace-nowrap' },
    { label: 'Alpha', class: 'text-center whitespace-nowrap' },
    { label: 'Cuti', class: 'text-center whitespace-nowrap' },
    { label: 'Terlambat', class: 'text-center whitespace-nowrap' },
    { label: 'Pulang Cepat', class: 'text-center whitespace-nowrap' },
    { label: 'Pelanggaran', class: 'text-center whitespace-nowrap' },
    { label: 'Total Lembur', class: 'text-center whitespace-nowrap' },
    { label: 'Durasi Lembur', class: 'text-center whitespace-nowrap' },
    { label: 'Evaluasi', class: 'text-center whitespace-nowrap' },
];
</script>

<template>
    <Head title="Rekap Kehadiran Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Rekap Kehadiran Karyawan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Laporan rekapitulasi kehadiran, keterlambatan, cuti, lembur, dan evaluasi status absensi karyawan.
                </p>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Attendances & Employees) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Rekap Kehadiran
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
                <!-- Row 1: Cabang, Divisi, Jabatan, Periode Tanggal -->
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

                    <!-- Jabatan Filter -->
                    <div>
                        <Select
                            label="Jabatan"
                            v-model="filters.position_id"
                            :options="positions.map(p => ({ value: p.id, label: p.name }))"
                            all-label="Semua Jabatan"
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

        <!-- Attendance Recap Table -->
        <DataTable :headers="tableHeaders">
            <template v-if="employees.data && employees.data.length > 0">
                <tr
                    v-for="emp in employees.data"
                    :key="emp.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Karyawan (Avatar, Nama, Cabang • Divisi • Jabatan) -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="emp.user?.avatar"
                                :src="emp.user.avatar"
                                :alt="emp.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{ emp.name ? emp.name.charAt(0).toUpperCase() : '?' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate text-sm">
                                    {{ emp.name }}
                                </div>
                                <div class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
                                    <span>{{ emp.branch?.name || '-' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span>{{ emp.division?.name || 'Tanpa Divisi' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span>{{ emp.position?.name || 'Tanpa Jabatan' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Jadwal Kerja -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-semibold text-xs text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                            {{ getRecapItem(emp.id).total_schedule || 0 }} Hari
                        </span>
                    </td>

                    <!-- Kehadiran -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-bold text-xs text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            {{ getRecapItem(emp.id).total_present || 0 }} Hari
                        </span>
                    </td>

                    <!-- Alpha -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_alpha > 0"
                            class="inline-flex items-center justify-center font-bold text-xs text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200"
                        >
                            {{ getRecapItem(emp.id).total_alpha }}
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Cuti -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_leave > 0"
                            class="inline-flex items-center justify-center font-semibold text-xs text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200"
                        >
                            {{ getRecapItem(emp.id).total_leave }}
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Terlambat -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_late > 0"
                            class="inline-flex items-center justify-center font-semibold text-xs text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200"
                        >
                            {{ getRecapItem(emp.id).total_late }}
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Pulang Cepat -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_early_leaving > 0"
                            class="inline-flex items-center justify-center font-semibold text-xs text-orange-700 bg-orange-50 px-2.5 py-0.5 rounded-full border border-orange-200"
                        >
                            {{ getRecapItem(emp.id).total_early_leaving }}
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Pelanggaran -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_violations > 0"
                            class="inline-flex items-center justify-center font-bold text-xs text-rose-800 bg-rose-100 px-2.5 py-0.5 rounded-full border border-rose-300"
                        >
                            {{ getRecapItem(emp.id).total_violations }}
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Total Lembur -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span
                            v-if="getRecapItem(emp.id).total_overtime_count > 0"
                            class="inline-flex items-center justify-center font-semibold text-xs text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200"
                        >
                            {{ getRecapItem(emp.id).total_overtime_count }}x
                        </span>
                        <span v-else class="text-slate-400 text-xs font-normal">
                            0
                        </span>
                    </td>

                    <!-- Durasi Lembur -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span class="font-medium text-xs font-mono text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                            {{ getRecapItem(emp.id).overtime_duration_formatted || '0j' }}
                        </span>
                    </td>

                    <!-- Evaluasi -->
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <Badge
                            :variant="getEvaluationBadgeVariant(getRecapItem(emp.id).evaluation_status)"
                            pill
                        >
                            {{ getRecapItem(emp.id).evaluation_status || 'Cukup' }}
                        </Badge>
                    </td>
                </tr>
            </template>

            <!-- Empty State -->
            <TableEmpty
                v-else
                :colspan="11"
                title="Belum ada data rekapitulasi"
                message="Data rekapitulasi kehadiran karyawan belum tersedia untuk periode atau filter yang dipilih."
            />
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="employees" />
    </div>
</template>
