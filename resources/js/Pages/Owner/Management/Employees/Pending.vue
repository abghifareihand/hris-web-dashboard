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
import TablePagination from "@/Components/Table/TablePagination.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    pendingEmployees: {
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
});

const selectedPending = ref(null);
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const actionForm = useForm({});

const applyFilters = () => {
    router.get(
        route("owner.management.employees.pending.index"),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            position_id: filters.position_id || undefined,
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
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.position_id,
    );
};

const openApproveModal = (item) => {
    selectedPending.value = item;
    isApproveModalOpen.value = true;
};

const openRejectModal = (item) => {
    selectedPending.value = item;
    isRejectModalOpen.value = true;
};

const executeApprove = () => {
    if (!selectedPending.value) return;
    actionForm.post(
        route(
            "owner.management.employees.pending.approve",
            selectedPending.value.id,
        ),
        {
            onSuccess: () => {
                isApproveModalOpen.value = false;
                selectedPending.value = null;
            },
        },
    );
};

const executeReject = () => {
    if (!selectedPending.value) return;
    actionForm.post(
        route(
            "owner.management.employees.pending.reject",
            selectedPending.value.id,
        ),
        {
            onSuccess: () => {
                isRejectModalOpen.value = false;
                selectedPending.value = null;
            },
        },
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head title="Persetujuan Karyawan Baru" />

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
                        Persetujuan Karyawan
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar calon karyawan menunggu verifikasi.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Calon Karyawan
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
                <!-- Row 1: Dropdown Filters (Cabang, Divisi, Jabatan) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Cabang -->
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

                    <!-- Divisi -->
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

                    <!-- Jabatan -->
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
                        placeholder="Cari nama, email, atau telepon..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable
            :headers="[
                'Nama',
                'Phone',
                'Penempatan',
                'Alamat',
                'Pengajuan',
                '',
            ]"
        >
            <template
                v-if="pendingEmployees.data && pendingEmployees.data.length > 0"
            >
                <tr
                    v-for="pendingEmployee in pendingEmployees.data"
                    :key="pendingEmployee.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Name & Email -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="pendingEmployee.user?.avatar"
                                :src="pendingEmployee.user.avatar"
                                :alt="pendingEmployee.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{
                                    pendingEmployee.name
                                        ?.charAt(0)
                                        .toUpperCase()
                                }}
                            </div>
                            <div class="min-w-0">
                                <div
                                    class="font-semibold text-slate-800 truncate"
                                >
                                    {{ pendingEmployee.name }}
                                </div>
                                <div
                                    class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5"
                                >
                                    <span>{{ pendingEmployee.email }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Phone -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge variant="success" size="sm">
                            {{ pendingEmployee.phone || "-" }}
                        </Badge>
                    </td>

                    <!-- Penempatan -->
                    <td class="px-5 py-3.5">
                        <div class="text-xs font-medium text-slate-700">
                            <span class="font-semibold text-slate-900">{{
                                pendingEmployee.branch?.name || "-"
                            }}</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Divisi {{ pendingEmployee.division?.name || "-" }} -
                            Jabatan {{ pendingEmployee.position?.name || "-" }}
                        </div>
                    </td>

                    <!-- Alamat -->
                    <td
                        class="px-5 py-3.5 text-xs text-slate-600 max-w-xs truncate"
                    >
                        {{ pendingEmployee.address || "-" }}
                    </td>

                    <!-- Pengajuan -->
                    <td
                        class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap"
                    >
                        {{ formatDate(pendingEmployee.created_at) }}
                    </td>

                    <!-- Action -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-2">
                            <button
                                type="button"
                                @click="openRejectModal(pendingEmployee)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50/90 hover:bg-rose-100/90 border border-rose-200/80 rounded-lg transition"
                                title="Tolak Pendaftaran"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                                    />
                                </svg>
                                <span>Tolak</span>
                            </button>
                            <button
                                type="button"
                                @click="openApproveModal(pendingEmployee)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50/90 hover:bg-emerald-100/90 border border-emerald-200/80 rounded-lg transition"
                                title="Setujui Karyawan"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>Setujui</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty
                    :colspan="6"
                    message="Tidak ada pendaftaran karyawan baru yang sedang menunggu persetujuan."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="pendingEmployees" />

        <!-- Approve Modal -->
        <Modal
            :show="isApproveModalOpen"
            maxWidth="sm"
            @close="isApproveModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Setujui Karyawan
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menyetujui karyawan
                    <span class="font-semibold text-slate-900">{{ selectedPending?.name }}</span>?
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="actionForm.processing"
                    @click="isApproveModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    @click="executeApprove"
                    :loading="actionForm.processing"
                    :disabled="actionForm.processing"
                >
                    Ya, Setujui
                </Button>
            </template>
        </Modal>

        <!-- Reject Modal -->
        <Modal
            :show="isRejectModalOpen"
            maxWidth="sm"
            @close="isRejectModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Tolak Pendaftaran
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menolak permohonan registrasi dari
                    <span class="font-semibold text-slate-900">{{ selectedPending?.name }}</span>?
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="actionForm.processing"
                    @click="isRejectModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="executeReject"
                    :loading="actionForm.processing"
                    :disabled="actionForm.processing"
                >
                    Ya, Tolak
                </Button>
            </template>
        </Modal>
    </div>
</template>
