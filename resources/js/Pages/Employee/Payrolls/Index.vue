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
    payrolls: Object,
    year: Number,
    years: Array,
    filters: Object,
});

const currentYear = new Date().getFullYear();

const filters = reactive({
    year: props.year || currentYear,
});

watch(
    () => props.year,
    (newYear) => {
        filters.year = newYear || currentYear;
    }
);

const yearOptions = computed(() => {
    return (props.years || []).map((y) => ({
        value: y,
        label: `Tahun ${y}`,
    }));
});

const applyFilter = () => {
    router.get(
        route('employee.payrolls.index'),
        { year: filters.year },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const onFilterChange = () => {
    applyFilter();
};

const resetFilters = () => {
    filters.year = currentYear;
    applyFilter();
};

const hasActiveFilters = () => {
    return Number(filters.year) !== Number(currentYear);
};

// Modal Detail State
const isSlipModalOpen = ref(false);
const selectedPayroll = ref(null);

const openSlipModal = (item) => {
    selectedPayroll.value = item;
    isSlipModalOpen.value = true;
};

const closeSlipModal = () => {
    isSlipModalOpen.value = false;
    selectedPayroll.value = null;
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Table Headers
const payrollHeaders = [
    { label: 'Periode Gaji', class: 'min-w-[180px]' },
    { label: 'Tanggal Cair', class: 'w-40 text-center' },
    { label: 'Total Pendapatan', class: 'w-44 text-right' },
    { label: 'Total Potongan', class: 'w-40 text-right' },
    { label: 'Gaji Bersih (THP)', class: 'w-48 text-right' },
    { label: 'Status', class: 'w-32 text-center' },
    { label: 'Aksi', class: 'w-44 text-right' },
];
</script>

<template>
    <Head title="Slip Gaji Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Slip Gaji</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Akses riwayat slip gaji resmi bulanan dan rincian penerimaan Anda.
                    </p>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Slip Gaji
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
                                label="Tahun Penggajian"
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
                <DataTable :headers="payrollHeaders">
                    <template #body>
                        <template v-if="payrolls?.data && payrolls.data.length > 0">
                            <tr
                                v-for="item in payrolls.data"
                                :key="item.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Periode Gaji -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-sm text-slate-900">{{ item.period_label }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                        {{ item.payroll_code }} &bull; {{ item.period_start }} - {{ item.period_end }}
                                    </div>
                                </td>

                                <!-- Tanggal Cair -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center font-medium text-xs text-slate-700">
                                    {{ item.paid_at }}
                                </td>

                                <!-- Total Pendapatan -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-medium text-xs text-slate-800">
                                    {{ formatRupiah(item.total_earnings) }}
                                </td>

                                <!-- Total Potongan -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-medium text-xs text-rose-600">
                                    -{{ formatRupiah(item.total_deductions) }}
                                </td>

                                <!-- Take Home Pay -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-xs text-emerald-700">
                                    {{ formatRupiah(item.net_salary) }}
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <Badge variant="success" size="sm">
                                        Lunas
                                    </Badge>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openSlipModal(item)"
                                            title="Lihat Rincian Gaji"
                                            class="text-xs"
                                        >
                                            Rincian
                                        </Button>

                                        <a
                                            :href="item.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-semibold transition-colors"
                                            title="Download PDF Resmi"
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
                            :colspan="7"
                            title="Belum Ada Slip Gaji"
                            :message="`Tidak ada data slip gaji yang tercatat untuk tahun ${filters.year}.`"
                        />
                    </template>
                </DataTable>

                <!-- Standard Table Pagination -->
                <TablePagination :pagination="payrolls" />
            </div>
        </div>

        <!-- Modal Rincian Slip Gaji -->
        <Modal
            :show="isSlipModalOpen"
            title="Rincian Slip Gaji Karyawan"
            size="lg"
            @close="closeSlipModal"
        >
            <div v-if="selectedPayroll" class="space-y-5">
                <!-- Header Box -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Periode Penggajian</span>
                        <h4 class="text-base font-bold text-slate-900">{{ selectedPayroll.period_label }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5 font-mono">Kode: {{ selectedPayroll.payroll_code }}</p>
                    </div>

                    <div class="text-left sm:text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Take Home Pay</span>
                        <div class="text-xl font-bold text-emerald-700">
                            {{ formatRupiah(selectedPayroll.net_salary) }}
                        </div>
                        <Badge variant="success" size="sm" class="mt-1">Sudah Ditransfer</Badge>
                    </div>
                </div>

                <!-- 2-Column Breakdown (Earnings vs Deductions) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Kolom 1: Penerimaan -->
                    <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 space-y-3">
                        <div class="flex items-center justify-between border-b border-emerald-100 pb-2">
                            <h5 class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Penerimaan (Earnings)</h5>
                            <span class="text-xs font-bold text-emerald-700">{{ formatRupiah(selectedPayroll.total_earnings) }}</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Gaji Pokok</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.basic_salary) }}</span>
                            </div>
                            <div v-if="selectedPayroll.fixed_allowance > 0" class="flex justify-between text-slate-600">
                                <span>Tunjangan Tetap</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.fixed_allowance) }}</span>
                            </div>
                            <div v-if="selectedPayroll.daily_allowance > 0" class="flex justify-between text-slate-600">
                                <span>Tunjangan Kehadiran / Harian</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.daily_allowance) }}</span>
                            </div>
                            <div v-if="selectedPayroll.overtime_amount > 0" class="flex justify-between text-slate-600">
                                <span>Upah Lembur</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.overtime_amount) }}</span>
                            </div>
                            <div v-if="selectedPayroll.bonus > 0" class="flex justify-between text-slate-600">
                                <span>Bonus / Insentif</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.bonus) }}</span>
                            </div>
                            <div v-if="selectedPayroll.reimbursement_amount > 0" class="flex justify-between text-slate-600">
                                <span>Reimbursement Klaim</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.reimbursement_amount) }}</span>
                            </div>
                            <div v-if="selectedPayroll.other_allowance > 0" class="flex justify-between text-slate-600">
                                <span>Tunjangan Lainnya</span>
                                <span class="font-medium text-slate-900">{{ formatRupiah(selectedPayroll.other_allowance) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Potongan -->
                    <div class="p-4 rounded-xl border border-rose-100 bg-rose-50/20 space-y-3">
                        <div class="flex items-center justify-between border-b border-rose-100 pb-2">
                            <h5 class="text-xs font-bold text-rose-950 uppercase tracking-wider">Potongan (Deductions)</h5>
                            <span class="text-xs font-bold text-rose-600">-{{ formatRupiah(selectedPayroll.total_deductions) }}</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div v-if="selectedPayroll.late_penalty_amount > 0" class="flex justify-between text-slate-600">
                                <span>Penalti Keterlambatan</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.late_penalty_amount) }}</span>
                            </div>
                            <div v-if="selectedPayroll.alpha_penalty_amount > 0" class="flex justify-between text-slate-600">
                                <span>Potongan Ketidakhadiran (Alpha)</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.alpha_penalty_amount) }}</span>
                            </div>
                            <div v-if="selectedPayroll.pph21_amount > 0" class="flex justify-between text-slate-600">
                                <span>PPh 21</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.pph21_amount) }}</span>
                            </div>
                            <div v-if="selectedPayroll.bpjs_kesehatan_employee > 0" class="flex justify-between text-slate-600">
                                <span>BPJS Kesehatan (Karyawan)</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.bpjs_kesehatan_employee) }}</span>
                            </div>
                            <div v-if="selectedPayroll.jht_employee > 0" class="flex justify-between text-slate-600">
                                <span>BPJS TK - JHT (Karyawan)</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.jht_employee) }}</span>
                            </div>
                            <div v-if="selectedPayroll.jp_employee > 0" class="flex justify-between text-slate-600">
                                <span>BPJS TK - JP (Karyawan)</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.jp_employee) }}</span>
                            </div>
                            <div v-if="selectedPayroll.loan_deduction > 0" class="flex justify-between text-slate-600">
                                <span>Cicilan Kasbon / Pinjaman</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.loan_deduction) }}</span>
                            </div>
                            <div v-if="selectedPayroll.other_deductions > 0" class="flex justify-between text-slate-600">
                                <span>Potongan Lainnya</span>
                                <span class="font-medium text-rose-700">-{{ formatRupiah(selectedPayroll.other_deductions) }}</span>
                            </div>
                            <div v-if="selectedPayroll.total_deductions === 0" class="text-slate-400 italic">
                                Tidak ada potongan untuk periode ini.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info BPJS Tanggungan Perusahaan (Informational) -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
                    <span class="font-bold text-slate-700 block mb-1.5">Kontribusi BPJS Ditanggung Perusahaan (Benefit Tambahan):</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-slate-600">
                        <div>BPJS Kes: <strong class="text-slate-900">{{ formatRupiah(selectedPayroll.bpjs_kesehatan_company) }}</strong></div>
                        <div>JHT: <strong class="text-slate-900">{{ formatRupiah(selectedPayroll.jht_company) }}</strong></div>
                        <div>JKK: <strong class="text-slate-900">{{ formatRupiah(selectedPayroll.jkk_company) }}</strong></div>
                        <div>JKM: <strong class="text-slate-900">{{ formatRupiah(selectedPayroll.jkm_company) }}</strong></div>
                    </div>
                </div>

                <!-- Remarks if any -->
                <div v-if="selectedPayroll.remarks" class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">
                    <span class="font-bold">Catatan HRD:</span> {{ selectedPayroll.remarks }}
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-between w-full">
                    <Button variant="secondary" @click="closeSlipModal">
                        Tutup
                    </Button>

                    <a
                        v-if="selectedPayroll"
                        :href="selectedPayroll.download_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Cetak Slip PDF</span>
                    </a>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
