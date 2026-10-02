<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    payroll: Object,
    unpaidLoanInstallments: Object,
})

// Status Changer
const updateStatusForm = useForm({
    status: props.payroll.status,
})

const handleStatusChange = (newStatus) => {
    if (confirm(`Apakah Anda yakin ingin mengubah status penggajian ini menjadi ${newStatus.toUpperCase()}?`)) {
        updateStatusForm.status = newStatus
        updateStatusForm.patch(route('owner.finance.payrolls.update-status', props.payroll.id))
    }
}

// Adjust Modal State
const showAdjustModal = ref(false)
const selectedItem = ref(null)

const adjustForm = useForm({
    items: {},
})

const openAdjustModal = (item) => {
    selectedItem.value = item
    adjustForm.items = {
        [item.id]: {
            bonus: item.bonus || 0,
            other_deductions: item.other_deductions || 0,
            remarks: item.remarks || '',
            loan_installment_id: item.loan_installment_id || '',
        }
    }
    showAdjustModal.value = true
}

const submitAdjust = () => {
    adjustForm.patch(route('owner.finance.payrolls.update-items', props.payroll.id), {
        onSuccess: () => {
            showAdjustModal.value = false
            selectedItem.value = null
        }
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head :title="`Batch Penggajian ${payroll.code} - Frans HRIS`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Batch Penggajian: {{ payroll.code }}</h1>
                        <Badge v-if="payroll.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                        <Badge v-else-if="payroll.status === 'process'" variant="warning">Dalam Proses</Badge>
                        <Badge v-else variant="danger">Dibatalkan</Badge>
                    </div>
                    <p class="text-sm text-secondary-500 mt-1">
                        Periode: <span class="font-medium text-secondary-800">{{ payroll.start_date }} s/d {{ payroll.end_date }}</span> • Cabang: {{ payroll.branch?.name || 'Semua Cabang' }} • Divisi: {{ payroll.division?.name || 'Semua Divisi' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Link :href="route('owner.finance.payrolls.index')">
                        <Button variant="secondary" size="md">Kembali</Button>
                    </Link>

                    <a :href="route('owner.finance.payrolls.export-bank-csv', payroll.id)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            CSV Bank Transfer
                        </Button>
                    </a>

                    <a :href="route('owner.finance.payrolls.slips', payroll.id)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Semua Slip
                        </Button>
                    </a>

                    <!-- Status Changer Buttons -->
                    <Button
                        v-if="payroll.status === 'process'"
                        variant="primary"
                        size="md"
                        @click="handleStatusChange('paid')"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Tandai Selesai (PAID)
                    </Button>

                    <Button
                        v-if="payroll.status === 'paid'"
                        variant="secondary"
                        size="md"
                        @click="handleStatusChange('process')"
                    >
                        Revisi (Kembalikan ke Process)
                    </Button>
                </div>
            </div>

            <!-- Aggregate Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Total Bersih Disbursed (Net)</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">{{ formatCurrency(payroll.total_amount) }}</div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Total Pendapatan Kotor</div>
                    <div class="text-xl font-bold text-secondary-900 mt-1">
                        {{ formatCurrency(payroll.items?.reduce((acc, curr) => acc + Number(curr.total_earnings || 0), 0)) }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Total Potongan (Deductions)</div>
                    <div class="text-xl font-bold text-rose-600 mt-1">
                        {{ formatCurrency(payroll.items?.reduce((acc, curr) => acc + Number(curr.total_deductions || 0), 0)) }}
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Jumlah Karyawan</div>
                    <div class="text-2xl font-bold text-primary-700 mt-1">{{ payroll.total_employees }} Orang</div>
                </div>
            </div>

            <!-- Employee Payroll Items Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-secondary-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-secondary-900">Rincian Slip Gaji Karyawan</h3>
                        <p class="text-xs text-secondary-500 mt-0.5">Klik tombol penyesuaian untuk mengubah bonus, potongan lain, atau mengaitkan kasbon.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3 text-right">Gaji Pokok</th>
                                <th class="px-5 py-3 text-right">Tunjangan</th>
                                <th class="px-5 py-3 text-right">Lembur & Klaim</th>
                                <th class="px-5 py-3 text-right">Denda Absen</th>
                                <th class="px-5 py-3 text-right">Pajak & BPJS</th>
                                <th class="px-5 py-3 text-right">Kasbon & Lainnya</th>
                                <th class="px-5 py-3 text-right font-black text-secondary-900">Gaji Bersih (Net)</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="item in payroll.items" :key="item.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ item.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ item.employee?.nip || '-' }} • {{ item.employee?.position?.name || '-' }}</div>
                                    <div v-if="item.remarks" class="text-[11px] text-amber-700 italic mt-0.5 max-w-[200px] truncate" :title="item.remarks">
                                        ℹ️ {{ item.remarks }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-medium text-secondary-900">
                                    {{ formatCurrency(item.basic_salary) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800 text-xs">
                                    <div>{{ formatCurrency((item.fixed_allowance || 0) + (item.daily_allowance || 0) + (item.other_allowance || 0)) }}</div>
                                    <div v-if="item.bonus > 0" class="text-emerald-600 font-semibold">+{{ formatCurrency(item.bonus) }} (Bonus)</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800 text-xs">
                                    <div>{{ formatCurrency(item.overtime_amount || 0) }}</div>
                                    <div v-if="item.reimbursement_amount > 0" class="text-blue-600 font-medium">+{{ formatCurrency(item.reimbursement_amount) }} (Klaim)</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-rose-600 text-xs font-medium">
                                    {{ item.total_penalty > 0 ? '-' + formatCurrency(item.total_penalty) : 'Rp 0' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-700 text-xs">
                                    <div>PPh: {{ formatCurrency(item.pph21_amount || 0) }}</div>
                                    <div class="text-secondary-400">BPJS: {{ formatCurrency((item.bpjs_kesehatan_employee || 0) + (item.jht_employee || 0) + (item.jp_employee || 0)) }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-rose-700 text-xs font-semibold">
                                    <span v-if="item.other_deductions > 0">-{{ formatCurrency(item.other_deductions) }}</span>
                                    <span v-else class="text-secondary-400 font-normal">Rp 0</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-black text-emerald-600 text-base">
                                    {{ formatCurrency(item.net_salary) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            v-if="payroll.status !== 'paid'"
                                            class="p-1.5 text-secondary-500 hover:text-primary-600 transition"
                                            title="Penyesuaian Bonus / Potongan"
                                            @click="openAdjustModal(item)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <Link :href="route('owner.finance.payrolls.items.show', { id: payroll.id, itemId: item.id })">
                                            <Button variant="secondary" size="sm">Detail</Button>
                                        </Link>
                                        <a :href="route('owner.finance.payrolls.slip-single', { id: payroll.id, itemId: item.id })" target="_blank">
                                            <Button variant="secondary" size="sm">Slip</Button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Adjust Modal -->
        <Modal :show="showAdjustModal" title="Penyesuaian Tambahan / Potongan Gaji" @close="showAdjustModal = false">
            <div v-if="selectedItem && adjustForm.items[selectedItem.id]" class="space-y-4">
                <div class="p-3.5 rounded-xl bg-secondary-50 border border-secondary-200">
                    <div class="text-sm font-bold text-secondary-900">{{ selectedItem.employee?.name }}</div>
                    <div class="text-xs text-secondary-500 font-mono">{{ selectedItem.employee?.nip }} • Gaji Pokok: {{ formatCurrency(selectedItem.basic_salary) }}</div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-secondary-700 mb-1">Bonus Tambahan (Rp)</label>
                    <input
                        type="number"
                        v-model="adjustForm.items[selectedItem.id].bonus"
                        placeholder="Contoh: 500000"
                        class="input"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-secondary-700 mb-1">Potongan Lain-lain (Rp)</label>
                    <input
                        type="number"
                        v-model="adjustForm.items[selectedItem.id].other_deductions"
                        placeholder="Contoh: 250000"
                        class="input"
                    />
                </div>

                <!-- Unpaid Loan Picker if available -->
                <div v-if="unpaidLoanInstallments && unpaidLoanInstallments[selectedItem.employee_id]">
                    <label class="block text-xs font-semibold text-secondary-700 mb-1">Pilih Tagihan Cicilan Kasbon Jatuh Tempo</label>
                    <select
                        v-model="adjustForm.items[selectedItem.id].loan_installment_id"
                        class="input"
                    >
                        <option value="">-- Tanpa Potongan Kasbon --</option>
                        <option
                            v-for="inst in unpaidLoanInstallments[selectedItem.employee_id]"
                            :key="inst.id"
                            :value="inst.id"
                        >
                            Jatuh tempo: {{ inst.due_date }} • Nominal: {{ formatCurrency(inst.amount) }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-secondary-700 mb-1">Catatan / Keterangan Penyesuaian</label>
                    <input
                        type="text"
                        v-model="adjustForm.items[selectedItem.id].remarks"
                        placeholder="Contoh: Bonus target penjualan Q3..."
                        class="input"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-secondary-200">
                    <Button variant="secondary" @click="showAdjustModal = false">Batal</Button>
                    <Button variant="primary" :disabled="adjustForm.processing" @click="submitAdjust">
                        {{ adjustForm.processing ? 'Menyimpan...' : 'Simpan Penyesuaian' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
