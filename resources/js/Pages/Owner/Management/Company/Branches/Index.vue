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
    branches: {
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
    timezone: props.filters.timezone || "",
});

const branchToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const applyFilters = () => {
    router.get(
        route("owner.management.company.branches.index"),
        {
            search: filters.search || undefined,
            timezone: filters.timezone || undefined,
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
    filters.timezone = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.search || filters.timezone);
};

const confirmDelete = (branch) => {
    branchToDelete.value = branch;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!branchToDelete.value) return;
    deleteForm.delete(
        route("owner.management.company.branches.destroy", branchToDelete.value.id),
        {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                branchToDelete.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Manajemen Cabang" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Daftar Cabang Kantor
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola lokasi kantor, zona waktu, dan radius geolokasi absensi karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.company.branches.create')">
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
                        Tambah Cabang
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Cabang
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
                <!-- Row 1: Dropdown filter (Full width) -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- Timezone Filter -->
                    <div>
                        <Select
                            label="Zona Waktu"
                            v-model="filters.timezone"
                            :options="[
                                { value: '', label: 'Semua Zona Waktu' },
                                { value: 'Asia/Jakarta', label: 'WIB (Asia/Jakarta)' },
                                { value: 'Asia/Makassar', label: 'WITA (Asia/Makassar)' },
                                { value: 'Asia/Jayapura', label: 'WIT (Asia/Jayapura)' },
                            ]"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Search field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama atau kode cabang..."
                    />
                </div>
            </div>
        </div>

        <!-- Branches Table -->
        <DataTable
            :headers="[
                'Cabang',
                'Kode',
                'Zona Waktu',
                'Radius GPS',
                'Total Karyawan',
                '',
            ]"
        >
            <template v-if="branches.data && branches.data.length > 0">
                <tr
                    v-for="branch in branches.data"
                    :key="branch.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Name & Address -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ branch.name }}
                        </div>
                        <div class="text-xs text-slate-500 truncate max-w-xs mt-0.5">
                            {{ branch.address || "-" }}
                        </div>
                    </td>

                    <!-- Code -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge variant="secondary" size="sm">
                            {{ branch.code }}
                        </Badge>
                    </td>

                    <!-- Timezone -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge variant="info" size="sm">
                            {{ branch.timezone }}
                        </Badge>
                    </td>

                    <!-- Radius GPS -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600">
                        {{ branch.radius }} meter
                    </td>

                    <!-- Total Karyawan -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-slate-700">
                        {{ branch.employees_count || 0 }} Karyawan
                    </td>

                    <!-- Action -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="route('owner.management.company.branches.edit', branch.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Cabang"
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
                                @click="confirmDelete(branch)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                title="Hapus Cabang"
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
                    title="Belum ada data cabang"
                    message="Tambahkan cabang kantor baru atau sesuaikan filter pencarian Anda."
                    :colspan="6"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="branches" />

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Cabang
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus cabang
                    <span class="font-semibold text-slate-800">{{ branchToDelete?.name }}</span>?
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
