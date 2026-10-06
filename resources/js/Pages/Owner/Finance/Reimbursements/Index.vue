<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePickerRange from '@/Components/UI/DatePickerRange.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    reimbursements: {
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
    stats: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    status: props.filters?.status || '',
    payout_method: props.filters?.payout_method || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const selectedDeleteId = ref(null);
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const selectedDetailItem = ref(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (item) => {
    selectedDetailItem.value = item;
    isDetailModalOpen.value = true;
};

const applyFilters = () => {
    router.get(
        route('owner.finance.reimbursements.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            status: filters.status || undefined,
            payout_method: filters.payout_method || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
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

const resetFilters = () => {
    filters.search = '';
    filters.branch_id = '';
    filters.division_id = '';
    filters.status = '';
    filters.payout_method = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.status ||
        filters.payout_method ||
        filters.start_date ||
        filters.end_date
    );
};

const openDeleteModal = (id) => {
    selectedDeleteId.value = id;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!selectedDeleteId.value) return;
    isDeleting.value = true;
    router.delete(route('owner.finance.reimbursements.destroy', selectedDeleteId.value), {
        onFinish: () => {
            isDeleting.value = false;
            isDeleteModalOpen.value = false;
            selectedDeleteId.value = null;
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const getAttachmentUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('/storage/')) return path;
    if (path.startsWith('storage/')) return '/' + path;
    return '/storage/' + path;
};
</script>

<template>
    <Head title="Manajemen Data Klaim Biaya - Frans HRIS" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Data Klaim Biaya</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola dan pantau seluruh riwayat penggantian biaya operasional karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.finance.reimbursements.create')">
                    <Button variant="primary" size="md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Klaim
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Stats Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Menunggu Approval -->
            <Link
                :href="route('owner.finance.reimbursements.pending.index')"
                class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:border-amber-300 hover:shadow-sm transition group"
            >
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Menunggu Approval</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5 flex items-baseline gap-1">
                        <span>{{ stats?.pending_count || 0 }}</span>
                        <span class="text-xs font-normal text-slate-500">klaim</span>
                    </div>
                </div>
            </Link>

            <!-- Disetujui (Payroll) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Disetujui (Payroll)</div>
                    <div class="text-xl font-bold text-blue-700 mt-0.5 truncate">{{ formatCurrency(stats?.total_approved) }}</div>
                </div>
            </div>

            <!-- Total Terbayar (Lunas) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Terbayar (Lunas)</div>
                    <div class="text-xl font-bold text-emerald-700 mt-0.5 truncate">{{ formatCurrency(stats?.total_paid) }}</div>
                </div>
            </div>

            <!-- Total Ditolak -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Ditolak</div>
                    <div class="text-2xl font-bold text-rose-600 mt-0.5 flex items-baseline gap-1">
                        <span>{{ stats?.total_rejected || 0 }}</span>
                        <span class="text-xs font-normal text-slate-500">klaim</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Data Klaim Biaya
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
                        class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs cursor-pointer"
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
                <!-- Row 1: Metode Pencairan & Periode Tanggal -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Metode Pencairan Filter -->
                    <div>
                        <Select
                            label="Metode Pencairan"
                            v-model="filters.payout_method"
                            :options="[
                                { value: '', label: 'Semua Metode' },
                                { value: 'payroll', label: 'Payroll (Gaji Bulanan)' },
                                { value: 'direct', label: 'Transfer Langsung' },
                            ]"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Periode Tanggal Filter -->
                    <div>
                        <DatePickerRange
                            label="Periode Tanggal"
                            v-model:startDate="filters.start_date"
                            v-model:endDate="filters.end_date"
                            placeholder="Pilih Rentang Tanggal"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Status, Cabang, Divisi -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.status"
                            :options="[
                                { value: '', label: 'Semua Status' },
                                { value: 'approved', label: 'Disetujui (Approved)', dotClass: 'bg-emerald-500' },
                                { value: 'paid', label: 'Terbayar (Paid)', dotClass: 'bg-blue-500' },
                                { value: 'rejected', label: 'Ditolak (Rejected)', dotClass: 'bg-rose-500' },
                            ]"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Cabang Filter -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map(b => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi Filter -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Row 3: Standalone Search field below divider -->
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

        <!-- Data Table -->
        <DataTable :headers="['Karyawan', 'Tanggal Klaim', 'Nominal', 'Metode', 'Status', 'Lampiran', '']">
            <template v-if="reimbursements.data && reimbursements.data.length > 0">
                <tr v-for="item in reimbursements.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                    <!-- Karyawan (Di depan) -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ item.employee?.name?.charAt(0).toUpperCase() || 'E' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate">
                                    {{ item.employee?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-400 truncate flex items-center gap-1.5 mt-0.5 font-mono">
                                    <span>{{ item.employee?.nip || '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ item.employee?.branch?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Tanggal Klaim (Formatted: 22 Sep 2026) -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.date) }}
                    </td>

                    <!-- Nominal -->
                    <td class="px-5 py-3.5 whitespace-nowrap font-bold text-slate-900 text-sm">
                        {{ formatCurrency(item.amount) }}
                    </td>

                    <!-- Metode Pencairan -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span v-if="item.payout_method === 'payroll'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            Payroll
                        </span>
                        <span v-else-if="item.payout_method === 'direct'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                            Transfer
                        </span>
                        <span v-else class="text-slate-400 text-xs">-</span>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge v-if="item.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                        <Badge v-else-if="item.status === 'approved'" variant="info">Disetujui</Badge>
                        <Badge v-else-if="item.status === 'rejected'" variant="danger">Ditolak</Badge>
                        <Badge v-else variant="warning">{{ item.status }}</Badge>
                    </td>

                    <!-- Lampiran -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <a
                            v-if="item.attachment"
                            :href="getAttachmentUrl(item.attachment)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-medium hover:underline bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-100 transition"
                            title="Lihat Berkas Lampiran"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>Lihat File</span>
                        </a>
                        <span v-else class="text-xs text-slate-400 italic">Tidak ada</span>
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <button
                                type="button"
                                @click="openDetailModal(item)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer"
                                title="Lihat Detail Klaim"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>

                            <button
                                v-if="item.status !== 'paid' && !item.payroll_item_id"
                                type="button"
                                @click="openDeleteModal(item.id)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                                title="Hapus Klaim"
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
                <TableEmpty :colspan="7" message="Belum ada data riwayat klaim biaya yang sesuai dengan filter." />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="reimbursements" />

        <!-- Detail Modal -->
        <Modal
            :show="isDetailModalOpen"
            maxWidth="md"
            @close="isDetailModalOpen = false"
        >
            <div class="space-y-4" v-if="selectedDetailItem">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">
                            Detail Klaim Biaya
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Informasi lengkap pengajuan reimbursement karyawan.
                        </p>
                    </div>
                    <Badge v-if="selectedDetailItem.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                    <Badge v-else-if="selectedDetailItem.status === 'approved'" variant="info">Disetujui</Badge>
                    <Badge v-else-if="selectedDetailItem.status === 'rejected'" variant="danger">Ditolak</Badge>
                    <Badge v-else variant="warning">{{ selectedDetailItem.status }}</Badge>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Karyawan:</span>
                        <span class="font-semibold text-slate-900">{{ selectedDetailItem.employee?.name || '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Tanggal Klaim:</span>
                        <span class="font-medium text-slate-700">{{ formatDate(selectedDetailItem.date) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Nominal:</span>
                        <span class="font-bold text-emerald-600 text-base">{{ formatCurrency(selectedDetailItem.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Metode Pencairan:</span>
                        <span v-if="selectedDetailItem.payout_method === 'payroll'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            Payroll (Gaji Bulanan)
                        </span>
                        <span v-else-if="selectedDetailItem.payout_method === 'direct'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                            Transfer Langsung
                        </span>
                        <span v-else class="text-slate-500 text-xs">-</span>
                    </div>
                </div>

                <!-- Deskripsi / Alasan Lengkap -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Deskripsi / Alasan Klaim</label>
                    <div class="p-3 bg-white rounded-xl border border-slate-200 text-sm text-slate-800 leading-relaxed max-h-48 overflow-y-auto whitespace-pre-line shadow-2xs">
                        {{ selectedDetailItem.reason || '-' }}
                    </div>
                </div>

                <!-- Alasan Penolakan (Jika Ada) -->
                <div v-if="selectedDetailItem.reject_reason" class="space-y-1.5">
                    <label class="block text-xs font-bold text-rose-600 uppercase tracking-wider">Alasan Penolakan</label>
                    <div class="p-3 bg-rose-50/60 rounded-xl border border-rose-200 text-sm text-rose-700 leading-relaxed whitespace-pre-line">
                        {{ selectedDetailItem.reject_reason }}
                    </div>
                </div>

                <!-- Berkas Lampiran -->
                <div v-if="selectedDetailItem.attachment" class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span class="text-xs font-semibold text-slate-800">Berkas Bukti / Nota</span>
                    </div>
                    <a
                        :href="getAttachmentUrl(selectedDetailItem.attachment)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-white border border-emerald-200 rounded-lg hover:bg-emerald-50 transition"
                    >
                        <span>Lihat Berkas</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    @click="isDetailModalOpen = false"
                >
                    Tutup
                </Button>
            </template>
        </Modal>

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Data Klaim Biaya
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus data pengajuan klaim biaya ini? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="isDeleting"
                    @click="isDeleteModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="executeDelete"
                    :loading="isDeleting"
                    :disabled="isDeleting"
                >
                    Ya, Hapus
                </Button>
            </template>
        </Modal>
    </div>
</template>
