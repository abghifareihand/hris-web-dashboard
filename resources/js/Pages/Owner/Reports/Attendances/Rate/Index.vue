<script setup>
import { reactive, watch, computed } from 'vue';
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
import { getAvatarUrl } from '@/Utils/avatar';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employees: {
        type: Object,
        required: true,
    },
    rateData: {
        type: [Array, Object],
        default: () => ({}),
    },
    branchRates: {
        type: Array,
        default: () => [],
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
    },
    { deep: true }
);

const applyFilters = () => {
    const isAllDates = !filters.start_date && !filters.end_date;

    router.get(
        route('owner.reports.attendances.rate.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
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
    filters.start_date = defaultStartDate;
    filters.end_date = defaultEndDate;
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        (filters.start_date && filters.start_date !== defaultStartDate) ||
        (filters.end_date && filters.end_date !== defaultEndDate) ||
        (!filters.start_date && !filters.end_date)
    );
};

const getItem = (empId) => {
    if (Array.isArray(props.rateData)) {
        return props.rateData.find(item => item.employee?.id === empId) || {};
    }
    return props.rateData?.[empId] || {};
};

// Summary metrics
const summaryMetrics = computed(() => {
    if (props.summary && Object.keys(props.summary).length > 0) {
        return {
            avgRate: props.summary.avgRate ?? 0,
            totalWorkingDays: props.summary.totalWorkingDays ?? 0,
            totalCount: props.summary.totalCount ?? (props.employees?.total || 0),
        };
    }

    const list = Array.isArray(props.rateData) ? props.rateData : Object.values(props.rateData || {});
    if (list.length === 0) return { avgRate: 0, totalWorkingDays: 0, totalCount: 0 };

    const totalRate = list.reduce((acc, curr) => acc + (parseFloat(curr.attendance_rate) || 0), 0);
    const avgRate = Math.round((totalRate / list.length) * 10) / 10;
    const maxDays = Math.max(...list.map(i => i.total_schedule || 0), 0);

    return {
        avgRate,
        totalWorkingDays: maxDays,
        totalCount: props.employees?.total || list.length,
    };
});

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
</script>

<template>
    <Head title="Laporan Tingkat Kehadiran" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Laporan Tingkat Kehadiran
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Analisis persentase dan tingkat kehadiran karyawan per area/cabang serta perorangan.
                </p>
            </div>
        </div>

        <!-- 3 Summary Stat Cards (Style matching Daily/Index.vue) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Rata-rata Kehadiran -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryMetrics.avgRate }}%</div>
                </div>
            </div>

            <!-- Hari Kerja Terjadwal -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hari Kerja Terjadwal</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryMetrics.totalWorkingDays }} Hari</div>
                </div>
            </div>

            <!-- Total Karyawan Terdata -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Karyawan Terdata</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryMetrics.totalCount }} Orang</div>
                </div>
            </div>
        </div>

        <!-- Section Card: Rata-rata Kehadiran per Area / Cabang -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Rata-rata Kehadiran per Area / Cabang
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tingkat persentase dan evaluasi kehadiran karyawan berdasarkan masing-masing cabang perusahaan.
                    </p>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="branch in branchRates"
                        :key="branch.id"
                        class="p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 hover:bg-slate-50 transition-all flex flex-col justify-between"
                    >
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100/70 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-slate-900 leading-tight">
                                       {{ branch.name }}
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ branch.employee_count }} Karyawan
                                    </p>
                                </div>
                            </div>
                            <Badge :variant="getEvaluationBadgeVariant(branch.status)" pill>
                                {{ branch.status }}
                            </Badge>
                        </div>

                        <!-- Rate -->
                        <div class="mt-2 pt-2.5 border-t border-slate-200/70 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Tingkat Kehadiran</span>
                            <span
                                class="font-bold text-xs font-mono px-2.5 py-0.5 rounded-full border"
                                :class="
                                    branch.attendance_rate >= 85
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : (branch.attendance_rate >= 75
                                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                                            : 'bg-rose-50 text-rose-700 border-rose-200')
                                "
                            >
                                {{ branch.attendance_rate }}%
                            </span>
                        </div>
                    </div>

                    <div v-if="!branchRates || branchRates.length === 0" class="col-span-full py-8 text-center text-slate-400 text-xs">
                        Tidak ada data cabang yang ditemukan.
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Attendances & Recap) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Tingkat Kehadiran
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
                <!-- Row 1: Cabang, Divisi, Periode Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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

        <!-- Attendance Rate Table -->
        <DataTable
            :headers="[
                'Karyawan',
                'Hari Kerja',
                'Hadir',
                'Tingkat Kehadiran',
                'Status',
            ]"
        >
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
                                :src="getAvatarUrl(emp.user.avatar)"
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

                    <!-- Hari Kerja -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-semibold text-xs text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                            {{ getItem(emp.id).total_schedule || 0 }} Hari
                        </span>
                    </td>

                    <!-- Hadir -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-bold text-xs text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            {{ getItem(emp.id).total_present || 0 }} Hari
                        </span>
                    </td>

                    <!-- Tingkat Kehadiran -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <span
                            class="inline-flex items-center justify-center font-bold text-xs font-mono px-2.5 py-0.5 rounded-full border"
                            :class="
                                (getItem(emp.id).attendance_rate || 0) >= 85
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : ((getItem(emp.id).attendance_rate || 0) >= 75
                                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border-rose-200')
                            "
                        >
                            {{ getItem(emp.id).attendance_rate || 0 }}%
                        </span>
                    </td>

                    <!-- Evaluasi -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <Badge
                            :variant="getEvaluationBadgeVariant(getItem(emp.id).evaluation_status)"
                            pill
                        >
                            {{ getItem(emp.id).evaluation_status || 'Cukup' }}
                        </Badge>
                    </td>
                </tr>
            </template>

            <!-- Empty State -->
            <TableEmpty
                v-else
                :colspan="5"
                title="Belum ada data tingkat kehadiran"
                message="Data tingkat kehadiran karyawan belum tersedia untuk periode atau filter yang dipilih."
            />
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="employees" />
    </div>
</template>
