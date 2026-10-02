<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'

const props = defineProps({
    payroll: Object,
    item: Object,
    company: Object,
    attendanceSummary: Object,
})

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head :title="`Rincian Gaji ${item.employee?.name} - Frans HRIS`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Rincian Slip Gaji Karyawan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Batch: <span class="font-mono font-bold text-primary-700">{{ payroll.code }}</span> ({{ payroll.start_date }} s/d {{ payroll.end_date }})
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.payrolls.show', payroll.id)">
                        <Button variant="secondary" size="md">Kembali ke Batch</Button>
                    </Link>
                    <a :href="route('owner.finance.payrolls.slip-single', { id: payroll.id, itemId: item.id })" target="_blank">
                        <Button variant="primary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Slip Gaji
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Employee & Attendance Banner -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Employee Card -->
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm space-y-3">
                    <h3 class="text-xs font-bold text-secondary-500 uppercase tracking-wider">Identitas Karyawan</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 font-bold text-lg flex items-center justify-center shrink-0">
                            {{ item.employee?.name?.charAt(0) || 'E' }}
                        </div>
                        <div>
                            <div class="text-base font-bold text-secondary-900">{{ item.employee?.name }}</div>
                            <div class="text-xs text-secondary-500 font-mono">{{ item.employee?.nip }}</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-secondary-100 text-xs space-y-1 text-secondary-600">
                        <div><strong>Divisi:</strong> {{ item.employee?.division?.name || '-' }}</div>
                        <div><strong>Jabatan:</strong> {{ item.employee?.position?.name || '-' }}</div>
                        <div><strong>Cabang:</strong> {{ item.employee?.branch?.name || '-' }}</div>
                        <div><strong>PTKP:</strong> {{ item.ptkp_status || 'TK/0' }}</div>
                    </div>
                </div>

                <!-- Attendance Stats Card -->
                <div class="md:col-span-2 bg-white p-5 rounded-xl border border-secondary-200 shadow-sm space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xs font-bold text-secondary-500 uppercase tracking-wider">Rekapitulasi Kehadiran Periode Ini</h3>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-primary-50 text-primary-700">
                            {{ attendanceSummary.percent }}% Rasio Kehadiran
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100 text-center">
                            <div class="text-xl font-bold">{{ attendanceSummary.present }} Hari</div>
                            <div class="text-[11px] text-emerald-600 font-medium mt-0.5">Hadir Tepat Waktu</div>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-50 text-amber-800 border border-amber-100 text-center">
                            <div class="text-xl font-bold">{{ attendanceSummary.late }} Hari</div>
                            <div class="text-[11px] text-amber-600 font-medium mt-0.5">Terlambat</div>
                        </div>
                        <div class="p-3 rounded-xl bg-rose-50 text-rose-800 border border-rose-100 text-center">
                            <div class="text-xl font-bold">{{ attendanceSummary.absent }} Hari</div>
                            <div class="text-[11px] text-rose-600 font-medium mt-0.5">Alpha / Mangkir</div>
                        </div>
                        <div class="p-3 rounded-xl bg-blue-50 text-blue-800 border border-blue-100 text-center">
                            <div class="text-xl font-bold">{{ attendanceSummary.leave }} Hari</div>
                            <div class="text-[11px] text-blue-600 font-medium mt-0.5">Cuti / Izin Sah</div>
                        </div>
                    </div>

                    <p v-if="item.remarks" class="text-xs text-amber-800 bg-amber-50/70 p-2.5 rounded-lg border border-amber-200/60">
                        <strong>Catatan Penyesuaian:</strong> {{ item.remarks }}
                    </p>
                </div>
            </div>

            <!-- Detailed Breakdown: Earnings & Deductions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Earnings Column -->
                <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                    <div class="bg-emerald-50/60 p-4 border-b border-emerald-100 flex items-center justify-between">
                        <h3 class="font-bold text-emerald-900">Pendapatan (Earnings)</h3>
                        <span class="text-xs font-semibold text-emerald-700">Komponen Penambah</span>
                    </div>

                    <div class="p-4 divide-y divide-secondary-100 text-sm">
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Gaji Pokok (Basic Salary)</span>
                            <span class="font-semibold text-secondary-900">{{ formatCurrency(item.basic_salary) }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Tunjangan Tetap</span>
                            <span class="font-semibold text-secondary-900">{{ formatCurrency(item.fixed_allowance) }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Uang Makan (Hadir {{ attendanceSummary.present }} hari)</span>
                            <span class="font-semibold text-secondary-900">{{ formatCurrency(item.daily_allowance) }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Tunjangan Lainnya</span>
                            <span class="font-semibold text-secondary-900">{{ formatCurrency(item.other_allowance) }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Upah Lembur (Overtime)</span>
                            <span class="font-semibold text-secondary-900">{{ formatCurrency(item.overtime_amount) }}</span>
                        </div>
                        <div v-if="item.reimbursement_amount > 0" class="py-2.5 flex justify-between">
                            <span class="text-blue-600 font-medium">Klaim Biaya (Reimbursement)</span>
                            <span class="font-bold text-blue-600">{{ formatCurrency(item.reimbursement_amount) }}</span>
                        </div>
                        <div v-if="item.bonus > 0" class="py-2.5 flex justify-between">
                            <span class="text-emerald-600 font-medium">Bonus Tambahan</span>
                            <span class="font-bold text-emerald-600">{{ formatCurrency(item.bonus) }}</span>
                        </div>
                        <div class="py-3 flex justify-between font-bold text-emerald-700 border-t-2 border-secondary-200 text-base">
                            <span>Total Pendapatan Kotor</span>
                            <span>{{ formatCurrency(item.total_earnings) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Deductions Column -->
                <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                    <div class="bg-rose-50/60 p-4 border-b border-rose-100 flex items-center justify-between">
                        <h3 class="font-bold text-rose-900">Potongan (Deductions)</h3>
                        <span class="text-xs font-semibold text-rose-700">Komponen Pengurang</span>
                    </div>

                    <div class="p-4 divide-y divide-secondary-100 text-sm">
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Denda Keterlambatan</span>
                            <span class="font-medium text-rose-600">{{ item.late_penalty_amount > 0 ? '-' + formatCurrency(item.late_penalty_amount) : 'Rp 0' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Denda Alpha / Absen</span>
                            <span class="font-medium text-rose-600">{{ item.alpha_penalty_amount > 0 ? '-' + formatCurrency(item.alpha_penalty_amount) : 'Rp 0' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">Pajak Penghasilan (PPh 21)</span>
                            <span class="font-medium text-rose-600">{{ item.pph21_amount > 0 ? '-' + formatCurrency(item.pph21_amount) : 'Rp 0' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">BPJS Kesehatan (1%)</span>
                            <span class="font-medium text-rose-600">{{ item.bpjs_kesehatan_employee > 0 ? '-' + formatCurrency(item.bpjs_kesehatan_employee) : 'Rp 0' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">BPJS TK - JHT (2%)</span>
                            <span class="font-medium text-rose-600">{{ item.jht_employee > 0 ? '-' + formatCurrency(item.jht_employee) : 'Rp 0' }}</span>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <span class="text-secondary-600">BPJS TK - JP (1%)</span>
                            <span class="font-medium text-rose-600">{{ item.jp_employee > 0 ? '-' + formatCurrency(item.jp_employee) : 'Rp 0' }}</span>
                        </div>
                        <div v-if="item.other_deductions > 0" class="py-2.5 flex justify-between">
                            <span class="text-rose-700 font-semibold">Potongan Kasbon / Lainnya</span>
                            <span class="font-bold text-rose-700">-{{ formatCurrency(item.other_deductions) }}</span>
                        </div>
                        <div class="py-3 flex justify-between font-bold text-rose-700 border-t-2 border-secondary-200 text-base">
                            <span>Total Potongan</span>
                            <span>-{{ formatCurrency(item.total_deductions) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Salary Big Card -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-100">Gaji Bersih Diterima Karyawan (Take Home Pay)</div>
                    <div class="text-3xl font-black mt-1">{{ formatCurrency(item.net_salary) }}</div>
                </div>
                <div class="text-sm text-emerald-100 sm:text-right">
                    <div>Perhitungan Gaji Resmi</div>
                    <div class="text-xs text-white/80 mt-0.5">Sesuai Peraturan Ketenagakerjaan RI</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
