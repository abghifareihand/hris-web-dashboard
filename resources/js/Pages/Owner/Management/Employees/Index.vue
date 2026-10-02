<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
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
    employees: {
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
    positions: {
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
    branch_id: props.filters.branch_id || "",
    division_id: props.filters.division_id || "",
    position_id: props.filters.position_id || "",
    is_active: props.filters.is_active || "",
});

const employeeToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const applyFilters = () => {
    router.get(
        route("owner.management.employees.index"),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            position_id: filters.position_id || undefined,
            is_active: filters.is_active || undefined,
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
    filters.branch_id = "";
    filters.division_id = "";
    filters.position_id = "";
    filters.is_active = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.position_id ||
        filters.is_active,
    );
};

const confirmDelete = (employee) => {
    employeeToDelete.value = employee;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!employeeToDelete.value) return;
    deleteForm.delete(
        route("owner.management.employees.destroy", employeeToDelete.value.id),
        {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                employeeToDelete.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Manajemen Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <h1
                        class="text-2xl font-bold text-slate-900 tracking-tight"
                    >
                        Daftar Karyawan
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola data karyawan perusahaan Anda.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.employees.create')">
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
                        Tambah Karyawan
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Karyawan
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
                <!-- Row 1: Dropdowns (Status, Cabang, Divisi, Jabatan) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.is_active"
                            :options="[
                                {
                                    value: '',
                                    label: 'Semua Status',
                                    dotClass: 'bg-slate-300',
                                },
                                {
                                    value: '1',
                                    label: 'Aktif',
                                    dotClass: 'bg-emerald-500',
                                },
                                {
                                    value: '0',
                                    label: 'Non-aktif',
                                    dotClass: 'bg-rose-500',
                                },
                            ]"
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

                    <!-- Position / Jabatan Filter -->
                    <div>
                        <Select
                            label="Jabatan"
                            v-model="filters.position_id"
                            :options="
                                positions.map((p) => ({
                                    value: p.id,
                                    label: p.name,
                                }))
                            "
                            all-label="Semua Jabatan"
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
                        placeholder="Cari nama, email, atau NIK..."
                    />
                </div>
            </div>
        </div>

        <!-- Employee Table -->
        <DataTable
            :headers="[
                'Nama',
                'NIP',
                'Jabatan & Divisi',
                'Cabang',
                'Status',
                '',
            ]"
        >
            <template v-if="employees.data && employees.data.length > 0">
                <tr
                    v-for="employee in employees.data"
                    :key="employee.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Name & Email -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="employee.user?.avatar"
                                :src="employee.user.avatar"
                                :alt="employee.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{ employee.name?.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <div
                                    class="font-semibold text-slate-800 truncate"
                                >
                                    {{ employee.name }}
                                </div>
                                <div
                                    class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5"
                                >
                                    <span>{{ employee.email }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- NIP -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge variant="secondary" size="sm">
                            {{ employee.nip || "-" }}
                        </Badge>
                    </td>

                    <!-- Position & Division -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="font-medium text-slate-700 text-sm">
                            {{ employee.position?.name || "Belum diatur" }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ employee.division?.name || "-" }}
                        </div>
                    </td>

                    <!-- Branch -->
                    <td
                        class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600"
                    >
                        {{ employee.branch?.name || "Semua Cabang" }}
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge
                            :variant="employee.is_active ? 'success' : 'danger'"
                            size="sm"
                        >
                            {{ employee.is_active ? "Aktif" : "Non-aktif" }}
                        </Badge>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <Link
                                :href="
                                    route(
                                        'owner.management.employees.show',
                                        employee.id,
                                    )
                                "
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Detail Profil"
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
                            <Link
                                :href="
                                    route(
                                        'owner.management.employees.edit',
                                        employee.id,
                                    )
                                "
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Karyawan"
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
                                @click="confirmDelete(employee)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Karyawan"
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
                    title="Belum ada data karyawan"
                    message="Tambahkan karyawan baru atau sesuaikan filter pencarian Anda."
                    :colspan="6"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="employees" />

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Karyawan
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus karyawan
                    {{ employeeToDelete?.name }}?
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
