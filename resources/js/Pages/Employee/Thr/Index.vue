<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    thrItems: Object,
    year: Number,
    years: Array,
    filters: Object,
});

const currentYear = new Date().getFullYear();

const filters = reactive({
    year: props.year || '',
});

watch(
    () => props.year,
    (newYear) => {
        filters.year = newYear || '';
    }
);

const yearOptions = computed(() => {
    return [
        { value: '', label: 'Semua Tahun' },
        ...(props.years || []).map((y) => ({
            value: y,
            label: `Tahun ${y}`,
        })),
    ];
});

const applyFilter = () => {
    router.get(
        route('employee.thr.index'),
        { year: filters.year || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const onFilterChange = () => {
    applyFilter();
};

const resetFilters = () => {
    filters.year = '';
    applyFilter();
};

const hasActiveFilters = () => {
    return Boolean(filters.year);
};

// Modal Detail State
const isModalOpen = ref(false);
const selectedItem = ref(null);

const openDetailModal = (item) => {
    selectedItem.value = item;
    isModalOpen.value = true;
};

const closeDetailModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Table Headers
const headers = [
    { label: 'Nama Tunjangan', class: 'min-w-[190px]' },
    { label: 'Tahun', class: 'w-24 text-center' },
    { label: 'Masa Kerja', class: 'w-36 text-center' },
    { label: 'Basis Perhitungan', class: 'w-44 text-right' },
    { label: 'Potongan PPh 21', class: 'w-36 text-right' },
    { label: 'Nominal Bersih (Net)', class: 'w-44 text-right' },
    { label: 'Tanggal Cair', class: 'w-36 text-center' },
    { label: 'Status', class: 'w-32 text-center' },
    { label: 'Aksi', class: 'w-44 text-right' },
];
</script>

<template>
    <Head title="Tunjangan Hari Raya (THR) Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tunjangan Hari Raya (THR)</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Akses riwayat slip tunjangan hari raya (THR) resmi dan rincian perhitungannya.
                    </p>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Tunjangan Hari Raya
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

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <Select
                                label="Tahun Pembayaran"
                                v-model="filters.year"
                                :options="yearOptions"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card & Pagination (1:1 Standar Unified Component) -->
            <div class="space-y-4">
                <DataTable :headers="headers">
                    <template #body>
                        <template v-if="thrItems?.data && thrItems.data.length > 0">
                            <tr
                                v-for="item in thrItems.data"
                                :key="item.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Nama Tunjangan -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-sm text-slate-900">{{ item.holiday_name }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                        {{ item.thr_code }}
                                    </div>
                                </td>

                                <!-- Tahun -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center font-medium text-xs text-slate-700">
                                    {{ item.year }}
                                </td>

                                <!-- Masa Kerja -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span class="font-semibold text-xs text-slate-800">{{ item.tenure_months }} Bulan</span>
                                    <div v-if="item.prorate_multiplier < 1" class="text-[10px] text-amber-600 font-medium">
                                        {{ (item.prorate_multiplier * 100).toFixed(0) }}% Prorate
                                    </div>
                                    <div v-else class="text-[10px] text-emerald-600 font-medium">
                                        Penuh (100%)
                                    </div>
                                </td>

                                <!-- Basis Perhitungan -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-medium text-xs text-slate-800">
                                    {{ formatRupiah(item.basis_amount) }}
                                </td>

                                <!-- Potongan PPh 21 -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-medium text-xs text-rose-600">
                                    <span v-if="item.tax_amount > 0">-{{ formatRupiah(item.tax_amount) }}</span>
                                    <span v-else class="text-slate-400">Rp 0</span>
                                </td>

                                <!-- Nominal Bersih -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-xs text-emerald-700">
                                    {{ formatRupiah(item.net_amount) }}
                                </td>

                                <!-- Tanggal Cair -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center font-medium text-xs text-slate-700">
                                    {{ item.payment_date }}
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <Badge :variant="item.status === 'paid' ? 'success' : 'warning'" size="sm">
                                        {{ item.status === 'paid' ? 'Ditransfer' : 'Diproses' }}
                                    </Badge>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openDetailModal(item)"
                                            title="Lihat Rincian THR"
                                            class="text-xs"
                                        >
                                            Rincian
                                        </Button>

                                        <a
                                            :href="item.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors"
                                            title="Download Slip THR (PDF)"
                                        >
                                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span>PDF</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <TableEmpty
                            v-else
                            :colspan="9"
                            title="Belum Ada Riwayat THR"
                            :message="filters.year ? `Tidak ada catatan pencairan THR yang terbit untuk tahun ${filters.year}.` : 'Belum ada catatan pencairan THR resmi yang diterbitkan untuk akun Anda.'"
                        />
                    </template>
                </DataTable>

                <!-- Standard Table Pagination -->
                <TablePagination :pagination="thrItems" />
            </div>
        </div>

        <!-- Modal Rincian Slip THR -->
        <Modal
            :show="isModalOpen"
            title="Rincian Slip Tunjangan Hari Raya (THR)"
            size="md"
            @close="closeDetailModal"
        >
            <div v-if="selectedItem" class="space-y-4">
                <!-- Header Box -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Perayaan Hari Raya</span>
                        <h4 class="text-base font-bold text-slate-900">{{ selectedItem.holiday_name }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5 font-mono">Kode: {{ selectedItem.thr_code }} &bull; Tahun {{ selectedItem.year }}</p>
                    </div>

                    <div class="text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nominal Bersih</span>
                        <div class="text-xl font-bold text-emerald-700">
                            {{ formatRupiah(selectedItem.net_amount) }}
                        </div>
                        <Badge :variant="selectedItem.status === 'paid' ? 'success' : 'warning'" size="sm" class="mt-1">
                            {{ selectedItem.status === 'paid' ? 'Sudah Ditransfer' : 'Diproses' }}
                        </Badge>
                    </div>
                </div>

                <!-- Breakdown Calculation Card -->
                <div class="p-4 rounded-xl border border-slate-200/80 bg-white space-y-3">
                    <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Rincian Perhitungan THR
                    </h5>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Masa Kerja Karyawan</span>
                            <span class="font-medium text-slate-900">{{ selectedItem.tenure_months }} Bulan</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Gaji Pokok / Basis Acuan</span>
                            <span class="font-medium text-slate-900">{{ formatRupiah(selectedItem.basis_amount) }}</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Pengali Prorate</span>
                            <span class="font-semibold text-slate-800">
                                {{ selectedItem.prorate_multiplier >= 1 ? '100% (1 Bulan Gaji Penuh)' : `${(selectedItem.prorate_multiplier * 100).toFixed(0)}% (${selectedItem.tenure_months}/12)` }}
                            </span>
                        </div>

                        <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-100">
                            <span>Kotor (Gross THR)</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(selectedItem.thr_amount) }}</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Potongan Pajak PPh 21</span>
                            <span class="font-medium text-rose-600">
                                {{ selectedItem.tax_amount > 0 ? `-${formatRupiah(selectedItem.tax_amount)}` : 'Rp 0' }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center text-sm font-bold text-emerald-950 pt-2 border-t border-slate-200">
                            <span>Total Diterima (Net)</span>
                            <span class="text-base text-emerald-700">{{ formatRupiah(selectedItem.net_amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-slate-400">
                    Tanggal pencairan dana: <strong class="text-slate-600">{{ selectedItem.payment_date }}</strong>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between w-full">
                    <Button variant="secondary" @click="closeDetailModal">
                        Tutup
                    </Button>

                    <a
                        v-if="selectedItem"
                        :href="selectedItem.download_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Cetak Slip THR PDF</span>
                    </a>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
