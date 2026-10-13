<script setup>
import { reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    branchList: {
        type: Array,
        default: () => [],
    },
    paginatedBranches: {
        type: Object,
        required: true,
    },
    totalBranches: {
        type: Number,
        default: 0,
    },
    totalEmployeesAllBranches: {
        type: Number,
        default: 0,
    },
    avgCompanyAttendance: {
        type: Number,
        default: 0,
    },
    avgCompanyScore: {
        type: Number,
        default: 0,
    },
    month: {
        type: Number,
        default: () => new Date().getMonth() + 1,
    },
    year: {
        type: Number,
        default: () => new Date().getFullYear(),
    },
    months: {
        type: Object,
        default: () => ({}),
    },
    years: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const defaultMonth = new Date().getMonth() + 1;
const defaultYear = new Date().getFullYear();

const filters = reactive({
    month: props.filters?.month ?? props.month ?? defaultMonth,
    year: props.filters?.year ?? props.year ?? defaultYear,
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.month = newFilters?.month ?? props.month ?? defaultMonth;
        filters.year = newFilters?.year ?? props.year ?? defaultYear;
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('owner.reports.performances.branch.index'),
        {
            month: filters.month || undefined,
            year: filters.year || undefined,
            search: filters.search || undefined,
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

const resetFilters = () => {
    filters.month = defaultMonth;
    filters.year = defaultYear;
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        Number(filters.month) !== Number(defaultMonth) ||
        Number(filters.year) !== Number(defaultYear)
    );
};

const monthOptions = computed(() => {
    return Object.entries(props.months || {}).map(([num, name]) => ({
        value: Number(num),
        label: name,
    }));
});

const yearOptions = computed(() => {
    return (props.years || []).map((y) => ({
        value: Number(y),
        label: String(y),
    }));
});

const tableHeaders = [
    { label: 'Cabang', align: 'left' },
    { label: 'Jumlah Karyawan', align: 'center' },
    { label: 'Tingkat Kehadiran', align: 'center' },
    { label: 'Rata-rata Jam Kerja', align: 'center' },
    { label: 'Turnover Rate', align: 'center' },
    { label: 'Skor Performa', align: 'center' },
    { label: 'Status', align: 'center' },
];

const getStatusVariant = (label) => {
    const l = String(label || '').toLowerCase();
    if (l.includes('sangat baik') || l.includes('optimal')) return 'success';
    if (l.includes('baik')) return 'primary';
    if (l.includes('cukup') || l.includes('perlu perhatian')) return 'warning';
    if (l.includes('kurang') || l.includes('kritis')) return 'danger';
    return 'secondary';
};

const getAttendanceVariant = (rate) => {
    if (rate >= 85) return 'success';
    if (rate >= 70) return 'primary';
    if (rate >= 50) return 'warning';
    return 'danger';
};
</script>

<template>
    <Head title="Laporan Performa Cabang" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Laporan Performa Cabang
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Analisis tingkat kehadiran, rata-rata jam kerja, turnover karyawan, dan skor performa per cabang.
                </p>
            </div>
        </div>

        <!-- 4 Summary Stat Cards (Style matching Daily/Index.vue) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Cabang -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Cabang</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalBranches }} Cabang</div>
                </div>
            </div>

            <!-- Total Karyawan -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Karyawan</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalEmployeesAllBranches }} Orang</div>
                </div>
            </div>

            <!-- Rata-rata Kehadiran -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ avgCompanyAttendance }}%</div>
                </div>
            </div>

            <!-- Rata-rata Skor Cabang -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Skor Cabang</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ avgCompanyScore }}%</div>
                </div>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Daily/Index.vue) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Laporan Performa Cabang
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

            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Bulan -->
                    <Select
                        label="Bulan"
                        v-model="filters.month"
                        :options="monthOptions"
                        @update:model-value="onFilterChange"
                    />

                    <!-- Tahun -->
                    <Select
                        label="Tahun"
                        v-model="filters.year"
                        :options="yearOptions"
                        @update:model-value="onFilterChange"
                    />

                    <!-- Cari Cabang -->
                    <Input
                        label="Pencarian Cabang"
                        :model-value="filters.search"
                        placeholder="Ketik nama atau kode cabang..."
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

        <!-- Table Performa Cabang -->
        <DataTable :headers="tableHeaders">
            <template v-if="branchList && branchList.length > 0">
                <tr
                    v-for="branch in branchList"
                    :key="branch.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Cabang Info -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800 text-sm">
                            {{ branch.branch_name }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Kode: {{ branch.branch_code || '-' }}
                        </div>
                    </td>

                    <!-- Jumlah Karyawan -->
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-semibold text-slate-800 text-xs">
                            {{ branch.employee_count }} Orang
                        </span>
                    </td>

                    <!-- Tingkat Kehadiran -->
                    <td class="px-5 py-3.5 text-center">
                        <Badge :variant="getAttendanceVariant(branch.attendance_rate)" class="font-bold">
                            {{ branch.attendance_rate }}%
                        </Badge>
                    </td>

                    <!-- Rata-rata Jam Kerja -->
                    <td class="px-5 py-3.5 text-center">
                        <span class="font-medium text-slate-700 text-xs">
                            {{ branch.avg_work_hours }} Jam / Hari
                        </span>
                    </td>

                    <!-- Turnover Rate -->
                    <td class="px-5 py-3.5 text-center">
                        <span
                            :class="[
                                'text-xs font-semibold',
                                branch.turnover_rate > 10 ? 'text-rose-600' : 'text-slate-700'
                            ]"
                        >
                            {{ branch.turnover_rate }}%
                        </span>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            ({{ branch.resigned_count }} resign)
                        </div>
                    </td>

                    <!-- Skor Performa -->
                    <td class="px-5 py-3.5 text-center font-bold text-slate-900 text-sm">
                        {{ branch.performance_score }}%
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 text-center">
                        <Badge :variant="getStatusVariant(branch.status_label)">
                            {{ branch.status_label }}
                        </Badge>
                    </td>
                </tr>
            </template>

            <template v-else>
                <TableEmpty
                    :colspan="7"
                    title="Tidak ada data performa cabang"
                    message="Coba sesuaikan bulan, tahun, atau kata kunci pencarian Anda."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="paginatedBranches" />
    </div>
</template>
