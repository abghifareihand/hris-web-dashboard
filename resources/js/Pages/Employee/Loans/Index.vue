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
    loans: Object,
    totalActiveLoan: Number,
    totalRemainingBalance: Number,
    pendingCount: Number,
    loanPolicy: Object,
    filters: Object,
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.loans.index'),
        {
            status: filters.status || undefined,
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
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.status || filters.search);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'paid', label: 'Lunas' },
    { value: 'rejected', label: 'Ditolak' },
];

// Tenor Options for Form
const tenorOptions = computed(() => {
    const list = props.loanPolicy?.tenorOptions || [1, 2, 3, 6, 12];
    const unit = props.loanPolicy?.cycleUnit || 'Bulan';
    return list.map((t) => ({
        value: t,
        label: `${t} ${unit}`,
    }));
});

// Create Modal & Form
const isCreateModalOpen = ref(false);

const form = useForm({
    date: '',
    amount: '',
    tenor: 1,
    description: '',
});

// Installment Simulation Calculation
const installmentSimulation = computed(() => {
    const amt = Number(form.amount) || 0;
    const t = Number(form.tenor) || 1;
    if (amt <= 0 || t <= 0) return null;

    const installmentPerCycle = Math.round(amt / t);
    const basicSalary = props.loanPolicy?.basicSalary || 0;
    const maxInstallment = props.loanPolicy?.maxMonthlyInstallment || 0;
    const ratio = basicSalary > 0 ? ((installmentPerCycle / basicSalary) * 100).toFixed(1) : null;
    const isExceeding = maxInstallment > 0 && installmentPerCycle > maxInstallment;
    const unit = props.loanPolicy?.cycleUnit || 'Bulan';
    const unitLower = unit.toLowerCase();

    return {
        installmentPerCycle,
        ratio,
        isExceeding,
        maxInstallment,
        unit,
        unitLower,
    };
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().split('T')[0];
    form.tenor = (props.loanPolicy?.tenorOptions && props.loanPolicy.tenorOptions[0]) || 1;
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitLoan = () => {
    form.post(route('employee.loans.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Detail / Installment Modal
const isDetailModalOpen = ref(false);
const selectedLoan = ref(null);

const openDetailModal = (loan) => {
    selectedLoan.value = loan;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedLoan.value = null;
};

// Cancel Loan
const cancelForm = useForm({});
const isCancelling = ref(false);

const cancelLoan = (id) => {
    if (!confirm('Apakah Anda yakin ingin membatalkan permohonan pinjaman / kasbon ini?')) return;
    isCancelling.value = true;
    cancelForm.post(route('employee.loans.cancel', id), {
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

const getBadgeVariant = (status) => {
    switch (status) {
        case 'paid':
            return 'info';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'pending':
        default:
            return 'warning';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'paid':
            return 'Lunas';
        case 'approved':
            return 'Disetujui';
        case 'rejected':
            return 'Ditolak';
        case 'pending':
        default:
            return 'Menunggu Persetujuan';
    }
};

const tableHeaders = [
    { label: 'Tanggal', class: 'w-36' },
    { label: 'Nominal Pinjaman', class: 'w-44 font-bold' },
    { label: 'Tenor Cicilan', class: 'w-32 text-center' },
    { label: 'Potongan / Angsuran', class: 'w-40' },
    { label: 'Keperluan Kasbon', class: 'w-60' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-28 text-right' },
];
</script>

<template>
    <Head title="Pinjaman Karyawan & Kasbon" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pinjaman Karyawan (Kasbon)</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Fasilitas pembiayaan internal perusahaan dengan pemotongan angsuran gaji bulanan secara otomatis.
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
                        Ajukan Kasbon Baru
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Kasbon Disetujui</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-0.5">
                            {{ formatRupiah(totalActiveLoan) }}
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sisa Saldo Belum Terpotong</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ formatRupiah(totalRemainingBalance) }}
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
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Menunggu Persetujuan</p>
                        <p class="text-2xl font-bold text-amber-600 mt-0.5">
                            {{ pendingCount }} <span class="text-xs font-normal text-slate-400">Pengajuan</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Riwayat Kasbon
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Pengajuan"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Search Input -->
                        <div>
                            <Input
                                label="Cari Keperluan Kasbon"
                                :modelValue="filters.search"
                                @update:modelValue="onSearchInput"
                                placeholder="Ketik alasan atau keperluan pinjaman..."
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
            </div>

            <!-- Standar DataTable Component -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="loans?.data && loans.data.length > 0">
                        <tr
                            v-for="item in loans.data"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-semibold text-slate-800">
                                {{ formatDate(item.date) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-slate-900">
                                {{ formatRupiah(item.amount) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ item.tenor_label || `${item.tenor} ${item.tenor_unit || 'Bulan'}` }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-semibold text-emerald-700">
                                {{ formatRupiah(item.amount / item.tenor) }} <span class="text-[11px] text-slate-400 font-normal">/ {{ (item.tenor_unit || 'bln').toLowerCase() }}</span>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <p
                                    class="text-sm font-medium text-slate-900 hover:text-emerald-600 transition-colors cursor-pointer line-clamp-1"
                                    @click="openDetailModal(item)"
                                >
                                    {{ item.description }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">Diajukan: {{ formatDate(item.created_at) }}</p>
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
                                        title="Lihat Detail & Jadwal Angsuran"
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
                                        @click="cancelLoan(item.id)"
                                    >
                                        Batal
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="7"
                        title="Belum Ada Permohonan Pinjaman"
                        message="Anda belum pernah mengajukan pinjaman kasbon atau tidak ada data yang cocok dengan kriteria filter yang dipilih."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openCreateModal">
                                Ajukan Kasbon Baru Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="loans" />
        </div>

        <!-- Modal Ajukan Pinjaman / Kasbon -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Pengajuan Kasbon</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ajukan bantuan dana kasbon dengan skema angsuran</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitLoan" class="space-y-4">
                <div>
                    <DatePicker
                        label="Tanggal Pengajuan"
                        v-model="form.date"
                        placeholder="Pilih Tanggal Pengajuan"
                        :error="form.errors.date"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <Input
                            label="Nominal Pinjaman (Rp)"
                            type="number"
                            min="50000"
                            step="10000"
                            v-model="form.amount"
                            placeholder="Contoh: 1000000"
                            :error="form.errors.amount"
                            required
                        />
                    </div>
                    <div>
                        <Select
                            label="Tenor Cicilan"
                            v-model="form.tenor"
                            :options="tenorOptions"
                            :error="form.errors.tenor"
                            required
                        />
                    </div>
                </div>

                <!-- Simulation Box -->
                <div v-if="installmentSimulation" class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl space-y-1.5 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Estimasi Cicilan per {{ installmentSimulation.unit }}:</span>
                        <span class="font-bold text-emerald-700 text-sm">
                            {{ formatRupiah(installmentSimulation.installmentPerCycle) }} / {{ installmentSimulation.unitLower }}
                        </span>
                    </div>
                    <div v-if="installmentSimulation.ratio" class="flex justify-between items-center">
                        <span class="text-slate-500">Rasio terhadap Gaji {{ props.loanPolicy?.payrollCycle === 'weekly' ? 'Mingguan' : (props.loanPolicy?.payrollCycle === 'daily' ? 'Harian' : 'Bulanan') }}:</span>
                        <span :class="installmentSimulation.isExceeding ? 'text-rose-600 font-bold' : 'text-slate-700 font-semibold'">
                            {{ installmentSimulation.ratio }}% (Batas Aman: 35%)
                        </span>
                    </div>

                    <!-- Warning if exceeding 35% -->
                    <div v-if="installmentSimulation.isExceeding" class="mt-2 pt-2 border-t border-slate-200 text-[11px] text-rose-600 leading-relaxed">
                        ⚠️ Cicilan melebihi batas kebijakan 35% gaji {{ (props.loanPolicy?.payrollCycle === 'weekly' ? 'mingguan' : (props.loanPolicy?.payrollCycle === 'daily' ? 'harian' : 'bulanan')) }} (Maks. {{ formatRupiah(installmentSimulation.maxInstallment) }}/{{ installmentSimulation.unitLower }}). Pertimbangkan memperpanjang tenor atau mengurangi nominal pinjaman.
                    </div>
                </div>

                <div>
                    <Textarea
                        label="Alasan / Keperluan Pinjaman"
                        v-model="form.description"
                        rows="3"
                        placeholder="Contoh: Biaya berobat keluarga, kebutuhan mendesak..."
                        :error="form.errors.description"
                        required
                    />
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
                        Ajukan Kasbon
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Modal Detail & Jadwal Angsuran -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="lg">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Jadwal Angsuran Pinjaman</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedLoan">
                            Total: {{ formatRupiah(selectedLoan.amount) }} ({{ selectedLoan.tenor_label || `${selectedLoan.tenor} Bulan` }})
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedLoan" class="space-y-4 text-sm">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Nominal Pinjaman:</span>
                        <span class="text-lg font-bold text-emerald-600">{{ formatRupiah(selectedLoan.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Tenor Cicilan:</span>
                        <span class="font-semibold text-slate-800">{{ selectedLoan.tenor_label || `${selectedLoan.tenor} Bulan` }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Potongan per {{ selectedLoan.tenor_unit || 'Periode' }}:</span>
                        <span class="font-semibold text-slate-800">{{ formatRupiah(selectedLoan.amount / selectedLoan.tenor) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Status Permohonan:</span>
                        <Badge :variant="getBadgeVariant(selectedLoan.status)">
                            {{ getStatusLabel(selectedLoan.status) }}
                        </Badge>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Keperluan Pinjaman:</h4>
                    <p class="text-xs text-slate-600 bg-slate-50/50 p-3 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line">
                        {{ selectedLoan.description }}
                    </p>
                </div>

                <!-- Installments Table -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Rincian Angsuran:</h4>
                    <div v-if="selectedLoan.installments && selectedLoan.installments.length > 0" class="border border-slate-200/80 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 uppercase font-semibold text-slate-500 border-b border-slate-200/80">
                                <tr>
                                    <th class="px-4 py-2.5">Cicilan Ke</th>
                                    <th class="px-4 py-2.5">Jatuh Tempo</th>
                                    <th class="px-4 py-2.5">Nominal Potongan</th>
                                    <th class="px-4 py-2.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(ins, idx) in selectedLoan.installments" :key="ins.id">
                                    <td class="px-4 py-3 font-semibold text-slate-900">#{{ idx + 1 }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ formatDate(ins.due_date) }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-900">{{ formatRupiah(ins.amount) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <Badge :variant="ins.status === 'paid' ? 'success' : 'warning'">
                                            {{ ins.status === 'paid' ? 'Sudah Terpotong' : 'Belum Terpotong' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
                        <p class="text-xs text-slate-500">
                            Jadwal angsuran akan dibuat secara otomatis setelah permohonan disetujui oleh Owner / Manajemen.
                        </p>
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-between border-t border-slate-100">
                    <Button
                        v-if="selectedLoan.status === 'pending'"
                        variant="danger"
                        size="sm"
                        :disabled="isCancelling"
                        @click="cancelLoan(selectedLoan.id)"
                    >
                        Batalkan Pinjaman
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
