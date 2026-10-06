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
    loans: {
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
    status: props.filters?.status || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const statusOptions = [
    { value: 'approved', label: 'Sedang Berjalan' },
    { value: 'paid', label: 'Lunas (Paid)' },
];

const selectedDeleteId = ref(null);
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const applyFilters = () => {
    router.get(
        route('owner.finance.loans.index'),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
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
    filters.status = '';
    filters.branch_id = '';
    filters.division_id = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.status ||
        filters.branch_id ||
        filters.division_id ||
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
    router.delete(route('owner.finance.loans.destroy', selectedDeleteId.value), {
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

const getPaidCount = (installments) => {
    if (!installments || !Array.isArray(installments)) return 0;
    return installments.filter((i) => i.status === 'paid').length;
};

const getPaidAmount = (installments) => {
    if (!installments || !Array.isArray(installments)) return 0;
    return installments
        .filter((i) => i.status === 'paid')
        .reduce((sum, i) => sum + Number(i.amount || 0), 0);
};

const getProgressPercent = (installments) => {
    if (!installments || !Array.isArray(installments) || installments.length === 0) return 0;
    const paid = installments.filter((i) => i.status === 'paid').length;
    return Math.round((paid / installments.length) * 100);
};
</script>

<template>
    <Head title="Manajemen Pinjaman & Kasbon - Frans HRIS" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pinjaman & Kasbon</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Pantau saldo pinjaman, jadwal cicilan, dan riwayat kasbon karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.finance.loans.create')">
                    <Button variant="primary" size="md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambahan Pinjaman
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Stats Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Pinjaman Berjalan (Aktif) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pinjaman Berjalan (Aktif)</div>
                    <div class="text-xl font-bold text-amber-700 mt-0.5 truncate">{{ formatCurrency(stats?.total_active_loan) }}</div>
                </div>
            </div>

            <!-- Total Pinjaman Lunas -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pinjaman Lunas</div>
                    <div class="text-xl font-bold text-emerald-700 mt-0.5 truncate">{{ formatCurrency(stats?.total_paid_loan) }}</div>
                </div>
            </div>

            <!-- Permohonan Pending -->
            <Link
                :href="route('owner.finance.loans.pending.index')"
                class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:border-amber-300 hover:shadow-sm transition group cursor-pointer"
            >
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Permohonan Pending</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5 flex items-baseline gap-1">
                        <span>{{ stats?.pending_count || 0 }}</span>
                        <span class="text-xs font-normal text-slate-500">menunggu</span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Pinjaman & Kasbon
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
                <!-- Row 1: Dropdown Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Status -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.status"
                            :options="statusOptions"
                            all-label="Semua Status"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Cabang -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map((b) => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map((d) => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Periode Tanggal Pinjam -->
                    <div>
                        <DatePickerRange
                            label="Periode Tanggal Pinjam"
                            v-model:startDate="filters.start_date"
                            v-model:endDate="filters.end_date"
                            placeholder="Pilih Periode"
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
        <DataTable :headers="['Karyawan', 'Tanggal Pinjam', 'Nominal Pinjaman', 'Tenor & Cicilan', 'Progres Cicilan', 'Status', '']">
            <template v-if="loans.data && loans.data.length > 0">
                <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-slate-50/70 transition">
                    <!-- Karyawan -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ loan.employee?.name?.charAt(0).toUpperCase() || 'E' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate">
                                    {{ loan.employee?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-400 truncate flex items-center gap-1.5 mt-0.5 font-mono">
                                    <span>{{ loan.employee?.nip || '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ loan.employee?.branch?.name || loan.employee?.division?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Tanggal Pinjam -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-slate-700 whitespace-nowrap">
                        {{ formatDate(loan.date) }}
                    </td>

                    <!-- Nominal Pinjaman -->
                    <td class="px-5 py-3.5 whitespace-nowrap font-bold text-emerald-600 text-sm">
                        {{ formatCurrency(loan.amount) }}
                    </td>

                    <!-- Tenor & Cicilan -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="font-semibold text-slate-800 text-xs">
                            {{ loan.tenor }} Bulan
                        </div>
                        <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                            {{ formatCurrency(loan.amount / loan.tenor) }}/bulan
                        </div>
                    </td>

                    <!-- Progres Cicilan -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <template v-if="loan.installments && loan.installments.length > 0">
                            <div
                                class="text-xs"
                                :class="getPaidAmount(loan.installments) > 0 ? 'font-bold text-emerald-600' : 'font-medium text-slate-400'"
                            >
                                {{ formatCurrency(getPaidAmount(loan.installments)) }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ getPaidCount(loan.installments) }} dari {{ loan.installments.length }} cicilan
                            </div>
                        </template>
                        <span v-else class="text-xs text-slate-400 italic">Belum digenerate</span>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge v-if="loan.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                        <Badge v-else-if="loan.status === 'approved'" variant="warning">Berjalan</Badge>
                        <Badge v-else variant="secondary">{{ loan.status }}</Badge>
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <Link
                                :href="route('owner.finance.loans.show', loan.id)"
                                class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                title="Lihat Detail Pinjaman"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </Link>

                            <button
                                v-if="loan.status !== 'paid' && (!loan.installments || getPaidCount(loan.installments) === 0)"
                                type="button"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                title="Hapus Pinjaman"
                                @click="openDeleteModal(loan.id)"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty
                    :colspan="7"
                    message="Belum ada data pinjaman atau kasbon yang tercatat."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="loans" />

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">
                            Hapus Data Pinjaman
                        </h3>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data pinjaman ini? Pinjaman yang belum memiliki riwayat pembayaran cicilan akan dihapus secara permanen dari sistem.
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
                    :loading="isDeleting"
                    :disabled="isDeleting"
                    @click="executeDelete"
                >
                    Ya, Hapus Pinjaman
                </Button>
            </template>
        </Modal>
    </div>
</template>
