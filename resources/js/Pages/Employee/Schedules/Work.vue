<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    employee: Object,
    schedules: Array,
    holidays: Array,
    month: Number,
    year: Number,
    months: Object,
    years: Array,
});

const currentMonth = new Date().getMonth() + 1;
const currentYear = new Date().getFullYear();

// View Mode Toggle ('calendar' | 'table')
const getInitialViewMode = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        return params.get('view') || 'calendar';
    }
    return 'calendar';
};

const viewMode = ref(getInitialViewMode());

const setViewMode = (mode) => {
    viewMode.value = mode;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('view', mode);
        window.history.replaceState({}, '', url);
    }
};

// Filter State
const filters = reactive({
    month: props.month || currentMonth,
    year: props.year || currentYear,
    day_status: '',
});

watch(
    () => [props.month, props.year],
    () => {
        filters.month = props.month || currentMonth;
        filters.year = props.year || currentYear;
    }
);

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

const dayStatusOptions = [
    { value: '', label: 'Semua Status Hari' },
    { value: 'work', label: 'Hari Kerja' },
    { value: 'off', label: 'Hari Libur (Day Off)' },
];

const applyFilter = () => {
    router.get(
        route('employee.schedules.work.index'),
        {
            month: filters.month,
            year: filters.year,
            view: viewMode.value !== 'calendar' ? viewMode.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const onFilterChange = () => {
    applyFilter();
};

const resetFilters = () => {
    filters.month = currentMonth;
    filters.year = currentYear;
    filters.day_status = '';
    applyFilter();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.day_status ||
        Number(filters.month) !== Number(currentMonth) ||
        Number(filters.year) !== Number(currentYear)
    );
};

// Calendar Calculations (1:1 Standar Owner Work Schedule)
const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const calendarDays = computed(() => {
    const y = Number(filters.year);
    const m = Number(filters.month) - 1; // 0-indexed in JS Date
    const firstDayIndex = new Date(y, m, 1).getDay();
    const totalDaysInMonth = new Date(y, m + 1, 0).getDate();

    const days = [];

    // Pre-month empty padding
    for (let i = 0; i < firstDayIndex; i++) {
        days.push({
            dayNumber: null,
            dateStr: null,
            dayName: null,
            isCurrentMonth: false,
            isSunday: false,
            schedule: null,
            holidays: [],
        });
    }

    // Days in current month
    for (let day = 1; day <= totalDaysInMonth; day++) {
        const dateStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayOfWeek = new Date(y, m, day).getDay();

        // Match schedule for authenticated employee on this date
        const schedule = (props.schedules || []).find((s) => {
            if (!s.date) return false;
            return s.date.substring(0, 10) === dateStr;
        });

        // Match national holidays / cuti bersama
        const dayHolidays = (props.holidays || []).filter((h) => {
            const start = h.start_date ? h.start_date.substring(0, 10) : (h.date ? h.date.substring(0, 10) : '');
            const end = h.end_date ? h.end_date.substring(0, 10) : start;
            return dateStr >= start && dateStr <= end;
        });

        days.push({
            dayNumber: day,
            dateStr: dateStr,
            dayName: daysOfWeek[dayOfWeek],
            isCurrentMonth: true,
            isSunday: dayOfWeek === 0,
            schedule: schedule || null,
            holidays: dayHolidays,
        });
    }

    return days;
});

const isToday = (dateStr) => {
    if (!dateStr) return false;
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    return dateStr === todayStr;
};

const isCellMatchingFilter = (cell) => {
    if (!cell.isCurrentMonth) return true;
    if (!filters.day_status) return true;
    if (!cell.schedule) return false;
    if (filters.day_status === 'work') return !cell.schedule.is_day_off;
    if (filters.day_status === 'off') return cell.schedule.is_day_off;
    return true;
};

// Summary Statistics
const summaryStats = computed(() => {
    const list = props.schedules || [];
    const workDays = list.filter((s) => !s.is_day_off).length;
    const offDays = list.filter((s) => s.is_day_off).length;
    const totalHolidays = (props.holidays || []).length;
    return {
        workDays,
        offDays,
        totalHolidays,
    };
});

// Day Detail Modal State
const selectedDateDetails = ref(null);
const isDayDetailModalOpen = ref(false);

const openDayDetails = (cell) => {
    if (!cell.isCurrentMonth) return;
    selectedDateDetails.value = cell;
    isDayDetailModalOpen.value = true;
};

const openDayDetailsFromSchedule = (sch) => {
    const dateStr = sch.date ? sch.date.substring(0, 10) : '';
    const dayHolidays = (props.holidays || []).filter((h) => {
        const start = h.start_date ? h.start_date.substring(0, 10) : (h.date ? h.date.substring(0, 10) : '');
        const end = h.end_date ? h.end_date.substring(0, 10) : start;
        return dateStr >= start && dateStr <= end;
    });

    const parts = dateStr.split('-');
    const dayName = parts.length === 3 ? daysOfWeek[new Date(parts[0], parts[1] - 1, parts[2]).getDay()] : '';

    selectedDateDetails.value = {
        dayNumber: parts.length === 3 ? Number(parts[2]) : null,
        dateStr: dateStr,
        dayName: dayName,
        isCurrentMonth: true,
        isSunday: dayName === 'Min',
        schedule: sch,
        holidays: dayHolidays,
    };
    isDayDetailModalOpen.value = true;
};

// Table View Filtered Data
const filteredSchedules = computed(() => {
    if (!props.schedules) return [];
    if (!filters.day_status) return props.schedules;
    if (filters.day_status === 'off') {
        return props.schedules.filter((s) => s.is_day_off);
    }
    if (filters.day_status === 'work') {
        return props.schedules.filter((s) => !s.is_day_off);
    }
    return props.schedules;
});

// Date formatters (immune to timezone shift)
const formatFullDate = (dateStr) => {
    if (!dateStr) return '-';
    const parts = dateStr.substring(0, 10).split('-');
    if (parts.length === 3) {
        return new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }
    return dateStr;
};

const formatShortDate = (dateStr) => {
    if (!dateStr) return '-';
    const parts = dateStr.substring(0, 10).split('-');
    if (parts.length === 3) {
        return new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    }
    return dateStr;
};

const formatDayOnly = (dateStr) => {
    if (!dateStr) return '';
    const parts = dateStr.substring(0, 10).split('-');
    if (parts.length === 3) {
        return new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString('id-ID', {
            weekday: 'long',
        });
    }
    return '';
};

const tableHeaders = [
    { label: 'Tanggal & Hari', class: 'min-w-[180px]' },
    { label: 'Shift Kerja', class: 'min-w-[160px]' },
    { label: 'Jam Kerja', class: 'w-48 text-center' },
    { label: 'Status Hari', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-28 text-right' },
];
</script>

<template>
    <Head title="Jadwal & Shift Kerja" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header (1:1 Standar Owner) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Jadwal Kerja
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Visualisasi kalender jadwal tugas harian, shift kerja, dan hari libur Anda.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Mode View Toggle (Kalender / Daftar Tabel) -->
                    <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 border border-slate-200/80 shadow-2xs">
                        <button
                            type="button"
                            @click="setViewMode('calendar')"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150',
                                viewMode === 'calendar'
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50',
                            ]"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Kalender</span>
                        </button>
                        <button
                            type="button"
                            @click="setViewMode('table')"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150',
                                viewMode === 'table'
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50',
                            ]"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>Daftar Tabel</span>
                        </button>
                    </div>

                    <!-- Shortcut Tukar Shift -->
                    <Link :href="route('employee.schedules.swap-personal.index')">
                        <Button variant="outline" class="gap-1.5 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span>Tukar Shift</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Jadwal Kerja
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
                                label="Bulan"
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

                        <!-- Status Hari -->
                        <div>
                            <Select
                                label="Status Hari"
                                v-model="filters.day_status"
                                :options="dayStatusOptions"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Holidays Alert Banner (if any) -->
            <div v-if="holidays && holidays.length > 0" class="p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div class="text-xs text-amber-900">
                    <h4 class="font-bold uppercase tracking-wider">Hari Libur & Cuti Bersama Bulan Ini:</h4>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-amber-800">
                        <span v-for="h in holidays" :key="h.id || h.name" class="inline-flex items-center gap-1">
                            • <strong>{{ h.name }}</strong> ({{ formatShortDate(h.start_date || h.date) }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Summary Statistic Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Total Hari Kerja</div>
                        <div class="text-lg font-bold text-slate-800">
                            {{ summaryStats.workDays }} <span class="text-xs font-normal text-slate-400">Hari</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Hari Libur Terjadwal (Off)</div>
                        <div class="text-lg font-bold text-slate-800">
                            {{ summaryStats.offDays }} <span class="text-xs font-normal text-slate-400">Hari</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Libur Nasional / Cuti</div>
                        <div class="text-lg font-bold text-slate-800">
                            {{ summaryStats.totalHolidays }} <span class="text-xs font-normal text-slate-400">Hari</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. CALENDAR VIEW (1:1 Standar Owner) -->
            <div v-show="viewMode === 'calendar'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Day of Week Header -->
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/80 text-center text-xs font-bold text-slate-600 py-3">
                    <div v-for="d in daysOfWeek" :key="d" :class="{ 'text-rose-600': d === 'Min' }">
                        {{ d }}
                    </div>
                </div>

                <!-- Calendar Days Grid -->
                <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100">
                    <div
                        v-for="(cell, index) in calendarDays"
                        :key="index"
                        :class="[
                            'min-h-[110px] sm:min-h-[120px] p-2 sm:p-2.5 transition flex flex-col justify-between group',
                            !cell.isCurrentMonth
                                ? 'bg-slate-50/40 select-none'
                                : 'hover:bg-emerald-50/25 cursor-pointer',
                            !isCellMatchingFilter(cell) ? 'opacity-30' : '',
                        ]"
                        @click="cell.isCurrentMonth && openDayDetails(cell)"
                    >
                        <!-- Top Cell Row: Day Number & Indicator -->
                        <div class="flex items-center justify-between">
                            <span
                                v-if="cell.dayNumber"
                                :class="[
                                    'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition',
                                    cell.holidays.length > 0 || cell.isSunday
                                        ? 'bg-rose-50 text-rose-600 font-bold group-hover:bg-rose-600 group-hover:text-white'
                                        : 'text-slate-700 group-hover:bg-emerald-600 group-hover:text-white',
                                    isToday(cell.dateStr) ? 'ring-2 ring-emerald-600 ring-offset-1 font-extrabold' : '',
                                ]"
                            >
                                {{ cell.dayNumber }}
                            </span>
                            <span
                                v-if="cell.isCurrentMonth && isToday(cell.dateStr)"
                                class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded shadow-2xs"
                            >
                                Hari Ini
                            </span>
                        </div>

                        <!-- Middle Cell Content: Holiday & Shift Badges -->
                        <div v-if="cell.isCurrentMonth" class="mt-1 space-y-1">
                            <!-- Holiday Tag -->
                            <div
                                v-for="h in cell.holidays.slice(0, 1)"
                                :key="h.id || h.name"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-100 truncate flex items-center gap-1"
                                :title="h.name"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                <span class="truncate">{{ h.name }}</span>
                            </div>

                            <!-- Shift Tag -->
                            <div v-if="cell.schedule">
                                <!-- Day Off -->
                                <div
                                    v-if="cell.schedule.is_day_off"
                                    class="px-2 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-600 border border-slate-200/70 group-hover:border-slate-300 transition"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-700">Libur</span>
                                        <span class="text-[9px] font-semibold text-slate-400">Day Off</span>
                                    </div>
                                </div>

                                <!-- Working Shift -->
                                <div
                                    v-else
                                    class="px-2 py-1.5 rounded-lg text-xs bg-emerald-50 text-emerald-900 border border-emerald-200/80 group-hover:border-emerald-300 group-hover:bg-emerald-100/60 transition"
                                >
                                    <div class="font-bold truncate text-[11px] sm:text-xs text-emerald-950">
                                        {{ cell.schedule.shift?.name || 'Shift Kerja' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-emerald-700 mt-0.5 flex items-center gap-1 font-medium">
                                        <svg class="w-2.5 h-2.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ cell.schedule.shift?.clock_in ? cell.schedule.shift.clock_in.slice(0, 5) : '08:00' }} - {{ cell.schedule.shift?.clock_out ? cell.schedule.shift.clock_out.slice(0, 5) : '17:00' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-[10px] text-slate-300 italic text-center py-1">
                                Belum diatur
                            </div>
                        </div>

                        <!-- Cell Bottom Prompt -->
                        <div v-if="cell.isCurrentMonth" class="pt-1 flex items-center justify-end">
                            <span class="text-[9px] font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition flex items-center gap-0.5">
                                Detail &rarr;
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Calendar Footer Note -->
                <div class="p-4 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex items-center gap-1.5 font-medium text-emerald-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Hari Kerja Terjadwal
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            Hari Libur (Day Off)
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-rose-600">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Hari Libur Nasional
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400 italic">
                        *Klik pada kotak tanggal untuk melihat detail lengkap shift & opsi tukar jadwal.
                    </div>
                </div>
            </div>

            <!-- 2. TABLE VIEW (1:1 Standar Owner) -->
            <div v-show="viewMode === 'table'" class="space-y-4">
                <DataTable :headers="tableHeaders">
                    <template #body>
                        <template v-if="filteredSchedules && filteredSchedules.length > 0">
                            <tr
                                v-for="sch in filteredSchedules"
                                :key="sch.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Tanggal & Hari -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 text-sm">
                                        {{ formatShortDate(sch.date) }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ formatDayOnly(sch.date) }}
                                    </div>
                                </td>

                                <!-- Shift Kerja -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <Badge :variant="sch.is_day_off ? 'secondary' : 'success'" size="sm">
                                        {{ sch.is_day_off ? 'Libur Terjadwal' : (sch.shift?.name || 'Shift Reguler') }}
                                    </Badge>
                                </td>

                                <!-- Jam Kerja -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span v-if="!sch.is_day_off" class="font-mono text-sm font-semibold text-slate-800">
                                        {{ sch.shift?.clock_in ? sch.shift.clock_in.slice(0, 5) : '08:00' }} - {{ sch.shift?.clock_out ? sch.shift.clock_out.slice(0, 5) : '17:00' }} <span class="text-[11px] text-slate-400 font-sans font-normal">WIB</span>
                                    </span>
                                    <span v-else class="text-xs text-slate-400">-- : --</span>
                                </td>

                                <!-- Status Hari -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <Badge :variant="sch.is_day_off ? 'danger' : 'success'" size="sm">
                                        {{ sch.is_day_off ? 'Hari Libur' : 'Hari Kerja' }}
                                    </Badge>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openDayDetailsFromSchedule(sch)"
                                            class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                            title="Lihat Detail Hari"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <Link
                                            v-if="!sch.is_day_off"
                                            :href="route('employee.schedules.swap-personal.index')"
                                            class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                            title="Ajukan Tukar Shift"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <TableEmpty
                            v-else
                            :colspan="5"
                            title="Belum Ada Jadwal Kerja"
                            :message="`Jadwal kerja untuk bulan ${months[month]} ${year} belum dipublikasikan oleh manajemen HRD.`"
                        />
                    </template>
                </DataTable>
            </div>

            <!-- Day Details Modal (1:1 Standar Owner) -->
            <Modal :show="isDayDetailModalOpen" maxWidth="md" @close="isDayDetailModalOpen = false">
                <div class="space-y-4">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                {{ formatFullDate(selectedDateDetails?.dateStr) }}
                            </h3>
                        </div>
                    </div>

                    <!-- Holiday Alert if any -->
                    <div
                        v-if="selectedDateDetails?.holidays?.length > 0"
                        class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-start gap-2.5"
                    >
                        <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold">Hari Libur / Cuti Bersama:</span>
                            <p class="mt-0.5 font-medium">
                                {{ selectedDateDetails.holidays.map((h) => h.name).join(", ") }}
                            </p>
                        </div>
                    </div>

                    <!-- Schedule Breakdown Card -->
                    <div v-if="selectedDateDetails?.schedule" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <span class="text-xs text-slate-500 font-medium">Status Jadwal</span>
                            <Badge :variant="selectedDateDetails.schedule.is_day_off ? 'danger' : 'success'" size="sm">
                                {{ selectedDateDetails.schedule.is_day_off ? 'Hari Libur (Day Off)' : 'Hari Kerja' }}
                            </Badge>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Shift Penugasan</span>
                                <span class="font-semibold text-slate-800 text-sm">
                                    {{ selectedDateDetails.schedule.is_day_off ? 'Libur Terjadwal' : (selectedDateDetails.schedule.shift?.name || 'Shift Reguler') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Jam Kerja</span>
                                <span v-if="!selectedDateDetails.schedule.is_day_off" class="font-mono font-semibold text-slate-800 text-sm">
                                    {{ selectedDateDetails.schedule.shift?.clock_in?.slice(0, 5) || '08:00' }} - {{ selectedDateDetails.schedule.shift?.clock_out?.slice(0, 5) || '17:00' }} WIB
                                </span>
                                <span v-else class="text-slate-400 italic">-- : --</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Penempatan Cabang</span>
                                <span class="font-medium text-slate-700">
                                    {{ employee?.branch?.name || 'Kantor Utama' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Divisi</span>
                                <span class="font-medium text-slate-700">
                                    {{ employee?.division?.name || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-6 text-xs text-slate-400 italic">
                        Belum ada data jadwal kerja yang dipublikasikan pada tanggal ini.
                    </div>
                </div>

                <template #footer>
                    <Button type="button" variant="ghost" @click="isDayDetailModalOpen = false">
                        Tutup
                    </Button>
                </template>
            </Modal>
        </div>
    </AppLayout>
</template>
