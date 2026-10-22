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
    feedbacks: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const cleanFilterValue = (val) => (!val || val === "all" ? "" : val);

const filters = reactive({
    type: cleanFilterValue(props.filters?.type),
    start_date: props.filters?.start_date || "",
    end_date: props.filters?.end_date || "",
    search: props.filters?.search || "",
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.type = cleanFilterValue(newFilters?.type);
        filters.start_date = newFilters?.start_date || "";
        filters.end_date = newFilters?.end_date || "";
        filters.search = newFilters?.search || "";
    },
    { deep: true }
);

const typeOptions = [
    { value: "anonymous", label: "Hanya Anonim (Dirahasiakan)" },
    { value: "identified", label: "Identitas Terbuka (Karyawan)" },
];

const applyFilters = () => {
    router.get(
        route("owner.feedbacks.index"),
        {
            type: filters.type && filters.type !== "all" ? filters.type : undefined,
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
    filters.type = "";
    filters.start_date = "";
    filters.end_date = "";
    filters.search = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        (filters.type && filters.type !== "all") ||
        filters.start_date ||
        filters.end_date
    );
};

// Delete Confirmation Modal State
const itemToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    deleteForm.delete(route("owner.feedbacks.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            itemToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Data Kritik & Saran" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Data Kritik & Saran
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola dan tinjau kritik serta saran yang dikirimkan oleh karyawan untuk kemajuan perusahaan.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Kritik & Saran
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
                <!-- Dropdown Filters Row (Tipe Pengirim & Periode Dikirim) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Tipe Pengirim -->
                    <div>
                        <Select
                            label="Tipe Pengirim"
                            v-model="filters.type"
                            :options="typeOptions"
                            all-label="Semua Tipe Pengirim"
                            placeholder="Semua Tipe Pengirim"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Periode Dikirim -->
                    <div>
                        <label class="form-label">
                            Periode Dikirim
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
                        label="Cari Kritik & Saran"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Ketik target, kritik, saran, atau nama karyawan..."
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

        <!-- Feedbacks Table -->
        <DataTable
            :headers="[
                'Pengirim',
                'Sasaran',
                'Waktu',
                '',
            ]"
        >
            <template v-if="feedbacks.data && feedbacks.data.length > 0">
                <tr
                    v-for="item in feedbacks.data"
                    :key="item.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- 1. Pengirim -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div v-if="item.is_anonymous">
                            <Badge variant="secondary">
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Anonim
                                </span>
                            </Badge>
                        </div>
                        <div v-else-if="item.employee">
                            <div class="font-semibold text-slate-800 text-xs sm:text-sm">
                                {{ item.employee.name }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ item.employee.position }}
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400">-</div>
                    </td>

                    <!-- 2. Sasaran -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge variant="primary">
                            {{ item.target }}
                        </Badge>
                    </td>

                    <!-- 3. Tanggal (lengkap beserta hari dan jam) -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="font-medium text-slate-800 text-xs sm:text-sm">
                            {{ item.date_formatted }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            {{ item.time_ago }}
                        </div>
                    </td>

                    <!-- 4. Actions -->
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Detail Button -->
                            <Link
                                :href="route('owner.feedbacks.show', item.id)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Lihat Detail"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </Link>

                            <!-- Delete Button -->
                            <button
                                type="button"
                                @click="confirmDelete(item)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Masukan"
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
                    title="Tidak ada data ditemukan"
                    message="Belum ada kritik dan saran yang ditemukan atau sesuaikan filter pencarian Anda."
                    :colspan="4"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="feedbacks" />

        <!-- Modal Konfirmasi Hapus (Matches Pending/Index.vue style) -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-2">
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Kritik & Saran
                </h3>
                <p class="text-sm text-slate-500">
                    Apakah Anda yakin ingin menghapus data kritik dan saran ini? Tindakan ini tidak dapat dibatalkan.
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
                    @click="executeDelete"
                    :loading="deleteForm.processing"
                    :disabled="deleteForm.processing"
                >
                    Ya, Hapus
                </Button>
            </template>
        </Modal>
    </div>
</template>
