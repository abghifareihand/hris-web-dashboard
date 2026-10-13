<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'

const props = defineProps({
    payroll: Object,
    item: Object,
    company: Object,
    attendanceSummary: Object,
    prorataDetails: Object,
    bpjsTk: Object,
    bpjsKes: Object,
})

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

// Deteksi & Urai Potongan (Deductions Breakdown) Secara Dinamis
const deductionBreakdown = computed(() => {
    const list = []
    const totalOther = Number(props.item?.other_deductions || 0)
    if (totalOther <= 0) return list

    let remaining = totalOther
    const remarks = props.item?.remarks || ''
    const remarksLower = remarks.toLowerCase()

    // 1. Kasbon / Cicilan Pinjaman
    const loanInstallment = props.item?.loan_installment || props.item?.loanInstallment
    const isLoanAttached = Boolean(props.item?.loan_installment_id || loanInstallment)
    if (isLoanAttached) {
        const loanAmount = Number(loanInstallment?.amount || 0) || Math.min(remaining, totalOther)
        const loanDesc = loanInstallment?.loan?.description || 'Cicilan Pinjaman Kasbon'
        const dueDate = loanInstallment?.due_date ? formatDate(loanInstallment.due_date) : null

        list.push({
            id: 'loan',
            type: 'kasbon',
            label: 'Potongan Cicilan Kasbon',
            amount: loanAmount,
            badge: 'Kasbon',
            badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
            note: dueDate ? `${loanDesc} (Jatuh tempo: ${dueDate})` : loanDesc,
        })
        remaining = Math.max(0, remaining - loanAmount)
    }

    // 2. Prorata Hari Kerja
    const hasProrataDetails = Boolean(props.prorataDetails)
    const hasProrataKeyword = remarksLower.includes('prorata')
    if (hasProrataDetails && (hasProrataKeyword || !isLoanAttached)) {
        const prorateAmount = Number(props.prorataDetails?.prorate_amount || 0) || remaining
        const activeDays = props.prorataDetails?.active_days
        const totalDays = props.prorataDetails?.total_days
        const joinFormatted = props.prorataDetails?.join_date_formatted || formatDate(props.item?.employee?.joined_at)

        list.push({
            id: 'prorata',
            type: 'prorata',
            label: 'Potongan Prorata Gaji',
            amount: prorateAmount,
            badge: 'Prorata',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            note: `Masuk tgl ${joinFormatted} (Aktif: ${activeDays}/${totalDays} hari)`,
        })
        remaining = Math.max(0, remaining - prorateAmount)
    }

    // 3. Sisa Potongan Lainnya / Penyesuaian Manual
    if (remaining > 0 || list.length === 0) {
        let cleanRemarks = remarks
            .replace(/Prorata gaji baru masuk[^\•\n]*/gi, '')
            .replace(/Potongan Kasbon \/ Cicilan Pinjaman[^\•\n]*/gi, '')
            .replace(/Potongan Kasbon[^\•\n]*/gi, '')
            .replace(/Cicilan Pinjaman[^\•\n]*/gi, '')
            .replace(/^[\s•\&\-\/]+|[\s•\&\-\/]+$/g, '')
            .trim()

        const labelText = cleanRemarks ? cleanRemarks : 'Potongan Lainnya'
        list.push({
            id: 'other',
            type: 'manual',
            label: labelText,
            amount: remaining > 0 ? remaining : totalOther,
            badge: 'Lainnya',
            badgeClass: 'bg-slate-100 text-slate-700 border-slate-200',
            note: cleanRemarks ? 'Penyesuaian manual sesuai catatan' : null,
        })
    }

    return list
})
</script>

<template>
    <AppLayout>
        <Head :title="`Rincian Gaji ${item.employee?.name} - ${company?.name_company || 'HRIS'}`" />

        <div class="space-y-6">
            <!-- Header (Full Width) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <Link
                        :href="route('owner.finance.payrolls.show', payroll.id)"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 shadow-xs transition shrink-0 mt-0.5"
                        title="Kembali ke Daftar Slip Gaji Batch"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rincian Slip Gaji Karyawan</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200 font-mono font-bold text-xs tracking-wider">
                                {{ payroll.code }}
                            </span>
                            <Badge v-if="payroll.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="payroll.status === 'process'" variant="warning">Dalam Proses</Badge>
                            <Badge v-else variant="danger">Dibatalkan</Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                            <span class="font-medium text-slate-700">{{ formatDate(payroll.start_date) }} – {{ formatDate(payroll.end_date) }}</span>
                            <span class="text-slate-300">•</span>
                            <span>{{ item.employee?.branch?.name || payroll.branch?.name || 'Semua Cabang' }}</span>
                            <span class="text-slate-300">•</span>
                            <span>{{ item.employee?.division?.name || payroll.division?.name || 'Semua Divisi' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
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

            <!-- Content Utama (Tetap di tengah) -->
            <div class="max-w-5xl mx-auto space-y-6">

            <!-- Employee & Attendance Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Employee Card -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Identitas Karyawan</span>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            {{ item.ptkp_status || 'TK/0' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-700 font-bold text-lg flex items-center justify-center shrink-0 border border-primary-100">
                            {{ item.employee?.name?.charAt(0) || 'K' }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-base font-bold text-slate-900 truncate">{{ item.employee?.name }}</div>
                            <div class="text-xs text-slate-500 font-mono mt-0.5">{{ item.employee?.nip || '-' }}</div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 text-xs space-y-2 text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jabatan:</span>
                            <span class="font-semibold text-slate-800">{{ item.employee?.position?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Divisi:</span>
                            <span class="font-semibold text-slate-800">{{ item.employee?.division?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cabang:</span>
                            <span class="font-semibold text-slate-800">{{ item.employee?.branch?.name || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tgl Bergabung:</span>
                            <span class="font-semibold text-slate-800">{{ formatDate(item.employee?.joined_at) }}</span>
                        </div>
                        <div v-if="item.employee?.bank_account_number" class="flex justify-between pt-1 border-t border-slate-100">
                            <span class="text-slate-500">Rekening:</span>
                            <span class="font-mono font-semibold text-slate-800">
                                {{ item.employee?.bank_name }} {{ item.employee?.bank_account_number }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Attendance Stats Card -->
                <div class="md:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rekapitulasi Kehadiran Periode Ini</span>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-200">
                            {{ attendanceSummary?.percent || 0 }}% Rasio Kehadiran
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-100 text-center">
                            <div class="text-2xl font-bold text-emerald-800">{{ attendanceSummary?.present || 0 }}</div>
                            <div class="text-xs text-emerald-600 font-medium mt-0.5">Hari Hadir</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-100 text-center">
                            <div class="text-2xl font-bold text-amber-800">{{ attendanceSummary?.late || 0 }}</div>
                            <div class="text-xs text-amber-600 font-medium mt-0.5">Terlambat</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-rose-50/70 border border-rose-100 text-center">
                            <div class="text-2xl font-bold text-rose-800">{{ attendanceSummary?.absent || 0 }}</div>
                            <div class="text-xs text-rose-600 font-medium mt-0.5">Alpha / Mangkir</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-100 text-center">
                            <div class="text-2xl font-bold text-sky-800">{{ attendanceSummary?.leave || 0 }}</div>
                            <div class="text-xs text-sky-600 font-medium mt-0.5">Cuti / Izin</div>
                        </div>
                    </div>

                    <!-- Indikator Catatan / Penyesuaian Gaji -->
                    <div v-if="item.remarks" class="p-3 rounded-xl bg-amber-50/80 border border-amber-200/80 text-xs text-amber-900 leading-relaxed flex items-start gap-2">
                        <span class="text-sm">📌</span>
                        <div>
                            <strong>Catatan Penyesuaian Penggajian:</strong>
                            <p class="mt-0.5 text-amber-800">{{ item.remarks }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown: Earnings & Deductions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Earnings Column -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="bg-emerald-50/60 p-4 border-b border-emerald-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                            <h3 class="font-bold text-emerald-950 text-sm">Pendapatan (Earnings)</h3>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700">Komponen Penambah</span>
                    </div>

                    <div class="p-4 divide-y divide-slate-100 text-sm">
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-600">Gaji Pokok (Basic Salary)</span>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(item.basic_salary) }}</span>
                        </div>

                        <div v-if="Number(item.fixed_allowance || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-600">Tunjangan Tetap</span>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(item.fixed_allowance) }}</span>
                        </div>

                        <div v-if="Number(item.daily_allowance || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">Tunjangan Harian / Kehadiran</div>
                                <div class="text-[11px] text-slate-400">Total kehadiran: {{ attendanceSummary?.present || 0 }} hari</div>
                            </div>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(item.daily_allowance) }}</span>
                        </div>

                        <div v-if="Number(item.other_allowance || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-600">Tunjangan Lainnya</span>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(item.other_allowance) }}</span>
                        </div>

                        <div v-if="Number(item.overtime_amount || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">Upah Lembur (Overtime)</div>
                                <div class="text-[11px] text-emerald-600 font-medium">Kompensasi kerja lembur disetujui</div>
                            </div>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(item.overtime_amount) }}</span>
                        </div>

                        <div v-if="Number(item.reimbursement_amount || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-blue-700 font-medium">Klaim Biaya (Reimbursement)</div>
                                <div v-if="item.reimbursements?.length" class="text-[11px] text-slate-400">
                                    {{ item.reimbursements.length }} klaim biaya disetujui
                                </div>
                            </div>
                            <span class="font-bold text-blue-700">{{ formatCurrency(item.reimbursement_amount) }}</span>
                        </div>

                        <div v-if="Number(item.bonus || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-emerald-700 font-medium">Bonus Tambahan</div>
                                <div class="text-[11px] text-slate-400">Insentif / bonus penyesuaian periode ini</div>
                            </div>
                            <span class="font-bold text-emerald-700">{{ formatCurrency(item.bonus) }}</span>
                        </div>

                        <div class="py-3 flex items-center justify-between font-bold text-emerald-800 border-t-2 border-slate-200 text-base">
                            <span>Total Pendapatan Kotor</span>
                            <span>{{ formatCurrency(item.total_earnings) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Deductions Column (100% DINAMIS) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="bg-rose-50/60 p-4 border-b border-rose-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                            <h3 class="font-bold text-rose-950 text-sm">Potongan (Deductions)</h3>
                        </div>
                        <span class="text-xs font-semibold text-rose-700">Komponen Pengurang</span>
                    </div>

                    <div class="p-4 divide-y divide-slate-100 text-sm">
                        <!-- Denda Keterlambatan -->
                        <div v-if="Number(item.late_penalty_amount || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">Denda Keterlambatan</div>
                                <div class="text-[11px] text-rose-500 font-medium">Terlambat {{ attendanceSummary?.late || 0 }} hari</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.late_penalty_amount) }}</span>
                        </div>

                        <!-- Denda Alpha / Absen -->
                        <div v-if="Number(item.alpha_penalty_amount || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">Denda Alpha / Absen</div>
                                <div class="text-[11px] text-rose-500 font-medium">Alpha {{ attendanceSummary?.absent || 0 }} hari kerja</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.alpha_penalty_amount) }}</span>
                        </div>

                        <!-- PPh 21 -->
                        <div v-if="Number(item.pph21_amount || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">Pajak Penghasilan (PPh 21)</div>
                                <div class="text-[11px] text-slate-400">Status tarif PTKP {{ item.ptkp_status || 'TK/0' }}</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.pph21_amount) }}</span>
                        </div>

                        <!-- BPJS Kesehatan -->
                        <div v-if="Number(item.bpjs_kesehatan_employee || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">BPJS Kesehatan ({{ Number(bpjsKes?.employee_percent || 1) }}%)</div>
                                <div class="text-[11px] text-slate-400">Iuran kepesertaan karyawan</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.bpjs_kesehatan_employee) }}</span>
                        </div>

                        <!-- BPJS TK - JHT -->
                        <div v-if="Number(item.jht_employee || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">BPJS TK - JHT ({{ Number(bpjsTk?.jht_employee_percent || 2) }}%)</div>
                                <div class="text-[11px] text-slate-400">Jaminan Hari Tua</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.jht_employee) }}</span>
                        </div>

                        <!-- BPJS TK - JP -->
                        <div v-if="Number(item.jp_employee || 0) > 0" class="py-2.5 flex items-center justify-between">
                            <div>
                                <div class="text-slate-600">BPJS TK - JP ({{ Number(bpjsTk?.jp_employee_percent || 1) }}%)</div>
                                <div class="text-[11px] text-slate-400">Jaminan Pensiun</div>
                            </div>
                            <span class="font-semibold text-rose-600">-{{ formatCurrency(item.jp_employee) }}</span>
                        </div>

                        <!-- RINCIAN POTONGAN LAINNYA / KASBON / PRORATA (DINAMIS) -->
                        <template v-if="deductionBreakdown.length > 0">
                            <div
                                v-for="ded in deductionBreakdown"
                                :key="ded.id"
                                class="py-2.5 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-slate-800 font-semibold">{{ ded.label }}</span>
                                        <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold border', ded.badgeClass]">
                                            {{ ded.badge }}
                                        </span>
                                    </div>
                                    <div v-if="ded.note" class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        {{ ded.note }}
                                    </div>
                                </div>
                                <span class="font-bold text-rose-700 whitespace-nowrap">
                                    -{{ formatCurrency(ded.amount) }}
                                </span>
                            </div>
                        </template>

                        <!-- Jika tidak ada potongan sama sekali -->
                        <div
                            v-if="Number(item.total_deductions || 0) === 0"
                            class="py-4 text-center text-xs text-slate-400 italic"
                        >
                            Tidak ada potongan pada periode penggajian ini.
                        </div>

                        <div class="py-3 flex items-center justify-between font-bold text-rose-800 border-t-2 border-slate-200 text-base">
                            <span>Total Potongan</span>
                            <span>-{{ formatCurrency(item.total_deductions) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manfaat BPJS Ditanggung Perusahaan (Company Contribution) -->
            <div v-if="Number(item.total_benefits || 0) > 0" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Iuran BPJS Ditanggung Perusahaan (Company Benefits)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Iuran proteksi ketenagakerjaan yang dibayarkan langsung oleh perusahaan untuk karyawan (di luar gaji).</p>
                    </div>
                    <div class="text-sm font-bold text-indigo-700 whitespace-nowrap">
                        Total Manfaat: +{{ formatCurrency(item.total_benefits) }}
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-3 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                        <div class="text-slate-500 text-[11px]">BPJS Kes ({{ Number(bpjsKes?.company_percent || 4) }}%)</div>
                        <div class="font-semibold text-slate-800 mt-1">{{ formatCurrency(item.bpjs_kesehatan_company) }}</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                        <div class="text-slate-500 text-[11px]">JHT ({{ Number(bpjsTk?.jht_company_percent || 3.7) }}%)</div>
                        <div class="font-semibold text-slate-800 mt-1">{{ formatCurrency(item.jht_company) }}</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                        <div class="text-slate-500 text-[11px]">JP ({{ Number(bpjsTk?.jp_company_percent || 2) }}%)</div>
                        <div class="font-semibold text-slate-800 mt-1">{{ formatCurrency(item.jp_company) }}</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                        <div class="text-slate-500 text-[11px]">JKK ({{ Number(bpjsTk?.jkk_percent || 0.24) }}%)</div>
                        <div class="font-semibold text-slate-800 mt-1">{{ formatCurrency(item.jkk_company) }}</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                        <div class="text-slate-500 text-[11px]">JKM ({{ Number(bpjsTk?.jkm_percent || 0.3) }}%)</div>
                        <div class="font-semibold text-slate-800 mt-1">{{ formatCurrency(item.jkm_company) }}</div>
                    </div>
                </div>
            </div>

            <!-- Net Salary Big Card (Take Home Pay) -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white shadow-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-100 flex items-center gap-2">
                        <span>GAJI BERSIH DITERIMA KARYAWAN (TAKE HOME PAY)</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-extrabold font-mono mt-1 tracking-tight">
                        {{ formatCurrency(item.net_salary) }}
                    </div>
                    <p class="text-xs text-emerald-100/90 mt-1">
                        Total Pendapatan Kotor ({{ formatCurrency(item.total_earnings) }}) &minus; Total Potongan ({{ formatCurrency(item.total_deductions) }})
                    </p>
                </div>

                <div class="sm:text-right border-t sm:border-t-0 sm:border-l border-emerald-500/40 pt-3 sm:pt-0 sm:pl-6 text-xs text-emerald-100 space-y-1">
                    <div class="font-bold text-white text-sm">Metode Pembayaran:</div>
                    <div v-if="item.employee?.bank_account_number" class="font-mono text-white">
                        {{ item.employee?.bank_name }} - {{ item.employee?.bank_account_number }}
                    </div>
                    <div v-if="item.employee?.bank_account_name" class="text-emerald-200">
                        a.n {{ item.employee?.bank_account_name }}
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
