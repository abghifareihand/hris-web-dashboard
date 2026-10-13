<script setup>
import { ref, reactive, watch, computed } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import DatePickerRange from "@/Components/UI/DatePickerRange.vue";
import Badge from "@/Components/UI/Badge.vue";
import Modal from "@/Components/UI/Modal.vue";
import DataTable from "@/Components/Table/DataTable.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import TablePagination from "@/Components/Table/TablePagination.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    announcements: {
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
    branch_id: props.filters?.branch_id || "",
    division_id: props.filters?.division_id || "",
    start_date: props.filters?.start_date || "",
    end_date: props.filters?.end_date || "",
    search: props.filters?.search || "",
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.branch_id = newFilters?.branch_id || "";
        filters.division_id = newFilters?.division_id || "";
        filters.start_date = newFilters?.start_date || "";
        filters.end_date = newFilters?.end_date || "";
        filters.search = newFilters?.search || "";
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route("owner.announcements.index"),
        {
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
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

const onDateRangeChange = (range) => {
    if (range && typeof range === "object") {
        if ("startDate" in range) filters.start_date = range.startDate || "";
        if ("endDate" in range) filters.end_date = range.endDate || "";
    }
    applyFilters();
};

const resetFilters = () => {
    filters.branch_id = "";
    filters.division_id = "";
    filters.start_date = "";
    filters.end_date = "";
    filters.search = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.start_date ||
        filters.end_date
    );
};

const branchOptions = computed(() => [
    { value: "", label: "Semua Cabang" },
    { value: "all", label: "Khusus Semua Cabang (Global)" },
    ...props.branches.map((b) => ({ value: String(b.id), label: b.name })),
]);

const divisionOptions = computed(() => [
    { value: "", label: "Semua Divisi" },
    { value: "all", label: "Khusus Semua Divisi (Global)" },
    ...props.divisions.map((d) => ({ value: String(d.id), label: d.name })),
]);

// Delete Modal State
const isDeleteModalOpen = ref(false);
const announcementToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (ann) => {
    announcementToDelete.value = ann;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!announcementToDelete.value) return;

    deleteForm.delete(route("owner.announcements.destroy", announcementToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            announcementToDelete.value = null;
        },
    });
};

const getStatusBadge = (startDate, endDate) => {
    const today = new Date().toISOString().slice(0, 10);
    const start = startDate ? startDate.slice(0, 10) : "";
    const end = endDate ? endDate.slice(0, 10) : "";

    if (start && start > today) {
        return { label: "Jadwal Mendatang", variant: "primary" };
    }
    if (end && end < today) {
        return { label: "Sudah Berakhir", variant: "secondary" };
    }
    return { label: "Sedang Aktif", variant: "success" };
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Data Pengumuman" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Data Pengumuman
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola dan siarkan informasi penting bagi seluruh karyawan perusahaan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.announcements.create')">
                    <Button variant="primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Pengumuman</span>
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Controls (Style matching Employees/Index) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Pengumuman
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
                <!-- Dropdown Filters Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Cabang Sasaran -->
                    <div>
                        <Select
                            label="Cabang Sasaran"
                            v-model="filters.branch_id"
                            :options="branchOptions"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi Sasaran -->
                    <div>
                        <Select
                            label="Divisi Sasaran"
                            v-model="filters.division_id"
                            :options="divisionOptions"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Periode Tayang -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            Periode Tayang
                        </label>
                        <DatePickerRange
                            :start-date="filters.start_date"
                            :end-date="filters.end_date"
                            placeholder="Semua Periode"
                            @change="onDateRangeChange"
                        />
                    </div>
                </div>

                <!-- Search Input Row -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari Pengumuman"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Ketik judul pengumuman atau kata kunci keterangan..."
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

        <!-- Announcements Table -->
        <DataTable
            :headers="[
                'Pengumuman',
                'Sasaran',
                'Periode Tayang',
                'Status',
                '',
            ]"
        >
            <template v-if="announcements.data && announcements.data.length > 0">
                <tr
                    v-for="ann in announcements.data"
                    :key="ann.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Pengumuman (Judul & Keterangan singkat) -->
                    <td class="px-5 py-3.5 max-w-sm">
                        <div class="font-bold text-slate-900 text-sm">
                            {{ ann.title }}
                        </div>
                        <div class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                            {{ ann.content }}
                        </div>
                    </td>

                    <!-- Sasaran (Cabang & Divisi) -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="font-semibold text-slate-800 text-xs">
                            {{ ann.branch?.name || 'Semua Cabang' }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            {{ ann.division?.name || 'Semua Divisi' }}
                        </div>
                    </td>

                    <!-- Periode Tayang -->
                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                        <span class="font-medium text-slate-700 text-xs">
                            {{ formatDate(ann.start_date) }}
                        </span>
                        <span class="text-slate-400 text-xs"> s/d </span>
                        <span class="font-medium text-slate-700 text-xs">
                            {{ formatDate(ann.end_date) }}
                        </span>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 text-center">
                        <Badge :variant="getStatusBadge(ann.start_date, ann.end_date).variant">
                            {{ getStatusBadge(ann.start_date, ann.end_date).label }}
                        </Badge>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Detail -->
                            <Link
                                :href="route('owner.announcements.show', ann.id)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Lihat Detail"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </Link>

                            <!-- Edit -->
                            <Link
                                :href="route('owner.announcements.edit', ann.id)"
                                class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                title="Edit Pengumuman"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>

                            <!-- Delete -->
                            <button
                                type="button"
                                @click="confirmDelete(ann)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Pengumuman"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>

            <template v-else>
                <TableEmpty
                    title="Belum ada data pengumuman"
                    message="Tambahkan pengumuman baru atau sesuaikan filter pencarian Anda."
                    :colspan="5"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="announcements" />

        <!-- Delete Modal Confirmation -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Pengumuman
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus pengumuman
                    <span class="font-semibold text-slate-700">"{{ announcementToDelete?.title }}"</span>? Tindakan ini tidak dapat dibatalkan.
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
