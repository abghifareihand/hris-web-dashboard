<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
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
    sort: props.filters.sort || "desc",
});

const sortOptions = [
    { value: "desc", label: "Terbaru" },
    { value: "asc", label: "Terlama" },
];

const selectedSwap = ref(null);
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isProcessing = ref(false);

const applyFilters = () => {
    router.get(
        route("owner.management.schedules.swap-personal.index"),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            sort: filters.sort !== "desc" ? filters.sort : undefined,
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
    filters.sort = "desc";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.status ||
        filters.branch_id ||
        filters.division_id ||
        (filters.sort && filters.sort !== "desc"),
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
        route("owner.management.schedules.swap-personal.approve", selectedSwap.value.id),
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
        route("owner.management.schedules.swap-personal.reject", selectedSwap.value.id),
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
    <Head title="Persetujuan Tukar Jadwal Pribadi" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        Tukar Jadwal Pribadi
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar permohonan penukaran hari/shift kerja yang diajukan oleh karyawan untuk dirinya sendiri.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Tukar Jadwal Pribadi
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

                    <!-- Sort Filter -->
                    <div>
                        <Select
                            label="Urutkan"
                            v-model="filters.sort"
                            :options="sortOptions"
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

        <!-- Table -->
        <DataTable
            :headers="[
                'Karyawan',
                'Tanggal Semula',
                'Tanggal Tujuan',
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
                    <!-- Employee -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="item.employee?.user?.avatar"
                                :src="item.employee.user.avatar.startsWith('http') || item.employee.user.avatar.startsWith('/') ? item.employee.user.avatar : `/storage/${item.employee.user.avatar}`"
                                :alt="item.employee?.name"
                                class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                            >
                                {{ item.employee?.name?.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <div
                                    class="font-semibold text-slate-800 truncate"
                                >
                                    {{ item.employee?.name || "-" }}
                                </div>
                                <div
                                    class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5"
                                >
                                    <span>{{ item.employee?.branch?.name || "-" }} &bull; {{ item.employee?.division?.name || "-" }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Original Date -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.original_work_date) }}
                    </td>

                    <!-- Target Date -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-emerald-700 whitespace-nowrap">
                        {{ formatDate(item.target_work_date) }}
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge :variant="statusVariant(item.status)" size="sm">
                            {{ statusLabel(item.status) }}
                        </Badge>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-2 justify-end">
                            <template v-if="item.status === 'pending'">
                                <button
                                    type="button"
                                    @click="openReject(item)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50/90 hover:bg-rose-100/90 border border-rose-200/80 rounded-lg transition cursor-pointer"
                                    title="Tolak Permohonan"
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
                                    @click="openApprove(item)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50/90 hover:bg-emerald-100/90 border border-emerald-200/80 rounded-lg transition cursor-pointer"
                                    title="Setujui Permohonan"
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
                            </template>

                            <Link
                                :href="route('owner.management.schedules.swap-personal.show', item.id)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                                title="Lihat Detail Permohonan"
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
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty
                    title="Belum ada permohonan tukar jadwal"
                    message="Tidak ada permohonan tukar jadwal pribadi yang ditemukan atau sesuaikan filter pencarian Anda."
                    :colspan="5"
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
                    Setujui Tukar Jadwal
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda menyetujui pertukaran jadwal kerja untuk
                    <span class="font-semibold text-slate-800">{{ selectedSwap?.employee?.name }}</span>
                    dari tanggal <span class="font-semibold text-slate-800">{{ formatDate(selectedSwap?.original_work_date) }}</span>
                    ke tanggal <span class="font-semibold text-slate-800">{{ formatDate(selectedSwap?.target_work_date) }}</span>?
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
                    Tolak Permohonan Tukar
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menolak permohonan tukar jadwal dari
                    <span class="font-semibold text-slate-800">{{ selectedSwap?.employee?.name }}</span>?
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
