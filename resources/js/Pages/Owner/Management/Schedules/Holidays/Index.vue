<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Badge from "@/Components/UI/Badge.vue";
import DataTable from "@/Components/Table/DataTable.vue";
import TablePagination from "@/Components/Table/TablePagination.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import Modal from "@/Components/UI/Modal.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    holidays: {
        type: Object,
        required: true,
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

const filters = reactive({
    search: props.filters.search || "",
    month: props.filters.month || "",
    year: props.filters.year || "",
    branch_id: props.filters.branch_id || "",
    division_id: props.filters.division_id || "",
});

const holidayToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const applyFilters = () => {
    router.get(
        route("owner.management.schedules.holidays.index"),
        {
            search: filters.search || undefined,
            month: filters.month || undefined,
            year: filters.year || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
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
    filters.search = "";
    filters.month = "";
    filters.year = "";
    filters.branch_id = "";
    filters.division_id = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.month ||
        filters.year ||
        filters.branch_id ||
        filters.division_id,
    );
};

const confirmDelete = (holiday) => {
    holidayToDelete.value = holiday;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!holidayToDelete.value) return;
    deleteForm.delete(
        route("owner.management.schedules.holidays.destroy", holidayToDelete.value.id),
        {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                holidayToDelete.value = null;
            },
        },
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

const monthOptions = [
    { value: "", label: "Semua Bulan" },
    { value: "1", label: "Januari" },
    { value: "2", label: "Februari" },
    { value: "3", label: "Maret" },
    { value: "4", label: "April" },
    { value: "5", label: "Mei" },
    { value: "6", label: "Juni" },
    { value: "7", label: "Juli" },
    { value: "8", label: "Agustus" },
    { value: "9", label: "September" },
    { value: "10", label: "Oktober" },
    { value: "11", label: "November" },
    { value: "12", label: "Desember" },
];
</script>

<template>
    <Head title="Hari Libur Perusahaan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Hari Libur Perusahaan
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola hari libur nasional atau cuti bersama khusus perusahaan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.schedules.holidays.create')">
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
                        Tambah Hari Libur
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Hari Libur
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
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Month Filter -->
                    <div>
                        <Select
                            label="Bulan"
                            v-model="filters.month"
                            :options="monthOptions"
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
                </div>

                <!-- Search Field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama hari libur..."
                    />
                </div>
            </div>
        </div>

        <!-- Holidays Table -->
        <DataTable
            :headers="[
                'Nama Libur',
                'Periode Tanggal',
                'Cakupan Penempatan',
                '',
            ]"
        >
            <template v-if="holidays.data && holidays.data.length > 0">
                <tr
                    v-for="item in holidays.data"
                    :key="item.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Holiday Name -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ item.name }}
                        </div>
                    </td>

                    <!-- Date Range -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="text-sm font-medium text-slate-700">
                            {{ formatDate(item.start_date) }}
                            <span
                                v-if="item.end_date && item.end_date !== item.start_date"
                                class="text-slate-400 font-normal"
                            >
                                &mdash; {{ formatDate(item.end_date) }}
                            </span>
                        </div>
                    </td>

                    <!-- Coverage / Placement -->
                    <td class="px-5 py-3.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <Badge
                                v-if="!item.branch && !item.division"
                                variant="secondary"
                                size="sm"
                            >
                                Semua Cabang & Divisi
                            </Badge>
                            <template v-else>
                                <Badge
                                    v-if="item.branch"
                                    variant="info"
                                    size="sm"
                                >
                                    {{ item.branch.name }}
                                </Badge>
                                <Badge
                                    v-if="item.division"
                                    variant="warning"
                                    size="sm"
                                >
                                    {{ item.division.name }}
                                </Badge>
                            </template>
                        </div>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="route('owner.management.schedules.holidays.edit', item.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Hari Libur"
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
                                @click="confirmDelete(item)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Hari Libur"
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
                    title="Belum ada data hari libur"
                    message="Tambahkan hari libur baru atau sesuaikan filter pencarian Anda."
                    :colspan="4"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="holidays" />

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Hari Libur
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus data libur
                    <span class="font-semibold text-slate-800">{{ holidayToDelete?.name }}</span>?
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
