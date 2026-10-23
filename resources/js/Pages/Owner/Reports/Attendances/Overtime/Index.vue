<script setup>
import { reactive, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePickerRange from '@/Components/UI/DatePickerRange.vue';
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
    overtimeData: {
        type: [Array, Object],
        default: () => ({}),
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
    status: props.filters?.status || 'approved',
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
        if (newFilters.status !== undefined) filters.status = newFilters.status || 'approved';
    },
    { deep: true }
);

const applyFilters = () => {
    const isAllDates = !filters.start_date && !filters.end_date;

    router.get(
        route('owner.reports.attendances.overtime-recap.index'),
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
    filters.status = 'approved';
    filters.start_date = defaultStartDate;
    filters.end_date = defaultEndDate;
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        (filters.status && filters.status !== 'approved') ||
        (filters.start_date && filters.start_date !== defaultStartDate) ||
        (filters.end_date && filters.end_date !== defaultEndDate) ||
        (!filters.start_date && !filters.end_date)
    );
};

const getEmpOt = (empId) => {
    if (Array.isArray(props.overtimeData)) {
        return props.overtimeData.find(item => item.employee?.id === empId) || { total_count: 0, duration_formatted: '0j', estimated_pay: 0 };
    }
    return props.overtimeData?.[empId] || { total_count: 0, duration_formatted: '0j', estimated_pay: 0 };
};

// Summary metrics computed
const summaryStats = computed(() => {
    if (props.summary && Object.keys(props.summary).length > 0) {
        return {
            employeesWithOt: props.summary.employeesWithOt ?? 0,
            totalRequests: props.summary.totalRequests ?? 0,
            durationFormatted: props.summary.durationFormatted ?? '0j',
        };
    }

    const items = Array.isArray(props.overtimeData) ? props.overtimeData : Object.values(props.overtimeData || {});
    const employeesWithOt = items.filter(i => (i.total_count || 0) > 0).length;
    const totalRequests = items.reduce((acc, curr) => acc + (curr.total_count || 0), 0);
    const totalMinutes = items.reduce((acc, curr) => acc + (curr.total_minutes || 0), 0);
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    const durationFormatted = totalMinutes > 0 ? `${hours}j ${mins}m` : '0j';

    return {
        employeesWithOt,
        totalRequests,
        durationFormatted,
    };
});
</script>

<template>
    <Head title="Rekap Lembur Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Rekap Lembur Karyawan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Laporan rekapitulasi data dan total durasi lembur karyawan yang telah disetujui.
                </p>
            </div>
        </div>

        <!-- 3 Summary Stat Cards (Style matching Daily/Index.vue) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Karyawan Lembur -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Lembur</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.employeesWithOt }} Orang</div>
                </div>
            </div>

            <!-- Total Pengajuan Lembur -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengajuan Lembur</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.totalRequests }} Kali</div>
                </div>
            </div>

            <!-- Total Durasi Lembur -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Durasi Lembur</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.durationFormatted }}</div>
                </div>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Rate & Recap) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Rekap Lembur
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

                    <!-- Status Filter -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.status"
                            :options="[
                                { value: 'approved', label: 'Disetujui' },
                                { value: 'pending', label: 'Menunggu' },
                                { value: 'rejected', label: 'Ditolak' },
                                { value: 'all', label: 'Semua Status' },
                            ]"
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

                <!-- Row 2: Search field -->
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

        <!-- Overtime Recap Table -->
        <DataTable
            :headers="[
                'Karyawan',
                'Jumlah Pengajuan',
                'Total Durasi',
                '',
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

                    <!-- Jumlah Pengajuan -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-semibold text-xs text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                            {{ getEmpOt(emp.id).total_count }} Kali
                        </span>
                    </td>

                    <!-- Total Durasi -->
                    <td class="px-4 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-bold text-xs font-mono text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            {{ getEmpOt(emp.id).duration_formatted }}
                        </span>
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="route('owner.reports.attendances.overtime-recap.detail', [emp.id, { start_date: filters.start_date || undefined, end_date: filters.end_date || undefined }])"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Rincian Lembur"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </td>
                </tr>
            </template>

            <!-- Empty State -->
            <TableEmpty
                v-else
                :colspan="4"
                title="Belum ada data rekap lembur"
                message="Data rekapitulasi lembur karyawan belum tersedia untuk periode atau filter yang dipilih."
            />
        </DataTable>

        <!-- Pagination (1:1 Style dengan Employees/Index) -->
        <TablePagination :pagination="employees" />
    </div>
</template>
