<script setup>
import { ref, reactive } from "vue";
import { Head, router } from "@inertiajs/vue3";
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
    swaps: {
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
    status: props.filters.status || "",
    branch_id: props.filters.branch_id || "",
    division_id: props.filters.division_id || "",
});

const selectedSwap = ref(null);
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isProcessing = ref(false);

const applyFilters = () => {
    router.get(
        route("owner.management.schedules.swap-team.index"),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
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
    filters.status = "";
    filters.branch_id = "";
    filters.division_id = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.status ||
        filters.branch_id ||
        filters.division_id,
    );
};

const openApprove = (swap) => {
    selectedSwap.value = swap;
    isApproveModalOpen.value = true;
};

const openReject = (swap) => {
    selectedSwap.value = swap;
    isRejectModalOpen.value = true;
};

const executeApprove = () => {
    if (!selectedSwap.value) return;
    isProcessing.value = true;
    router.post(
        route("owner.management.schedules.swap-team.approve", selectedSwap.value.id),
        {},
        {
            onFinish: () => {
                isProcessing.value = false;
                isApproveModalOpen.value = false;
                selectedSwap.value = null;
            },
        },
    );
};

const executeReject = () => {
    if (!selectedSwap.value) return;
    isProcessing.value = true;
    router.post(
        route("owner.management.schedules.swap-team.reject", selectedSwap.value.id),
        {},
        {
            onFinish: () => {
                isProcessing.value = false;
                isRejectModalOpen.value = false;
                selectedSwap.value = null;
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

const statusVariant = (status) => {
    switch (status) {
        case "approved":
            return "success";
        case "rejected":
            return "danger";
        default:
            return "warning";
    }
};

const statusLabel = (status) => {
    switch (status) {
        case "approved":
            return "Disetujui";
        case "rejected":
            return "Ditolak";
        default:
            return "Menunggu";
    }
};

const statusOptions = [
    { value: "", label: "Semua Status", dotClass: "bg-slate-300" },
    { value: "pending", label: "Menunggu Persetujuan", dotClass: "bg-amber-500" },
    { value: "approved", label: "Disetujui", dotClass: "bg-emerald-500" },
    { value: "rejected", label: "Ditolak", dotClass: "bg-rose-500" },
];
</script>

<template>
    <Head title="Persetujuan Tukar Jadwal Antar Tim" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Tukar Jadwal Antar Tim
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar permohonan pertukaran shift kerja antar dua rekan kerja/tim yang membutuhkan persetujuan owner.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Tukar Jadwal Tim
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
                    <!-- Status Filter -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.status"
                            :options="statusOptions"
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
                        placeholder="Cari nama pemohon atau rekan target..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable
            :headers="[
                'Pemohon',
                'Rekan Target',
                'Pertukaran Tanggal',
                'Alasan',
                'Status',
                '',
            ]"
        >
            <template v-if="swaps.data && swaps.data.length > 0">
                <tr
                    v-for="item in swaps.data"
                    :key="item.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <!-- Requestor -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ item.requestor?.name || "-" }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ item.requestor?.branch?.name || "-" }} &bull; {{ item.requestor?.division?.name || "-" }}
                        </div>
                    </td>

                    <!-- Target Employee -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ item.target_employee?.name || "-" }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ item.target_employee?.branch?.name || "-" }} &bull; {{ item.target_employee?.division?.name || "-" }}
                        </div>
                    </td>

                    <!-- Dates -->
                    <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                        <div>
                            <span class="text-slate-400 font-medium">Pemohon:</span>
                            <span class="font-semibold text-slate-800 ml-1">{{ formatDate(item.requestor_work_date) }}</span>
                        </div>
                        <div class="mt-0.5">
                            <span class="text-slate-400 font-medium">Rekan:</span>
                            <span class="font-semibold text-emerald-700 ml-1">{{ formatDate(item.target_work_date) }}</span>
                        </div>
                    </td>

                    <!-- Reason -->
                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs truncate" :title="item.reason">
                        {{ item.reason || "-" }}
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge :variant="statusVariant(item.status)" size="sm">
                            {{ statusLabel(item.status) }}
                        </Badge>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <template v-if="item.status === 'pending'">
                            <div class="inline-flex items-center gap-1.5">
                                <Button
                                    variant="primary"
                                    size="sm"
                                    class="!px-3 !py-1 text-xs"
                                    @click="openApprove(item)"
                                >
                                    Setujui
                                </Button>
                                <Button
                                    variant="danger"
                                    size="sm"
                                    class="!px-3 !py-1 text-xs"
                                    @click="openReject(item)"
                                >
                                    Tolak
                                </Button>
                            </div>
                        </template>
                        <span v-else class="text-xs text-slate-400 italic">Selesai</span>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty
                    title="Belum ada permohonan tukar jadwal tim"
                    message="Tidak ada permohonan tukar jadwal antar rekan kerja yang ditemukan atau sesuaikan filter pencarian Anda."
                    :colspan="6"
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="swaps" />

        <!-- Modal Setujui -->
        <Modal
            :show="isApproveModalOpen"
            maxWidth="sm"
            @close="isApproveModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Setujui Tukar Jadwal Tim
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda menyetujui pertukaran jadwal antara
                    <span class="font-semibold text-slate-800">{{ selectedSwap?.requestor?.name }}</span>
                    dan
                    <span class="font-semibold text-slate-800">{{ selectedSwap?.target_employee?.name }}</span>?
                    Jadwal kerja kedua karyawan pada tanggal tersebut akan otomatis ditukar.
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="isProcessing"
                    @click="isApproveModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    :loading="isProcessing"
                    :disabled="isProcessing"
                    @click="executeApprove"
                >
                    Ya, Setujui
                </Button>
            </template>
        </Modal>

        <!-- Modal Tolak -->
        <Modal
            :show="isRejectModalOpen"
            maxWidth="sm"
            @close="isRejectModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Tolak Permohonan Tukar Tim
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menolak permohonan tukar jadwal ini?
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="isProcessing"
                    @click="isRejectModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    :loading="isProcessing"
                    :disabled="isProcessing"
                    @click="executeReject"
                >
                    Ya, Tolak
                </Button>
            </template>
        </Modal>
    </div>
</template>
