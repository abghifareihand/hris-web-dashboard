<script setup>
import { ref, computed, reactive, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Badge from "@/Components/UI/Badge.vue";
import DataTable from "@/Components/Table/DataTable.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import TablePagination from "@/Components/Table/TablePagination.vue";
import Modal from "@/Components/UI/Modal.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    shifts: {
        type: Array,
        default: () => [],
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
    schedules: {
        type: Object,
        required: true,
    },
    calendarSchedules: {
        type: Array,
        default: () => [],
    },
    holidays: {
        type: Array,
        default: () => [],
    },
    currentMonth: {
        type: Number,
        required: true,
    },
    currentYear: {
        type: Number,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const getInitialViewMode = () => {
    if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        return params.get("view") || "calendar";
    }
    return "calendar";
};

const viewMode = ref(getInitialViewMode());

const setViewMode = (mode) => {
    viewMode.value = mode;
    if (typeof window !== "undefined") {
        const url = new URL(window.location.href);
        url.searchParams.set("view", mode);
        window.history.replaceState({}, "", url);
    }
};

const selectedDateDetails = ref(null);
const isDayDetailModalOpen = ref(false);
const scheduleToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const filters = reactive({
    month: Number(props.filters.month) || props.currentMonth,
    year: Number(props.filters.year) || props.currentYear,
    branch_id: props.filters.branch_id || "",
    division_id: props.filters.division_id || "",
    shift_id: props.filters.shift_id || "",
    search: props.filters.search || "",
});

watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;
        filters.month = Number(newFilters.month) || props.currentMonth;
        filters.year = Number(newFilters.year) || props.currentYear;
        filters.branch_id = newFilters.branch_id || "";
        filters.division_id = newFilters.division_id || "";
        filters.shift_id = newFilters.shift_id || "";
        filters.search = newFilters.search || "";
    },
    { deep: true },
);

const applyFilters = () => {
    router.get(
        route("owner.management.schedules.work.index"),
        {
            month: filters.month,
            year: filters.year,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            shift_id: filters.shift_id || undefined,
            search: filters.search || undefined,
            view: viewMode.value !== "calendar" ? viewMode.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
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
    filters.month = props.currentMonth;
    filters.year = props.currentYear;
    filters.branch_id = "";
    filters.division_id = "";
    filters.shift_id = "";
    filters.search = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.shift_id ||
        Number(filters.month) !== Number(props.currentMonth) ||
        Number(filters.year) !== Number(props.currentYear),
    );
};

// Calendar calculations
const daysOfWeek = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

const calendarDays = computed(() => {
    const year = Number(filters.year);
    const month = Number(filters.month) - 1; // 0-indexed in JS Date
    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDaysInMonth = new Date(year, month + 1, 0).getDate();

    const days = [];

    // Pre-month empty padding
    for (let i = 0; i < firstDayIndex; i++) {
        days.push({ dayNumber: null, dateStr: null, isCurrentMonth: false, schedules: [], holidays: [] });
    }

    const calList = props.calendarSchedules && props.calendarSchedules.length > 0
        ? props.calendarSchedules
        : (props.schedules?.data || []);

    // Days in current month
    for (let day = 1; day <= totalDaysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

        // Find schedules on this day
        const daySchedules = calList.filter((s) => s.day === day || s.date === dateStr);

        // Find holidays on this day
        const dayHolidays = props.holidays.filter((h) => {
            const start = h.start_date.substring(0, 10);
            const end = h.end_date ? h.end_date.substring(0, 10) : start;
            return dateStr >= start && dateStr <= end;
        });

        days.push({
            dayNumber: day,
            dateStr: dateStr,
            isCurrentMonth: true,
            schedules: daySchedules,
            holidays: dayHolidays,
        });
    }

    return days;
});

const openDayDetails = (cell) => {
    if (!cell.isCurrentMonth) return;
    selectedDateDetails.value = cell;
    isDayDetailModalOpen.value = true;
};

const confirmDelete = (schedule) => {
    scheduleToDelete.value = schedule;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!scheduleToDelete.value) return;
    deleteForm.delete(route("owner.management.schedules.work.destroy", scheduleToDelete.value.id), {
        onSuccess: () => {
            if (isDayDetailModalOpen.value && selectedDateDetails.value) {
                selectedDateDetails.value.schedules = selectedDateDetails.value.schedules.filter(
                    (s) => s.id !== scheduleToDelete.value.id,
                );
            }
            isDeleteModalOpen.value = false;
            scheduleToDelete.value = null;
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

const formatDayName = (dateStr) => {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        weekday: "long",
    });
};

const months = [
    { value: 1, label: "Januari" },
    { value: 2, label: "Februari" },
    { value: 3, label: "Maret" },
    { value: 4, label: "April" },
    { value: 5, label: "Mei" },
    { value: 6, label: "Juni" },
    { value: 7, label: "Juli" },
    { value: 8, label: "Agustus" },
    { value: 9, label: "September" },
    { value: 10, label: "Oktober" },
    { value: 11, label: "November" },
    { value: 12, label: "Desember" },
];

const years = computed(() => {
    const base = Number(props.currentYear) || new Date().getFullYear();
    return [base - 2, base - 1, base, base + 1, base + 2].map((y) => ({
        value: y,
        label: String(y),
    }));
});

const shiftOptions = computed(() => [
    { value: "", label: "Semua Shift", dotClass: "bg-slate-300" },
    { value: "day_off", label: "Hari Libur (Day Off)", dotClass: "bg-rose-500" },
    ...props.shifts.map((s) => ({
        value: s.id,
        label: s.name,
        dotClass: "bg-emerald-500",
    })),
]);
</script>

<template>
    <Head title="Manajemen Jadwal Kerja Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Jadwal Kerja Karyawan
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Visualisasi kalender jadwal tugas, penempatan shift, dan hari libur seluruh tim.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Mode View Toggle -->
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

                <Link :href="route('owner.management.schedules.work.create')">
                    <Button variant="primary">
                        <svg
                            class="w-4 h-4 mr-1.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Buat Jadwal Baru
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Jadwal Kerja
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
                <!-- Dropdown Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Bulan Filter -->
                    <div>
                        <Select
                            label="Bulan"
                            v-model="filters.month"
                            :options="months"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Tahun Filter -->
                    <div>
                        <Select
                            label="Tahun"
                            v-model="filters.year"
                            :options="years"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Branch Filter -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="
                                branches.map((b) => ({
                                    value: b.id,
                                    label: b.name,
                                }))
                            "
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Division Filter -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="
                                divisions.map((d) => ({
                                    value: d.id,
                                    label: d.name,
                                }))
                            "
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Shift Filter -->
                    <div>
                        <Select
                            label="Shift"
                            v-model="filters.shift_id"
                            :options="shiftOptions"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Search Field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama karyawan atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- 1. CALENDAR VIEW -->
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
                        'min-h-[110px] p-2 transition flex flex-col justify-between group',
                        !cell.isCurrentMonth ? 'bg-slate-50/40 select-none' : 'hover:bg-emerald-50/20 cursor-pointer',
                    ]"
                    @click="cell.isCurrentMonth && openDayDetails(cell)"
                >
                    <div class="flex items-center justify-between">
                        <span
                            v-if="cell.dayNumber"
                            :class="[
                                'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                                cell.holidays.length > 0 ? 'bg-rose-100 text-rose-700 font-extrabold' : 'text-slate-700 group-hover:bg-emerald-600 group-hover:text-white transition',
                            ]"
                        >
                            {{ cell.dayNumber }}
                        </span>
                        <!-- Total Assigned badge -->
                        <span
                            v-if="cell.schedules.length > 0"
                            class="text-[10px] font-semibold text-slate-400 group-hover:text-emerald-700"
                        >
                            {{ cell.schedules.length }} Orang
                        </span>
                    </div>

                    <!-- Mini indicators for items -->
                    <div class="mt-1 space-y-1">
                        <!-- Holiday Tag -->
                        <div
                            v-for="h in cell.holidays.slice(0, 1)"
                            :key="h.id"
                            class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-100 truncate"
                        >
                            {{ h.name }}
                        </div>

                        <!-- Schedules summary tags -->
                        <div
                            v-for="s in cell.schedules.slice(0, 2)"
                            :key="s.id"
                            :class="[
                                'px-1.5 py-0.5 rounded text-[10px] truncate flex items-center justify-between',
                                s.is_day_off ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-800 border border-emerald-100',
                            ]"
                        >
                            <span class="font-medium truncate">{{ s.name }}</span>
                            <span class="text-[9px] font-semibold ml-1 shrink-0">{{ s.shift_name }}</span>
                        </div>

                        <div v-if="cell.schedules.length > 2" class="text-[10px] text-slate-400 font-medium pl-1">
                            +{{ cell.schedules.length - 2 }} lainnya
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TABLE VIEW (Matching Employees/Index.vue Pattern with Pagination) -->
        <div v-show="viewMode === 'table'" class="space-y-6">
            <DataTable
                :headers="[
                    'Karyawan',
                    'Tanggal',
                    'Shift Kerja',
                    'Jabatan & Divisi',
                    'Penempatan Cabang',
                    '',
                ]"
            >
                <template v-if="schedules.data && schedules.data.length > 0">
                    <tr
                        v-for="sched in schedules.data"
                        :key="sched.id"
                        class="hover:bg-slate-50/70 transition-colors"
                    >
                        <!-- Karyawan (Avatar, Name, NIP / Email matching Employees/Index.vue) -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="sched.employee?.user?.avatar"
                                    :src="sched.employee.user.avatar"
                                    :alt="sched.employee.name"
                                    class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                                />
                                <div
                                    v-else
                                    class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                                >
                                    {{ sched.employee?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-800 truncate">
                                        {{ sched.employee?.name }}
                                    </div>
                                    <div class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
                                        <span>{{ sched.employee?.nip && sched.employee.nip !== '-' ? 'NIP: ' + sched.employee.nip : sched.employee?.email }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Tanggal -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-800">
                                {{ formatDate(sched.date) }}
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">
                                {{ formatDayName(sched.date) }}
                            </div>
                        </td>

                        <!-- Shift Kerja -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <Badge
                                :variant="sched.is_day_off ? 'secondary' : 'success'"
                                size="sm"
                            >
                                {{ sched.shift_name }}
                                <span v-if="sched.clock_in" class="ml-1 font-mono font-normal">
                                    ({{ sched.clock_in }} - {{ sched.clock_out }})
                                </span>
                            </Badge>
                        </td>

                        <!-- Jabatan & Divisi -->
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="font-medium text-slate-700 text-sm">
                                {{ sched.employee?.position?.name || '-' }}
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">
                                {{ sched.employee?.division?.name || '-' }}
                            </div>
                        </td>

                        <!-- Penempatan Cabang -->
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600">
                            {{ sched.allowed_branches }}
                        </td>

                        <!-- Aksi -->
                        <td class="px-5 py-3.5 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <Link
                                    :href="route('owner.management.schedules.work.edit', sched.id)"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                    title="Edit Jadwal"
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
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </Link>
                                <button
                                    type="button"
                                    @click="confirmDelete(sched)"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Hapus Jadwal"
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
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <TableEmpty
                        title="Belum ada data jadwal kerja"
                        message="Tidak ada data jadwal kerja untuk filter bulan & kriteria yang dipilih."
                        :colspan="6"
                    />
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="schedules" />
        </div>

        <!-- Day Details Modal -->
        <Modal :show="isDayDetailModalOpen" maxWidth="lg" @close="isDayDetailModalOpen = false">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Jadwal Tanggal: {{ selectedDateDetails?.dateStr }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ selectedDateDetails?.schedules.length || 0 }} Karyawan Terjadwal
                        </p>
                    </div>
                </div>

                <!-- Holiday alert if any -->
                <div v-if="selectedDateDetails?.holidays.length > 0" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
                    <span class="font-bold">Hari Libur:</span>
                    {{ selectedDateDetails.holidays.map((h) => h.name).join(", ") }}
                </div>

                <!-- Schedule List -->
                <div v-if="selectedDateDetails?.schedules.length > 0" class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <div
                        v-for="item in selectedDateDetails.schedules"
                        :key="item.id"
                        class="py-3 flex items-center justify-between gap-3"
                    >
                        <div>
                            <div class="font-semibold text-sm text-slate-800">{{ item.name }}</div>
                            <div class="text-xs text-slate-400">{{ item.division }} &bull; {{ item.position }}</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    item.is_day_off ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                                ]"
                            >
                                {{ item.shift_name }}
                            </span>
                            <div class="inline-flex items-center gap-1">
                                <Link
                                    :href="route('owner.management.schedules.work.edit', item.id)"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                    title="Edit Jadwal"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </Link>
                                <button
                                    type="button"
                                    @click="confirmDelete(item)"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Hapus Jadwal"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-xs text-slate-400 italic">
                    Belum ada penugasan kerja pada tanggal ini.
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    @click="isDayDetailModalOpen = false"
                >
                    Tutup
                </Button>
            </template>
        </Modal>

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Jadwal Kerja
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus jadwal untuk
                    <span class="font-semibold text-slate-800">{{ scheduleToDelete?.name }}</span>?
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="deleteForm.processing"
                    @click="isDeleteModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    :loading="deleteForm.processing"
                    :disabled="deleteForm.processing"
                    @click="executeDelete"
                >
                    Ya, Hapus
                </Button>
            </template>
        </Modal>
    </div>
</template>
