<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    payrolls: Object,
    thrItems: Array,
});

const activeTab = ref('payrolls'); // 'payrolls' or 'thr'
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

const printSlip = () => {
    window.print();
};

const monthNames = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formatPeriod = (month, year) => {
    if (!month || !year) return '-';
    return `${monthNames[month] || month} ${year}`;
};

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
</script>

<template>
    <Head title="Slip Gaji & THR" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Slip Gaji & Kompensasi</h1>
                    <p class="text-sm text-slate-500 mt-1">Akses riwayat slip gaji bulanan dan tunjangan hari raya resmi Anda.</p>
                </div>
            </div>

            <!-- Tab Selection -->
            <div class="flex items-center border-b border-slate-200">
                <nav class="flex gap-6 -mb-px">
                    <button
                        type="button"
                        @click="activeTab = 'payrolls'"
                        :class="[
                            activeTab === 'payrolls'
                                ? 'border-sky-600 text-sky-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'pb-3 text-sm font-semibold border-b-2 transition-colors'
                        ]"
                    >
                        Slip Gaji Bulanan
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'thr'"
                        :class="[
                            activeTab === 'thr'
                                ? 'border-sky-600 text-sky-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'pb-3 text-sm font-semibold border-b-2 transition-colors'
                        ]"
                    >
                        Tunjangan Hari Raya (THR)
                    </button>
                </nav>
            </div>

            <!-- TAB 1: PAYROLLS SLIP -->
            <div v-if="activeTab === 'payrolls'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Daftar Slip Gaji Diterima</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Semua pembayaran gaji yang telah dibayarkan oleh bagian keuangan</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Periode Gaji</th>
                                <th class="px-5 py-3.5">Tanggal Pembayaran</th>
                                <th class="px-5 py-3.5">Gaji Pokok</th>
                                <th class="px-5 py-3.5">Tunjangan</th>
                                <th class="px-5 py-3.5">Potongan</th>
                                <th class="px-5 py-3.5">Take Home Pay</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!payrolls.data || payrolls.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada slip gaji</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Slip gaji bulanan Anda akan muncul otomatis di sini setiap kali proses payroll selesai dan dibayarkan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in payrolls.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-xs">
                                            {{ item.payroll?.month }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ formatPeriod(item.payroll?.month, item.payroll?.year) }}</p>
                                            <p class="text-[11px] text-slate-400">ID: #PAY-{{ item.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ formatDate(item.payroll?.payment_date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ formatRupiah(item.basic_salary) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-emerald-600 font-medium">
                                    +{{ formatRupiah(item.allowances) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-rose-500 font-medium">
                                    -{{ formatRupiah(item.deductions) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-extrabold text-slate-900 text-sm">
                                        {{ formatRupiah(item.net_salary) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openSlipModal(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-semibold rounded-xl border border-sky-200/60 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Slip
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payrolls.links && payrolls.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="payrolls.links" />
                </div>
            </div>

            <!-- TAB 2: THR ITEMS -->
            <div v-if="activeTab === 'thr'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Tunjangan Hari Raya (THR)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar pemberian tunjangan keagamaan dan bonus hari raya</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Tahun THR</th>
                                <th class="px-5 py-3.5">Tanggal Cair</th>
                                <th class="px-5 py-3.5">Nominal THR</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!thrItems || thrItems.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada riwayat THR</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Riwayat THR Anda akan tampil di sini saat pembayaran hari raya telah dibukukan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in thrItems" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900">
                                    THR Tahun {{ item.thr?.year }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ formatDate(item.thr?.payment_date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-extrabold text-emerald-600">
                                    {{ formatRupiah(item.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Sudah Dibayarkan
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500">
                                    {{ item.note || 'Tunjangan Hari Raya Keagamaan' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Slip Gaji Resmi -->
        <Modal :show="isSlipModalOpen" @close="closeSlipModal" maxWidth="lg">
            <template #title>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Slip Gaji Elektronik</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Frans HRIS Official Payslip</p>
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="selectedPayroll" class="space-y-6 text-slate-800" id="printableSlip">
                <!-- Slip Header -->
                <div class="border-b border-slate-200 pb-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Periode Pembayaran</span>
                        <h4 class="text-lg font-extrabold text-slate-900 mt-0.5">
                            {{ formatPeriod(selectedPayroll.payroll?.month, selectedPayroll.payroll?.year) }}
                        </h4>
                    </div>
                    <div class="text-right">
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Tanggal Pembayaran</span>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ formatDate(selectedPayroll.payroll?.payment_date) }}</p>
                    </div>
                </div>

                <!-- Salary Breakdown Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Pendapatan / Earnings -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-emerald-700">Penerimaan (Earnings)</h5>
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Gaji Pokok:</span>
                            <span class="font-semibold text-slate-900">{{ formatRupiah(selectedPayroll.basic_salary) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Tunjangan & Insentif:</span>
                            <span class="font-semibold text-emerald-600">+{{ formatRupiah(selectedPayroll.allowances) }}</span>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 flex justify-between text-xs font-bold text-slate-900">
                            <span>Total Penerimaan:</span>
                            <span>{{ formatRupiah((selectedPayroll.basic_salary || 0) + (selectedPayroll.allowances || 0)) }}</span>
                        </div>
                    </div>

                    <!-- Potongan / Deductions -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-3">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-rose-700">Potongan (Deductions)</h5>
                        <div class="flex justify-between text-xs text-slate-600">
                            <span>Potongan Absensi / Kasbon:</span>
                            <span class="font-semibold text-rose-600">-{{ formatRupiah(selectedPayroll.deductions) }}</span>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 flex justify-between text-xs font-bold text-slate-900">
                            <span>Total Potongan:</span>
                            <span class="text-rose-600">-{{ formatRupiah(selectedPayroll.deductions) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Net Take Home Pay Highlight -->
                <div class="bg-sky-50 border border-sky-100 rounded-xl p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-800">Gaji Bersih Diterima (THP)</span>
                        <p class="text-xs text-slate-500 mt-0.5">Sudah ditransfer ke rekening bank Anda</p>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-black text-sky-700">{{ formatRupiah(selectedPayroll.net_salary) }}</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <button
                        type="button"
                        @click="printSlip"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Slip Gaji
                    </button>
                    <button
                        type="button"
                        @click="closeSlipModal"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </EmployeeLayout>
</template>
