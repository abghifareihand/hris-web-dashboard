<script setup>
import { reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    recapList: {
        type: Array,
        default: () => [],
    },
    paginatedEmployees: {
        type: Object,
        required: true,
    },
    totalEmployees: {
        type: Number,
        default: 0,
    },
    totalNetHours: {
        type: String,
        default: '0 Jam',
    },
    totalOvertimeHours: {
        type: String,
        default: '0 Jam',
    },
    avgScore: {
        type: Number,
        default: 0,
    },
    chartBarData: {
        type: Object,
        default: () => ({}),
    },
    chartDonutData: {
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
    positions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const getDefaultMonthRange = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const lastDay = new Date(year, d.getMonth() + 1, 0).getDate();
    return {
        startDate: `${year}-${month}-01`,
        endDate: `${year}-${month}-${String(lastDay).padStart(2, '0')}`,
    };
};

const defaultRange = getDefaultMonthRange();
const defaultStartDate = defaultRange.startDate;
const defaultEndDate = defaultRange.endDate;

const filters = reactive({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    branch_id: props.filters?.branch_id ? String(props.filters.branch_id) : '',
    division_id: props.filters?.division_id ? String(props.filters.division_id) : '',
    position_id: props.filters?.position_id ? String(props.filters.position_id) : '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.start_date = newFilters?.start_date || '';
        filters.end_date = newFilters?.end_date || '';
        filters.branch_id = newFilters?.branch_id ? String(newFilters.branch_id) : '';
        filters.division_id = newFilters?.division_id ? String(newFilters.division_id) : '';
        filters.position_id = newFilters?.position_id ? String(newFilters.position_id) : '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    const isAllDates = !filters.start_date && !filters.end_date;

    router.get(
        route('owner.reports.performances.employee.index'),
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

const branchOptions = computed(() => [
    { value: '', label: 'Semua Cabang' },
    ...props.branches.map((b) => ({ value: String(b.id), label: b.name })),
]);

const divisionOptions = computed(() => [
    { value: '', label: 'Semua Divisi' },
    ...props.divisions.map((d) => ({ value: String(d.id), label: d.name })),
]);

const positionOptions = computed(() => [
    { value: '', label: 'Semua Jabatan' },
    ...props.positions.map((p) => ({ value: String(p.id), label: p.name })),
]);

const tableHeaders = [
    { label: 'Karyawan', align: 'left' },
    { label: 'Kehadiran', align: 'center' },
    { label: 'Terlambat', align: 'center' },
    { label: 'Jam Bersih', align: 'center' },
    { label: 'Lembur', align: 'center' },
    { label: 'Disiplin Istirahat', align: 'center' },
    { label: 'Skor Performa', align: 'center' },
];

const getScoreVariant = (score) => {
    if (score >= 80) return 'success';
    if (score >= 65) return 'primary';
    if (score >= 50) return 'warning';
    return 'danger';
};

const getDisciplineVariant = (key) => {
    const k = String(key || '').toLowerCase();
    if (k === 'obedient' || k.includes('patuh') || k.includes('disiplin')) return 'success';
    if (k === 'fair' || k.includes('cukup')) return 'warning';
    if (k === 'warning' || k.includes('peringatan') || k.includes('kurang')) return 'danger';
    return 'secondary';
};
</script>

<template>
    <Head title="Laporan Performa Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Laporan Performa Karyawan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Ringkasan produktivitas jam kerja bersih, kedisiplinan istirahat, lembur, dan evaluasi skor performa karyawan.
                </p>
            </div>
        </div>

        <!-- 4 Summary Stat Cards (Style matching Daily/Index.vue) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Karyawan Terdata -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Terdata</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalEmployees }} Orang</div>
                </div>
            </div>

            <!-- Jam Kerja Bersih -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Kerja Bersih</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalNetHours }}</div>
                </div>
            </div>

            <!-- Total Jam Lembur -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Jam Lembur</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalOvertimeHours }}</div>
                </div>
            </div>

            <!-- Rata-rata Skor Performa -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Skor Performa</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ avgScore }}%</div>
                </div>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Daily/Index.vue) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Laporan Performa Karyawan
                </h2>

                <!-- Reset Filter Button -->
                <transition
                    enter-active-class="transition-opacity duration-150 ease-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition-opacity duration-150 ease-in"
                    leave-to-class="opacity-0"
                >
                    <button
                        v-if="hasActiveFilters()"
                        type="button"
                        @click="resetFilters"
                        class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </transition>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Cabang -->
                    <Select
                        label="Cabang"
                        v-model="filters.branch_id"
                        :options="branchOptions"
                        @update:model-value="onFilterChange"
                    />

                    <!-- Divisi -->
                    <Select
                        label="Divisi"
                        v-model="filters.division_id"
                        :options="divisionOptions"
                        @update:model-value="onFilterChange"
                    />

                    <!-- Jabatan -->
                    <Select
                        label="Jabatan"
                        v-model="filters.position_id"
                        :options="positionOptions"
                        @update:model-value="onFilterChange"
                    />

                    <!-- Date Range -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            Periode Tanggal
                        </label>
                        <DatePickerRange
                            :start-date="filters.start_date"
                            :end-date="filters.end_date"
                            placeholder="Pilih rentang tanggal"
                            @change="onDateRangeChange"
                        />
                    </div>
                </div>

                <!-- Search Input Bar -->
                <div>
                    <Input
                        label="Pencarian Karyawan"
                        :model-value="filters.search"
                        placeholder="Ketik nama, NIP, atau email karyawan..."
                        @update:model-value="onSearchInput"
                    >
                        <template #prefix>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </template>
                    </Input>
                </div>
            </div>
        </div>

        <!-- Table Performa Karyawan -->
        <DataTable :headers="tableHeaders">
            <template v-if="recapList && recapList.length > 0">
                <tr
                    v-for="row in recapList"
                    :key="row.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Karyawan Info -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800 text-sm">
                            {{ row.name }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ row.nip || '-' }} • {{ row.division }} • {{ row.branch }}
                        </div>
                    </td>

                    <!-- Kehadiran -->
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-bold text-emerald-600">{{ row.present_days }}</span>
                        <span class="text-slate-400 text-xs"> / {{ row.scheduled_days }} Hari</span>
                    </td>

                    <!-- Terlambat -->
                    <td class="px-5 py-3.5 text-center">
                        <span
                            :class="[
                                'text-xs',
                                row.late_count > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400'
                            ]"
                        >
                            {{ row.late_count }} Kali
                        </span>
                    </td>

                    <!-- Jam Kerja Bersih -->
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-semibold text-slate-800 text-xs">
                            {{ row.net_hours_text }}
                        </span>
                    </td>

                    <!-- Lembur -->
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-medium text-slate-700 text-xs">
                            {{ row.overtime_hours_text }}
                        </span>
                    </td>

                    <!-- Disiplin Istirahat -->
                    <td class="px-5 py-3.5 text-center">
                        <Badge :variant="getDisciplineVariant(row.break_discipline_key)">
                            {{ row.break_discipline_label }}
                        </Badge>
                    </td>

                    <!-- Skor Performa -->
                    <td class="px-5 py-3.5 text-center">
                        <Badge :variant="getScoreVariant(row.score)" class="font-bold">
                            {{ row.score }}%
                        </Badge>
                    </td>
                </tr>
            </template>

            <template v-else>
                <TableEmpty
                    :colspan="7"
                    title="Tidak ada data performa karyawan"
                    message="Coba sesuaikan filter cabang, divisi, jabatan, atau rentang tanggal pencarian Anda."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="paginatedEmployees" />
    </div>
</template>
