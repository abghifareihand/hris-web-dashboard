<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import DataTable from "@/Components/Table/DataTable.vue";
import TablePagination from "@/Components/Table/TablePagination.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import Modal from "@/Components/UI/Modal.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    shifts: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    search: props.filters.search || "",
});

const shiftToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const applyFilters = () => {
    router.get(
        route("owner.management.schedules.shifts.index"),
        {
            search: filters.search || undefined,
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

const resetFilters = () => {
    filters.search = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.search);
};

const confirmDelete = (shift) => {
    shiftToDelete.value = shift;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!shiftToDelete.value) return;
    deleteForm.delete(
        route("owner.management.schedules.shifts.destroy", shiftToDelete.value.id),
        {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                shiftToDelete.value = null;
            },
        },
    );
};

const formatTime = (timeStr) => {
    if (!timeStr) return "-";
    return timeStr.substring(0, 5);
};
</script>

<template>
    <Head title="Manajemen Shift Kerja" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Daftar Shift Kerja
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola jam operasional kerja, jam masuk presensi, dan jam pulang karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.schedules.shifts.create')">
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
                        Tambah Shift
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Shift Kerja
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

            <div class="p-6">
                <Input
                    label="Cari"
                    :modelValue="filters.search"
                    @update:modelValue="onSearchInput"
                    placeholder="Cari nama shift..."
                />
            </div>
        </div>

        <!-- Shifts Table -->
        <DataTable
            :headers="[
                'Nama Shift',
                'Jam Masuk',
                'Jam Pulang',
                '',
            ]"
        >
            <template v-if="shifts.data && shifts.data.length > 0">
                <tr
                    v-for="shift in shifts.data"
                    :key="shift.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Shift Name -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ shift.name }}
                        </div>
                    </td>

                    <!-- Clock In -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            {{ formatTime(shift.clock_in) }}
                        </span>
                    </td>

                    <!-- Clock Out -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                            {{ formatTime(shift.clock_out) }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="route('owner.management.schedules.shifts.edit', shift.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Shift"
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
                                @click="confirmDelete(shift)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Shift"
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
                    title="Belum ada data shift kerja"
                    message="Tambahkan shift baru untuk mulai mengatur jadwal kerja."
                    :colspan="4"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="shifts" />

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Shift Kerja
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus shift
                    <span class="font-semibold text-slate-800">{{ shiftToDelete?.name }}</span>?
                    Jadwal kerja karyawan yang sedang menggunakan shift ini mungkin terpengaruh.
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
