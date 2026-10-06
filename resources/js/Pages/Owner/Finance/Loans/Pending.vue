<script setup>
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    pendingLoans: {
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
    search: props.filters.search || '',
    branch_id: props.filters.branch_id || '',
    division_id: props.filters.division_id || '',
    date: props.filters.date || '',
});

const selectedItem = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);

const approveForm = useForm({});
const rejectForm = useForm({});

const applyFilters = () => {
    router.get(
        route('owner.finance.loans.pending.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            date: filters.date || undefined,
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
    filters.date = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.date
    );
};

const openApprove = (item) => {
    selectedItem.value = item;
    showApproveModal.value = true;
};

const openReject = (item) => {
    selectedItem.value = item;
    showRejectModal.value = true;
};

const submitApprove = () => {
    if (!selectedItem.value) return;
    approveForm.post(route('owner.finance.loans.pending.approve', selectedItem.value.id), {
        onSuccess: () => {
            showApproveModal.value = false;
            selectedItem.value = null;
        },
    });
};

const submitReject = () => {
    if (!selectedItem.value) return;
    rejectForm.post(route('owner.finance.loans.pending.reject', selectedItem.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedItem.value = null;
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

const getSalaryRatio = (amount, tenor, basicSalary) => {
    if (!basicSalary || basicSalary <= 0 || !tenor || tenor <= 0) return null;
    const monthlyInstallment = amount / tenor;
    return (monthlyInstallment / basicSalary) * 100;
};
</script>

<template>
    <Head title="Persetujuan Pinjaman - Frans HRIS" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Persetujuan Pinjaman</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar pengajuan pinjaman karyawan dan jadwalkan pemotongan cicilan.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Pinjaman
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
                <!-- Row 1: Dropdown Filters (Cabang, Divisi, Tanggal Pengajuan) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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

                    <!-- Tanggal Pengajuan -->
                    <div>
                        <DatePicker
                            label="Tanggal Pengajuan"
                            v-model="filters.date"
                            placeholder="Pilih Tanggal"
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
        <DataTable :headers="['Karyawan', 'Tanggal Pengajuan', 'Nominal Pinjaman', 'Tenor', 'Cicilan / Bln', 'Rasio Gaji Pokok', '']">
            <template v-if="pendingLoans.data && pendingLoans.data.length > 0">
                <tr v-for="item in pendingLoans.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                    <!-- Karyawan -->
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
                                    <span>{{ item.employee?.division?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Tanggal Pengajuan -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.date) }}
                    </td>

                    <!-- Nominal Pinjaman -->
                    <td class="px-5 py-3.5 whitespace-nowrap font-bold text-emerald-600 text-sm">
                        {{ formatCurrency(item.amount) }}
                    </td>

                    <!-- Tenor -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                            {{ item.tenor }} Bulan
                        </span>
                    </td>

                    <!-- Cicilan / Bln -->
                    <td class="px-5 py-3.5 whitespace-nowrap font-semibold text-slate-800 text-xs">
                        {{ formatCurrency(item.amount / item.tenor) }}
                    </td>

                    <!-- Rasio Gaji Pokok -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <template v-if="getSalaryRatio(item.amount, item.tenor, item.employee?.basic_salary) !== null">
                            <span
                                v-if="getSalaryRatio(item.amount, item.tenor, item.employee?.basic_salary) > 35"
                                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200/80"
                                title="Beban cicilan melebihi rekomendasi batas 35% gaji pokok"
                            >
                                <span>{{ getSalaryRatio(item.amount, item.tenor, item.employee?.basic_salary).toFixed(1) }}%</span>
                                <span class="text-[10px] text-rose-500 font-normal">(Tinggi)</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80"
                                title="Beban cicilan aman dalam batas wajar"
                            >
                                <span>{{ getSalaryRatio(item.amount, item.tenor, item.employee?.basic_salary).toFixed(1) }}%</span>
                            </span>
                        </template>
                        <span v-else class="text-xs text-slate-400 italic">-</span>
                    </td>

                    <!-- Aksi -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-2">
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
                                title="Setujui Pengajuan"
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
                    :colspan="7"
                    message="Tidak ada pengajuan pinjaman/kasbon yang sedang menunggu persetujuan."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="pendingLoans" />

        <!-- Modal Setujui -->
        <Modal
            :show="showApproveModal"
            maxWidth="md"
            @close="showApproveModal = false"
        >
            <div class="space-y-4" v-if="selectedItem">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Setujui Pinjaman Karyawan
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Verifikasi persetujuan pengajuan pinjaman dan buat jadwal cicilan otomatis bulanan.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Karyawan:</span>
                        <span class="font-semibold text-slate-900">{{ selectedItem.employee?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm" v-if="selectedItem.employee?.nip">
                        <span class="text-slate-500">NIP / Divisi:</span>
                        <span class="font-mono text-xs text-slate-600">{{ selectedItem.employee?.nip }} • {{ selectedItem.employee?.division?.name || '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Nominal Pinjaman:</span>
                        <span class="font-bold text-emerald-600 text-base">{{ formatCurrency(selectedItem.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Durasi Tenor:</span>
                        <span class="font-semibold text-slate-700">{{ selectedItem.tenor }} Bulan</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Cicilan per Bulan:</span>
                        <span class="font-bold text-slate-900">{{ formatCurrency(selectedItem.amount / selectedItem.tenor) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm" v-if="selectedItem.employee?.basic_salary">
                        <span class="text-slate-500">Gaji Pokok Karyawan:</span>
                        <span class="font-medium text-slate-700">{{ formatCurrency(selectedItem.employee?.basic_salary) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm" v-if="getSalaryRatio(selectedItem.amount, selectedItem.tenor, selectedItem.employee?.basic_salary) !== null">
                        <span class="text-slate-500">Beban terhadap Gaji:</span>
                        <span
                            class="font-bold text-xs px-2 py-0.5 rounded"
                            :class="getSalaryRatio(selectedItem.amount, selectedItem.tenor, selectedItem.employee?.basic_salary) > 35 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-800'"
                        >
                            {{ getSalaryRatio(selectedItem.amount, selectedItem.tenor, selectedItem.employee?.basic_salary).toFixed(1) }}%
                            ({{ getSalaryRatio(selectedItem.amount, selectedItem.tenor, selectedItem.employee?.basic_salary) > 35 ? 'Di atas 35%' : 'Aman' }})
                        </span>
                    </div>
                    <div class="flex justify-between items-start text-sm" v-if="selectedItem.description">
                        <span class="text-slate-500 shrink-0">Keperluan:</span>
                        <span class="text-slate-700 text-right ml-4 text-xs italic">"{{ selectedItem.description }}"</span>
                    </div>
                </div>

                <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        <span class="font-semibold">Catatan:</span> Setelah disetujui, sistem akan otomatis menjadwalkan cicilan pinjaman per bulan dan memotongnya melalui slip gaji bulanan (payroll).
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="approveForm.processing"
                    @click="showApproveModal = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    @click="submitApprove"
                    :loading="approveForm.processing"
                    :disabled="approveForm.processing"
                >
                    Ya, Setujui Pinjaman
                </Button>
            </template>
        </Modal>

        <!-- Modal Tolak -->
        <Modal
            :show="showRejectModal"
            maxWidth="md"
            @close="showRejectModal = false"
        >
            <div class="space-y-4" v-if="selectedItem">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Tolak Pengajuan Pinjaman
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Konfirmasi penolakan pengajuan pinjaman/kasbon ini.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="text-sm">
                        <span class="text-slate-500">Nama Karyawan: </span>
                        <span class="font-semibold text-slate-900">{{ selectedItem.employee?.name }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="text-slate-500">Nominal Pinjaman: </span>
                        <span class="font-bold text-rose-600">{{ formatCurrency(selectedItem.amount) }}</span>
                    </div>
                    <div class="text-sm" v-if="selectedItem.description">
                        <span class="text-slate-500">Keperluan: </span>
                        <span class="text-slate-700 italic">"{{ selectedItem.description }}"</span>
                    </div>
                </div>

                <div class="p-3 bg-rose-50/70 border border-rose-200/80 rounded-xl flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-xs text-rose-800 leading-relaxed">
                        Apakah Anda yakin ingin menolak pengajuan pinjaman ini? Pengajuan akan berstatus Ditolak dan tidak dapat diproses lagi.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="rejectForm.processing"
                    @click="showRejectModal = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="submitReject"
                    :loading="rejectForm.processing"
                    :disabled="rejectForm.processing"
                >
                    Ya, Tolak Pinjaman
                </Button>
            </template>
        </Modal>
    </div>
</template>
