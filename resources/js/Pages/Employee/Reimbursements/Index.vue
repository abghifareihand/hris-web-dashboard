<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    reimbursements: Object,
    totalApproved: Number,
    totalPending: Number,
    pendingCount: Number,
    filters: Object,
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.start_date = newFilters?.start_date || '';
        filters.end_date = newFilters?.end_date || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.reimbursements.index'),
        {
            status: filters.status || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
            search: filters.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
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
    filters.status = '';
    filters.start_date = '';
    filters.end_date = '';
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.status || filters.start_date || filters.end_date || filters.search);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Menunggu Verifikasi' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'paid', label: 'Sudah Dicairkan' },
    { value: 'rejected', label: 'Ditolak' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

// Form Pengajuan
const isCreateModalOpen = ref(false);
const fileName = ref('');

const form = useForm({
    date: '',
    amount: '',
    reason: '',
    attachment: null,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().split('T')[0];
    fileName.value = '';
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.attachment = file;
        fileName.value = file.name;
    } else {
        form.attachment = null;
        fileName.value = '';
    }
};

const submitReimbursement = () => {
    form.post(route('employee.reimbursements.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Detail Modal
const isDetailModalOpen = ref(false);
const selectedReimbursement = ref(null);

const openDetailModal = (item) => {
    selectedReimbursement.value = item;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedReimbursement.value = null;
};

// Cancel Reimbursement
const cancelForm = useForm({});
const isCancelling = ref(false);

const cancelReimbursement = (id) => {
    if (!confirm('Apakah Anda yakin ingin membatalkan pengajuan klaim biaya ini?')) return;
    isCancelling.value = true;
    cancelForm.post(route('employee.reimbursements.cancel', id), {
        preserveScroll: true,
        onFinish: () => {
            isCancelling.value = false;
            if (isDetailModalOpen.value) {
                closeDetailModal();
            }
        },
    });
};

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const isImageFile = (path) => {
    if (!path) return false;
    const ext = path.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
};

const getBadgeVariant = (status) => {
    switch (status) {
        case 'paid':
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'cancelled':
            return 'neutral';
        case 'pending':
        default:
            return 'warning';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'paid':
            return 'Dicairkan';
        case 'approved':
            return 'Disetujui';
        case 'rejected':
            return 'Ditolak';
        case 'cancelled':
            return 'Dibatalkan';
        case 'pending':
        default:
            return 'Menunggu Verifikasi';
    }
};

const tableHeaders = [
    { label: 'Tanggal Kuitansi', class: 'w-36' },
    { label: 'Keterangan Pengeluaran', class: 'w-72' },
    { label: 'Nominal Klaim', class: 'w-40 font-bold' },
    { label: 'Bukti Nota', class: 'w-36 text-center' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-28 text-right' },
];
</script>

<template>
    <Head title="Klaim Biaya & Reimbursement" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Klaim Biaya (Reimbursement)</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Ajukan penggantian biaya operasional, medis, atau pengeluaran dinas kantor.
                    </p>
                </div>
                <div>
                    <Button
                        variant="primary"
                        @click="openCreateModal"
                        class="shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajukan Klaim Baru
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Klaim Disetujui / Dicairkan</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-0.5">
                            {{ formatRupiah(totalApproved) }}
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Klaim Sedang Diproses ({{ pendingCount }})</p>
                        <p class="text-2xl font-bold text-amber-600 mt-0.5">
                            {{ formatRupiah(totalPending) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Riwayat Reimbursement
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
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </Button>
                    </transition>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Pengajuan"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <DatePicker
                                label="Tanggal Mulai"
                                v-model="filters.start_date"
                                placeholder="Pilih Tanggal Mulai"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <DatePicker
                                label="Tanggal Selesai"
                                v-model="filters.end_date"
                                placeholder="Pilih Tanggal Selesai"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="pt-3 border-t border-slate-100">
                        <Input
                            label="Cari Keterangan Biaya"
                            :modelValue="filters.search"
                            @update:modelValue="onSearchInput"
                            placeholder="Ketik keperluan pengeluaran atau rincian nota biaya..."
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

            <!-- Standar DataTable Component -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="reimbursements?.data && reimbursements.data.length > 0">
                        <tr
                            v-for="item in reimbursements.data"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-semibold text-slate-800">
                                {{ formatDate(item.date) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p
                                    class="text-sm font-bold text-slate-900 hover:text-emerald-600 transition-colors cursor-pointer line-clamp-1"
                                    @click="openDetailModal(item)"
                                >
                                    {{ item.reason }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">Diajukan: {{ formatDate(item.created_at) }}</p>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-slate-900">
                                {{ formatRupiah(item.amount) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <a
                                    v-if="item.attachment"
                                    :href="'/storage/' + item.attachment"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Lihat Berkas
                                </a>
                                <span v-else class="text-xs text-slate-400 italic">Tanpa Bukti</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <Badge :variant="getBadgeVariant(item.status)" size="sm">
                                    {{ getStatusLabel(item.status) }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openDetailModal(item)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        title="Lihat Detail Klaim"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <Button
                                        v-if="item.status === 'pending'"
                                        variant="danger"
                                        size="xs"
                                        :disabled="isCancelling"
                                        @click="cancelReimbursement(item.id)"
                                    >
                                        Batal
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="6"
                        title="Belum Ada Klaim Reimbursement"
                        message="Anda belum pernah mengajukan reimbursement atau tidak ada data yang cocok dengan kriteria filter yang dipilih."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openCreateModal">
                                Ajukan Klaim Baru Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="reimbursements" />
        </div>

        <!-- Modal Ajukan Klaim Baru -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Klaim Biaya</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Unggah bukti kuitansi dan rincian klaim pengeluaran</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitReimbursement" class="space-y-4">
                <div>
                    <DatePicker
                        label="Tanggal Transaksi / Nota"
                        v-model="form.date"
                        placeholder="Pilih Tanggal Transaksi"
                        :error="form.errors.date"
                        required
                    />
                </div>

                <div>
                    <Input
                        label="Nominal Biaya (Rp)"
                        type="number"
                        min="1000"
                        step="100"
                        v-model="form.amount"
                        placeholder="Contoh: 150000"
                        :error="form.errors.amount"
                        required
                    />
                    <p v-if="form.amount" class="text-xs text-emerald-600 mt-1 font-semibold">
                        {{ formatRupiah(form.amount) }}
                    </p>
                </div>

                <div>
                    <Textarea
                        label="Keterangan Pengeluaran"
                        v-model="form.reason"
                        rows="3"
                        placeholder="Contoh: Pembelian tinta printer kantor, biaya konsumsi meeting klien..."
                        :error="form.errors.reason"
                        required
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Unggah Bukti Struk / Kuitansi
                    </label>
                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-xl p-4 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/20 transition-all text-center">
                        <svg class="w-8 h-8 text-slate-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span class="text-xs text-slate-600 font-medium">
                            {{ fileName || 'Klik untuk pilih foto struk / file PDF' }}
                        </span>
                        <span class="text-[10px] text-slate-400 mt-0.5">Format: JPG, PNG, atau PDF (Maks. 5MB)</span>
                        <input
                            type="file"
                            @change="handleFileChange"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="hidden"
                        />
                    </label>
                    <p v-if="form.errors.attachment" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.attachment }}
                    </p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeCreateModal"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Kirim Klaim
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Modal Detail Reimbursement -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian Klaim Biaya</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Detail verifikasi pengeluaran operasional</p>
                    </div>
                </div>
            </template>

            <div v-if="selectedReimbursement" class="space-y-4 text-sm">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Nominal Klaim:</span>
                        <span class="text-lg font-bold text-emerald-600">{{ formatRupiah(selectedReimbursement.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Tanggal Nota:</span>
                        <span class="font-medium text-slate-800">{{ formatDate(selectedReimbursement.date) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Status Permohonan:</span>
                        <Badge :variant="getBadgeVariant(selectedReimbursement.status)">
                            {{ getStatusLabel(selectedReimbursement.status) }}
                        </Badge>
                    </div>
                    <div v-if="selectedReimbursement.payout_method" class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Metode Pencairan:</span>
                        <span class="text-xs font-semibold text-slate-700 capitalize">
                            {{ selectedReimbursement.payout_method === 'payroll' ? 'Masuk ke Slip Gaji' : 'Transfer Langsung' }}
                        </span>
                    </div>
                    <div v-if="selectedReimbursement.approved_by" class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Diverifikasi Oleh:</span>
                        <span class="text-xs font-medium text-slate-700">{{ selectedReimbursement.approved_by?.name || 'Tim Finance' }}</span>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Keterangan Biaya:</h4>
                    <p class="text-xs text-slate-600 bg-slate-50/50 p-3 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line">
                        {{ selectedReimbursement.reason }}
                    </p>
                </div>

                <!-- Lampiran Bukti Kuitansi -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Bukti Kuitansi / Struk:</h4>
                    <div v-if="selectedReimbursement.attachment" class="mt-1">
                        <div v-if="isImageFile(selectedReimbursement.attachment)" class="rounded-xl overflow-hidden border border-slate-200">
                            <img
                                :src="'/storage/' + selectedReimbursement.attachment"
                                alt="Bukti Kuitansi"
                                class="w-full max-h-52 object-contain bg-slate-100"
                            />
                        </div>
                        <div class="mt-2 flex justify-start">
                            <a
                                :href="'/storage/' + selectedReimbursement.attachment"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Buka Dokumen Asli
                            </a>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-400 italic">Tidak ada berkas bukti kuitansi yang dilampirkan.</p>
                </div>

                <!-- Alasan Penolakan -->
                <div v-if="selectedReimbursement.status === 'rejected'" class="p-3 bg-rose-50 border border-rose-200 rounded-xl">
                    <p class="text-xs font-bold text-rose-800 mb-0.5">Alasan Penolakan:</p>
                    <p class="text-xs text-rose-700 leading-relaxed">
                        {{ selectedReimbursement.reject_reason || 'Tidak ada alasan khusus yang dicantumkan.' }}
                    </p>
                </div>

                <div class="pt-3 flex items-center justify-between border-t border-slate-100">
                    <Button
                        v-if="selectedReimbursement.status === 'pending'"
                        variant="danger"
                        size="sm"
                        :disabled="isCancelling"
                        @click="cancelReimbursement(selectedReimbursement.id)"
                    >
                        Batalkan Pengajuan
                    </Button>
                    <div v-else></div>

                    <Button
                        variant="secondary"
                        size="sm"
                        @click="closeDetailModal"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
