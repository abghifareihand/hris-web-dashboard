<script setup>
import { reactive, watch } from 'vue';
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

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    reports: {
        type: Object,
        required: true,
    },
    totalReports: {
        type: Number,
        default: 0,
    },
    reportingEmployeesCount: {
        type: Number,
        default: 0,
    },
    totalAttachmentsCount: {
        type: Number,
        default: 0,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    employees: {
        type: Array,
        default: () => [],
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
        route('owner.reports.performances.daily.index'),
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

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Laporan Kerja Harian" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Laporan Kerja Harian
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Rekapitulasi catatan dan uraian aktivitas kerja harian yang dilaporkan oleh karyawan.
                </p>
            </div>
        </div>

        <!-- 3 Summary Stat Cards (Header Cards yang dipertahankan, tanpa Periode) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Total Laporan -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Laporan</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalReports }} Laporan</div>
                </div>
            </div>

            <!-- Karyawan Melapor -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Melapor</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ reportingEmployeesCount }} Orang</div>
                </div>
            </div>

            <!-- Total Lampiran -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Lampiran</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalAttachmentsCount }} Berkas</div>
                </div>
            </div>
        </div>

        <!-- Filter Controls (1:1 Style dengan Rate, Index, & Recap) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Laporan Kerja Harian
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

                <!-- Row 2: Search field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari Laporan"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari judul aktivitas, uraian, atau nama karyawan..."
                    />
                </div>
            </div>
        </div>

        <!-- Table Laporan Kerja Harian -->
        <DataTable
            :headers="[
                'Tanggal',
                'Karyawan',
                'Judul Aktivitas',
                'Uraian / Ringkasan',
                'Lampiran',
                '',
            ]"
        >
            <template v-if="reports.data && reports.data.length > 0">
                <tr
                    v-for="rep in reports.data"
                    :key="rep.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Tanggal -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-slate-800">
                        {{ formatDate(rep.date) }}
                    </td>

                    <!-- Karyawan (Avatar, Nama, Cabang • Divisi) -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="rep.user?.avatar"
                                :src="rep.user.avatar"
                                :alt="rep.user?.employee?.name || rep.user?.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{ (rep.user?.employee?.name || rep.user?.name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate text-sm">
                                    {{ rep.user?.employee?.name || rep.user?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
                                    <span>{{ rep.user?.employee?.branch?.name || '-' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span>{{ rep.user?.employee?.division?.name || 'Tanpa Divisi' }}</span>
                                    <template v-if="rep.user?.employee?.position?.name">
                                        <span class="text-slate-300">•</span>
                                        <span>{{ rep.user?.employee?.position?.name }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Judul Aktivitas -->
                    <td class="px-5 py-3.5 text-sm font-semibold text-slate-800 max-w-xs truncate">
                        {{ rep.title }}
                    </td>

                    <!-- Uraian / Ringkasan -->
                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-sm truncate">
                        {{ rep.description || '-' }}
                    </td>

                    <!-- Lampiran -->
                    <td class="px-4 py-3.5 whitespace-nowrap text-center">
                        <span
                            v-if="rep.attachments && rep.attachments.length > 0"
                            class="inline-flex items-center justify-center font-bold text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 rounded-full"
                        >
                            {{ rep.attachments.length }} Berkas
                        </span>
                        <span v-else class="text-slate-400 text-xs">-</span>
                    </td>

                    <!-- Aksi (Detail Icon matching Employees/Index) -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="route('owner.reports.performances.daily.show', rep.id)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Lihat Detail Laporan"
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
                :colspan="6"
                title="Belum ada laporan kerja harian"
                message="Tidak ada laporan kerja harian karyawan untuk kriteria atau periode yang dipilih."
            />
        </DataTable>

        <!-- Pagination (1:1 Style dengan Employees/Index) -->
        <TablePagination :pagination="reports" />
    </div>
</template>
