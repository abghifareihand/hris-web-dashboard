<script setup>
import { ref, reactive } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import DataTable from "@/Components/Table/DataTable.vue";
import TablePagination from "@/Components/Table/TablePagination.vue";
import TableEmpty from "@/Components/Table/TableEmpty.vue";
import Modal from "@/Components/UI/Modal.vue";
import { useDebounce } from "@/Composables/useDebounce";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    pendingLeaves: {
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
    leaveCategories: {
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
    leave_category_id: props.filters.leave_category_id || "",
});

const selectedLeave = ref(null);
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isProcessing = ref(false);

const rejectForm = useForm({
    reject_reason: "",
});

const applyFilters = () => {
    router.get(
        route("owner.management.leaves.pending.index"),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            leave_category_id: filters.leave_category_id || undefined,
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
    filters.leave_category_id = "";
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.leave_category_id,
    );
};

const openApprove = (leave) => {
    selectedLeave.value = leave;
    isApproveModalOpen.value = true;
};

const openReject = (leave) => {
    selectedLeave.value = leave;
    rejectForm.reset();
    isRejectModalOpen.value = true;
};

const executeApprove = () => {
    if (!selectedLeave.value) return;
    isProcessing.value = true;
    router.post(
        route(
            "owner.management.leaves.pending.approve",
            selectedLeave.value.id,
        ),
        {},
        {
            onFinish: () => {
                isProcessing.value = false;
                isApproveModalOpen.value = false;
                selectedLeave.value = null;
            },
        },
    );
};

const executeReject = () => {
    if (!selectedLeave.value) return;
    if (!rejectForm.reject_reason || !rejectForm.reject_reason.trim()) {
        rejectForm.setError("reject_reason", "Alasan penolakan wajib diisi.");
        return;
    }
    rejectForm.post(
        route("owner.management.leaves.pending.reject", selectedLeave.value.id),
        {
            onSuccess: () => {
                isRejectModalOpen.value = false;
                selectedLeave.value = null;
                rejectForm.reset();
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
</script>

<template>
    <Head title="Persetujuan Cuti Karyawan" />

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
                        Persetujuan Pengajuan Cuti
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Verifikasi dan setujui permohonan cuti tahunan, sakit, atau
                    izin khusus dari karyawan.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div
                class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]"
            >
                <h2 class="text-base font-bold text-slate-900">
                    Filter Pengajuan Cuti
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
                <!-- Row 1: Dropdown Filters (Cabang, Divisi, Jenis Cuti) -->
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

                    <!-- Jenis Cuti -->
                    <div>
                        <Select
                            label="Jenis Cuti"
                            v-model="filters.leave_category_id"
                            :options="
                                leaveCategories.map((c) => ({
                                    value: c.id,
                                    label: c.name,
                                }))
                            "
                            all-label="Semua Jenis Cuti"
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
                        placeholder="Cari nama karyawan atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable
            :headers="[
                'Karyawan',
                'Jenis Cuti',
                'Periode Cuti',
                'Durasi',
                'Alasan Cuti',
                '',
            ]"
        >
            <template
                v-if="pendingLeaves.data && pendingLeaves.data.length > 0"
            >
                <tr
                    v-for="item in pendingLeaves.data"
                    :key="item.id"
                    class="hover:bg-slate-50/70 transition"
                >
                    <!-- Karyawan -->
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">
                            {{ item.employee?.name || "-" }}
                        </div>
                        <div class="text-xs text-slate-400">
                            {{ item.employee?.branch?.name || "-" }} &bull;
                            {{ item.employee?.division?.name || "-" }}
                        </div>
                    </td>

                    <!-- Jenis Cuti -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100"
                        >
                            {{ item.leave_category?.name || "Cuti" }}
                        </span>
                    </td>

                    <!-- Periode Cuti -->
                    <td
                        class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap"
                    >
                        {{ formatDate(item.start_date) }}
                        <template
                            v-if="
                                item.end_date &&
                                item.end_date !== item.start_date
                            "
                        >
                            &mdash; {{ formatDate(item.end_date) }}
                        </template>
                    </td>

                    <!-- Durasi -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700"
                        >
                            {{ item.days_count || 1 }} Hari
                        </span>
                    </td>

                    <!-- Alasan Cuti -->
                    <td
                        class="px-5 py-3.5 text-xs text-slate-600 max-w-xs truncate"
                    >
                        {{ item.reason || "-" }}
                    </td>

                    <!-- Aksi -->
                    <td
                        class="px-5 py-3.5 whitespace-nowrap text-right"
                    >
                        <div class="inline-flex items-center gap-2">
                            <button
                                type="button"
                                @click="openReject(item)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50/90 hover:bg-rose-100/90 border border-rose-200/80 rounded-lg transition cursor-pointer"
                                title="Tolak Pengajuan"
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
                                title="Setujui Cuti"
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
                    message="Tidak ada permohonan cuti yang sedang menunggu persetujuan."
                />
            </template>
        </DataTable>

        <TablePagination :pagination="pendingLeaves" />

        <!-- Modal Setujui -->
        <Modal
            :show="isApproveModalOpen"
            maxWidth="sm"
            @close="isApproveModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Setujui Permohonan Cuti
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menyetujui permohonan cuti dari
                    <span class="font-semibold text-slate-900">{{
                        selectedLeave?.employee?.name
                    }}</span>
                    sebanyak
                    <span class="font-semibold text-slate-900">{{ selectedLeave?.days_count }} hari</span>?
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
                    @click="executeApprove"
                    :loading="isProcessing"
                    :disabled="isProcessing"
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
                    Tolak Permohonan Cuti
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Berikan alasan penolakan permohonan cuti untuk karyawan
                    <span class="font-semibold text-slate-900">{{selectedLeave?.employee?.name}}</span>
                </p>

                <div class="mt-4">
                    <Textarea
                        label="Alasan Penolakan"
                        v-model="rejectForm.reject_reason"
                        placeholder="Masukkan alasan penolakan..."
                        :rows="3"
                        :error="rejectForm.errors.reject_reason"
                        required
                    />
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="rejectForm.processing"
                    @click="isRejectModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="executeReject"
                    :loading="rejectForm.processing"
                    :disabled="rejectForm.processing"
                >
                    Ya, Tolak
                </Button>
            </template>
        </Modal>
    </div>
</template>
