<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'
import Input from '@/Components/UI/Input.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'

const props = defineProps({
    payroll: Object,
    unpaidLoanInstallments: Object,
})

// Search Filter in Table
const searchQuery = ref('')

const filteredItems = computed(() => {
    if (!props.payroll?.items) return []
    if (!searchQuery.value.trim()) return props.payroll.items

    const q = searchQuery.value.toLowerCase().trim()
    return props.payroll.items.filter(item => {
        const name = item.employee?.name?.toLowerCase() || ''
        const nip = item.employee?.nip?.toLowerCase() || ''
        const position = item.employee?.position?.name?.toLowerCase() || ''
        const division = item.employee?.division?.name?.toLowerCase() || ''
        return name.includes(q) || nip.includes(q) || position.includes(q) || division.includes(q)
    })
})

// Status Changer Modal
const isStatusModalOpen = ref(false)
const targetStatus = ref('')
const isUpdatingStatus = ref(false)

const openConfirmStatusModal = (status) => {
    targetStatus.value = status
    isStatusModalOpen.value = true
}

const executeStatusChange = () => {
    isUpdatingStatus.value = true
    router.patch(route('owner.finance.payrolls.update-status', props.payroll.id), {
        status: targetStatus.value,
    }, {
        onFinish: () => {
            isUpdatingStatus.value = false
            isStatusModalOpen.value = false
        }
    })
}

// Adjust Modal State
const showAdjustModal = ref(false)
const selectedItem = ref(null)
const isProrataAppliedInModal = ref(false)
const isLoanAppliedInModal = ref(false)

const adjustForm = useForm({
    items: {},
})

// Prorata Helper
const getProrataDetails = (item) => {
    if (!item?.employee?.joined_at || !props.payroll?.start_date || !props.payroll?.end_date) return null
    const joinDate = new Date(item.employee.joined_at)
    const startDate = new Date(props.payroll.start_date)
    const endDate = new Date(props.payroll.end_date)

    // Jika bergabung di dalam periode (setelah start_date dan sebelum/pada end_date)
    if (joinDate > startDate && joinDate <= endDate) {
        const totalDays = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1
        const activeDays = Math.round((endDate - joinDate) / (1000 * 60 * 60 * 24)) + 1
        const basicSalary = Number(item.basic_salary || 0)
        const proratedSalary = (basicSalary / totalDays) * activeDays
        const prorateDeduction = Math.max(0, Math.round(basicSalary - proratedSalary))

        return {
            isNewJoin: true,
            totalDays,
            activeDays,
            prorateAmount: prorateDeduction,
            joinDateRaw: item.employee.joined_at,
            joinDateFormatted: new Date(item.employee.joined_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
        }
    }
    return null
}

const isItemProrataApplied = (item) => {
    const prorata = getProrataDetails(item)
    if (!prorata || prorata.prorateAmount <= 0) return false
    if (item.remarks && item.remarks.toLowerCase().includes('prorata')) return true
    return Number(item.other_deductions || 0) >= prorata.prorateAmount
}

// Loan / Kasbon Helper
const getAvailableLoan = (item) => {
    if (item?.loan_installment) return item.loan_installment
    const unpaidList = props.unpaidLoanInstallments?.[item?.employee_id]
    if (unpaidList && unpaidList.length > 0) return unpaidList[0]
    return null
}

const isItemLoanApplied = (item) => {
    return Boolean(item?.loan_installment_id || item?.loan_installment)
}

const removeLoanRemarks = (text) => {
    if (!text) return ''
    return text
        .replace(/(\&|•)?\s*Potongan\s*Kasbon\s*(\/\s*Cicilan\s*Pinjaman)?[^\•\n]*/gi, '')
        .replace(/(\&|•)?\s*Cicilan\s*Pinjaman[^\•\n]*/gi, '')
        .replace(/(\&|•)?\s*Kasbon[^\•\n]*/gi, '')
        .replace(/^[\s•\&\-\/]+|[\s•\&\-\/]+$/g, '')
        .trim()
}

const removeProrataRemarks = (text) => {
    if (!text) return ''
    return text
        .replace(/(\&|•)?\s*Prorata\s*gaji\s*baru\s*masuk[^\•\n]*/gi, '')
        .replace(/(\&|•)?\s*Gaji\s*Prorata[^\•\n]*/gi, '')
        .replace(/(\&|•)?\s*Prorata[^\•\n]*/gi, '')
        .replace(/^[\s•\&\-\/]+|[\s•\&\-\/]+$/g, '')
        .trim()
}

const getExtraRemarks = (item) => {
    if (!item?.remarks) return null
    let clean = removeProrataRemarks(item.remarks)
    clean = removeLoanRemarks(clean)
    clean = clean
        .replace(/(\&|•)?\s*Potongan\s*denda\s*absensi[^\•\n]*/gi, '')
        .replace(/(\&|•)?\s*denda\s*absensi[^\•\n]*/gi, '')
        .replace(/^[\s•\&\-\/]+|[\s•\&\-\/]+$/g, '')
        .trim()
    return clean || null
}

const openAdjustModal = (item) => {
    selectedItem.value = item
    const isProrataOn = isItemProrataApplied(item)
    const isLoanOn = isItemLoanApplied(item)

    isProrataAppliedInModal.value = isProrataOn
    isLoanAppliedInModal.value = isLoanOn

    let remarksVal = item.remarks || ''
    // Jika kasbon belum diterapkan pada item ini, bersihkan catatan kasbon
    if (!isLoanOn && remarksVal) {
        remarksVal = removeLoanRemarks(remarksVal)
    }
    // Jika prorata belum diterapkan pada item ini, bersihkan catatan prorata
    if (!isProrataOn && remarksVal) {
        remarksVal = removeProrataRemarks(remarksVal)
    }

    adjustForm.items = {
        [item.id]: {
            bonus: item.bonus || 0,
            other_deductions: item.other_deductions || 0,
            remarks: remarksVal,
            loan_installment_id: item.loan_installment_id || '',
        }
    }
    showAdjustModal.value = true
}

const toggleProrataModal = (apply) => {
    if (!selectedItem.value) return
    const prorata = getProrataDetails(selectedItem.value)
    if (!prorata) return

    const currentItemForm = adjustForm.items[selectedItem.value.id]
    const amount = prorata.prorateAmount

    if (apply) {
        currentItemForm.other_deductions = Number(currentItemForm.other_deductions || 0) + amount
        if (!currentItemForm.remarks?.toLowerCase().includes('prorata')) {
            const prorataNote = `Prorata gaji baru masuk tgl ${prorata.joinDateFormatted} (${prorata.activeDays}/${prorata.totalDays} hr)`
            currentItemForm.remarks = currentItemForm.remarks ? `${currentItemForm.remarks} • ${prorataNote}` : prorataNote
        }
        isProrataAppliedInModal.value = true
    } else {
        currentItemForm.other_deductions = Math.max(0, Number(currentItemForm.other_deductions || 0) - amount)
        currentItemForm.remarks = removeProrataRemarks(currentItemForm.remarks)
        isProrataAppliedInModal.value = false
    }
}

const toggleLoanModal = (apply) => {
    if (!selectedItem.value) return
    const loan = getAvailableLoan(selectedItem.value)
    if (!loan) return

    const currentItemForm = adjustForm.items[selectedItem.value.id]
    const loanAmount = Number(loan.amount || 0)

    if (apply) {
        currentItemForm.loan_installment_id = loan.id
        currentItemForm.other_deductions = Number(currentItemForm.other_deductions || 0) + loanAmount
        if (!currentItemForm.remarks?.toLowerCase().includes('kasbon')) {
            const loanNote = 'Potongan Kasbon / Cicilan Pinjaman'
            currentItemForm.remarks = currentItemForm.remarks ? `${currentItemForm.remarks} • ${loanNote}` : loanNote
        }
        isLoanAppliedInModal.value = true
    } else {
        currentItemForm.loan_installment_id = ''
        currentItemForm.other_deductions = Math.max(0, Number(currentItemForm.other_deductions || 0) - loanAmount)
        currentItemForm.remarks = removeLoanRemarks(currentItemForm.remarks)
        isLoanAppliedInModal.value = false
    }
}

const estimatedNetSalary = computed(() => {
    if (!selectedItem.value || !adjustForm.items[selectedItem.value.id]) return 0
    const item = selectedItem.value
    const formVals = adjustForm.items[item.id]

    const bonus = Number(formVals.bonus || 0)
    const otherDeductions = Number(formVals.other_deductions || 0)

    const totalEarnings =
        Number(item.basic_salary || 0) +
        Number(item.fixed_allowance || 0) +
        Number(item.daily_allowance || 0) +
        Number(item.other_allowance || 0) +
        Number(item.overtime_amount || 0) +
        Number(item.reimbursement_amount || 0) +
        bonus

    const totalDeductions =
        Number(item.total_penalty || 0) +
        otherDeductions +
        Number(item.pph21_amount || 0) +
        Number(item.bpjs_kesehatan_employee || 0) +
        Number(item.jht_employee || 0) +
        Number(item.jp_employee || 0)

    return Math.max(0, totalEarnings - totalDeductions)
})

const submitAdjust = () => {
    adjustForm.patch(route('owner.finance.payrolls.update-items', props.payroll.id), {
        onSuccess: () => {
            showAdjustModal.value = false
            selectedItem.value = null
        }
    })
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}

const getAdditionalEarnings = (item) => {
    if (item.total_earnings !== undefined && item.total_earnings !== null && Number(item.total_earnings) > 0) {
        return Math.max(0, Number(item.total_earnings) - Number(item.basic_salary || 0))
    }
    return (
        Number(item.fixed_allowance || 0) +
        Number(item.daily_allowance || 0) +
        Number(item.other_allowance || 0) +
        Number(item.overtime_amount || 0) +
        Number(item.bonus || 0) +
        Number(item.reimbursement_amount || 0)
    )
}

const getTotalDeductions = (item) => {
    if (item.total_deductions !== undefined && item.total_deductions !== null && Number(item.total_deductions) > 0) {
        return Number(item.total_deductions)
    }
    return (
        Number(item.total_penalty || 0) +
        Number(item.other_deductions || 0) +
        Number(item.pph21_amount || 0) +
        Number(item.bpjs_kesehatan_employee || 0) +
        Number(item.jht_employee || 0) +
        Number(item.jp_employee || 0)
    )
}

const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
    return (parts[0][0] + parts[1][0]).toUpperCase()
}
</script>

<template>
    <AppLayout>
        <Head :title="`Detail Penggajian ${payroll.code} - Frans HRIS`" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-start gap-3.5 sm:gap-4">
                    <Link
                        :href="route('owner.finance.payrolls.index')"
                        class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                        title="Kembali ke Daftar Penggajian"
                    >
                        <svg
                            class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Detail Penggajian</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200/90 font-mono font-bold text-xs tracking-wider">
                                {{ payroll.code }}
                            </span>
                            <Badge v-if="payroll.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="payroll.status === 'process'" variant="warning">Dalam Proses</Badge>
                            <Badge v-else variant="danger">Dibatalkan</Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                            <span class="font-medium text-slate-700">{{ formatDate(payroll.start_date) }} – {{ formatDate(payroll.end_date) }}</span>
                            <span class="text-slate-300">•</span>
                            <span>{{ payroll.branch?.name || 'Semua Cabang' }}</span>
                            <span class="text-slate-300">•</span>
                            <span>{{ payroll.division?.name || 'Semua Divisi' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi di Atas -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <a :href="`/owner/finance/payrolls/${payroll.id}/export-bank-excel`" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Bank Transfer
                        </Button>
                    </a>

                    <a :href="route('owner.finance.payrolls.slips', payroll.id)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Semua Slip
                        </Button>
                    </a>

                    <!-- Tombol Perubahan Status -->
                    <Button
                        v-if="payroll.status === 'process'"
                        variant="primary"
                        size="md"
                        @click="openConfirmStatusModal('paid')"
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
                        @click="openConfirmStatusModal('process')"
                    >
                        <svg class="w-4 h-4 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Revisi (Kembalikan ke Draft)
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Gaji Bersih (Net)</div>
                        <div class="text-xl font-bold text-emerald-600 truncate mt-0.5">{{ formatCurrency(payroll.total_amount) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pendapatan Kotor</div>
                        <div class="text-xl font-bold text-slate-900 truncate mt-0.5">
                            {{ formatCurrency(payroll.items?.reduce((acc, curr) => acc + Number(curr.total_earnings || 0), 0)) }}
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Potongan</div>
                        <div class="text-xl font-bold text-rose-600 truncate mt-0.5">
                            {{ formatCurrency(payroll.items?.reduce((acc, curr) => acc + Number(curr.total_deductions || 0), 0)) }}
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jumlah Penerima</div>
                        <div class="text-xl font-bold text-indigo-700 truncate mt-0.5">{{ payroll.total_employees }} Karyawan</div>
                    </div>
                </div>
            </div>

            <!-- Table Card: Rincian Slip Gaji Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Header Card & Search Filter -->
                <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Rincian Slip Gaji Karyawan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar kalkulasi nominal gaji seluruh karyawan pada periode ini.</p>
                    </div>

                    <div class="w-full md:w-72">
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari karyawan, NIP, jabatan..."
                            clearable
                            size="sm"
                        />
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    :headers="[
                        'Karyawan',
                        'Gaji Pokok',
                        'Pendapatan',
                        'Potongan',
                        'Gaji Bersih',
                        '',
                    ]"
                >
                    <template v-if="filteredItems && filteredItems.length > 0">
                        <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-slate-50/70 transition">
                            <!-- Karyawan -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900 text-sm">{{ item.employee?.name || '-' }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">
                                    {{ item.employee?.nip || '-' }} • {{ item.employee?.position?.name || '-' }}
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <!-- Badge Prorata -->
                                    <template v-if="getProrataDetails(item)">
                                        <span
                                            v-if="isItemProrataApplied(item)"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"
                                        >
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Potongan Prorata Diterapkan</span>
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200"
                                        >
                                            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Potongan Prorata Belum Diterapkan</span>
                                        </span>
                                    </template>

                                    <!-- Badge Kasbon -->
                                    <template v-if="getAvailableLoan(item)">
                                        <span
                                            v-if="isItemLoanApplied(item)"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-200"
                                        >
                                            <svg class="w-3 h-3 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Potongan Kasbon Diterapkan</span>
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200"
                                        >
                                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Potongan Kasbon Belum Diterapkan</span>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Gaji Pokok -->
                            <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-800 text-sm">
                                {{ formatCurrency(item.basic_salary) }}
                            </td>

                            <!-- Pendapatan (Selain Gaji Pokok) -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <span v-if="getAdditionalEarnings(item) > 0" class="font-semibold text-emerald-600">
                                    + {{ formatCurrency(getAdditionalEarnings(item)) }}
                                </span>
                                <span v-else class="text-slate-400">
                                    Rp 0
                                </span>
                            </td>

                            <!-- Potongan -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <span v-if="getTotalDeductions(item) > 0" class="font-semibold text-rose-600">
                                    - {{ formatCurrency(getTotalDeductions(item)) }}
                                </span>
                                <span v-else class="text-slate-400">
                                    Rp 0
                                </span>
                            </td>

                            <!-- Gaji Bersih -->
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900 text-sm">
                                {{ formatCurrency(item.net_salary) }}
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-1">
                                    <!-- Button Adjust (jika belum paid) -->
                                    <button
                                        v-if="payroll.status !== 'paid'"
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition cursor-pointer"
                                        title="Penyesuaian Bonus / Potongan"
                                        @click="openAdjustModal(item)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Button Detail -->
                                    <Link
                                        :href="route('owner.finance.payrolls.items.show', { id: payroll.id, itemId: item.id })"
                                        class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition cursor-pointer"
                                        title="Rincian Slip Gaji Karyawan"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>

                                    <!-- Button Cetak Slip -->
                                    <a
                                        :href="route('owner.finance.payrolls.slip-single', { id: payroll.id, itemId: item.id })"
                                        target="_blank"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition cursor-pointer"
                                        title="Cetak Slip Gaji (PDF)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <TableEmpty
                        v-else
                        :colspan="6"
                        title="Tidak Ada Karyawan"
                        :message="searchQuery ? 'Tidak ada data karyawan yang cocok dengan pencarian.' : 'Belum ada rincian data karyawan pada penggajian ini.'"
                    />
                </DataTable>
            </div>
        </div>

        <!-- Modal Konfirmasi Perubahan Status -->
        <Modal
            :show="isStatusModalOpen"
            :title="targetStatus === 'paid' ? 'Konfirmasi Tandai Selesai (PAID)' : 'Konfirmasi Kembalikan ke Draft'"
            maxWidth="md"
            @close="isStatusModalOpen = false"
        >
            <div class="space-y-4">
                <div class="p-3.5 rounded-xl flex items-start gap-3" :class="targetStatus === 'paid' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200'">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-xs leading-relaxed">
                        <template v-if="targetStatus === 'paid'">
                            Apakah Anda yakin ingin menyelesaikan periode penggajian ini dan mengubah status menjadi <strong>PAID</strong>?
                            Sistem akan mencatat waktu pembayaran dan menyelesaikan tagihan cicilan kasbon yang terkait.
                        </template>
                        <template v-else>
                            Apakah Anda yakin ingin mengembalikan status penggajian ini ke <strong>DRAFT / DALAM PROSES</strong>?
                            Status cicilan kasbon dan klaim reimbursement yang terkait akan dikembalikan ke status sebelumnya.
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <Button variant="secondary" :disabled="isUpdatingStatus" @click="isStatusModalOpen = false">
                        Batal
                    </Button>
                    <Button
                        :variant="targetStatus === 'paid' ? 'primary' : 'warning'"
                        :loading="isUpdatingStatus"
                        :disabled="isUpdatingStatus"
                        @click="executeStatusChange"
                    >
                        {{ targetStatus === 'paid' ? 'Ya, Tandai Selesai' : 'Ya, Kembalikan ke Draft' }}
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Adjust Modal (Penyesuaian Bonus / Potongan) -->
        <Modal
            :show="showAdjustModal"
            title="Penyesuaian Tambahan / Potongan Gaji"
            maxWidth="md"
            @close="showAdjustModal = false"
        >
            <div v-if="selectedItem && adjustForm.items[selectedItem.id]" class="space-y-4">
                <!-- Info Karyawan Header Card -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Karyawan</div>
                        <div class="text-sm font-bold text-slate-900 mt-0.5">
                            {{ selectedItem.employee?.name }}
                            <span v-if="getProrataDetails(selectedItem)" class="text-xs font-normal text-slate-600">
                                (Masuk {{ getProrataDetails(selectedItem).joinDateFormatted }})
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <!-- Status Badge Prorata -->
                        <span
                            v-if="getProrataDetails(selectedItem)"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold"
                            :class="isProrataAppliedInModal ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                        >
                            {{ isProrataAppliedInModal ? '✓ Potongan Prorata Diterapkan' : '⚠ Potongan Prorata Belum Diterapkan' }}
                        </span>
                        <!-- Status Badge Kasbon -->
                        <span
                            v-if="getAvailableLoan(selectedItem)"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold"
                            :class="isLoanAppliedInModal ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                        >
                            {{ isLoanAppliedInModal ? '✓ Potongan Kasbon Diterapkan' : '⚠ Potongan Kasbon Belum Diterapkan' }}
                        </span>
                    </div>
                </div>

                <!-- Banner Prorata Interaktif -->
                <div v-if="getProrataDetails(selectedItem)">
                    <!-- Saat Prorata Diterapkan -->
                    <div
                        v-if="isProrataAppliedInModal"
                        class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200/90 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-emerald-950">Potongan Prorata Diterapkan ke Gaji</h4>
                            </div>
                            <div class="text-base font-bold text-emerald-700 whitespace-nowrap">
                                {{ formatCurrency(getProrataDetails(selectedItem).prorateAmount) }}
                            </div>
                        </div>
                        <p class="text-xs text-emerald-800 leading-relaxed">
                            Potongan prorata hari kerja tgl {{ getProrataDetails(selectedItem).joinDateFormatted }} telah diterapkan ke potongan gaji karyawan.
                        </p>
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="toggleProrataModal(false)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Batalkan Potongan Prorata</span>
                            </button>
                        </div>
                    </div>

                    <!-- Saat Prorata Belum Diterapkan -->
                    <div
                        v-else
                        class="p-4 rounded-xl bg-rose-50/80 border border-rose-200/90 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-rose-950">Pengingat Prorata: Karyawan Baru Bergabung</h4>
                            </div>
                            <div class="text-base font-bold text-rose-600 whitespace-nowrap">
                                {{ formatCurrency(getProrataDetails(selectedItem).prorateAmount) }}
                            </div>
                        </div>
                        <p class="text-xs text-rose-800 leading-relaxed">
                            Karyawan baru bergabung tgl {{ getProrataDetails(selectedItem).joinDateFormatted }} (bekerja {{ getProrataDetails(selectedItem).activeDays }} dari total {{ getProrataDetails(selectedItem).totalDays }} hari periode). Anda bebas memutuskan: potong langsung dari gaji sekarang atau biarkan karyawan bayar secara mandiri / di luar gaji.
                        </p>
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="toggleProrataModal(true)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Terapkan Potongan Prorata (+ {{ formatCurrency(getProrataDetails(selectedItem).prorateAmount) }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Banner Kasbon Interaktif (Jika Ada Kasbon) -->
                <div v-if="getAvailableLoan(selectedItem)">
                    <!-- Saat Kasbon Diterapkan -->
                    <div
                        v-if="isLoanAppliedInModal"
                        class="p-4 rounded-xl bg-sky-50/80 border border-sky-200/90 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-sky-950">Potongan Kasbon Diterapkan ke Gaji</h4>
                            </div>
                            <div class="text-base font-bold text-sky-700 whitespace-nowrap">
                                {{ formatCurrency(getAvailableLoan(selectedItem).amount) }}
                            </div>
                        </div>
                        <p class="text-xs text-sky-800 leading-relaxed">
                            Potongan cicilan pinjaman kasbon jatuh tempo tgl {{ formatDate(getAvailableLoan(selectedItem).due_date) }} telah dihubungkan ke penggajian ini.
                        </p>
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="toggleLoanModal(false)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-700 font-semibold text-xs shadow-2xs transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Batalkan Potongan Kasbon</span>
                            </button>
                        </div>
                    </div>

                    <!-- Saat Kasbon Belum Diterapkan -->
                    <div
                        v-else
                        class="p-4 rounded-xl bg-amber-50/80 border border-amber-200/90 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-amber-950">Pengingat Kasbon: Ada Tagihan Jatuh Tempo</h4>
                            </div>
                            <div class="text-base font-bold text-amber-600 whitespace-nowrap">
                                {{ formatCurrency(getAvailableLoan(selectedItem).amount) }}
                            </div>
                        </div>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            Karyawan memiliki tagihan cicilan kasbon yang jatuh tempo pada tgl {{ formatDate(getAvailableLoan(selectedItem).due_date) }}. Anda dapat memotong langsung dari gaji periode ini.
                        </p>
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="toggleLoanModal(true)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Terapkan Potongan Kasbon (+ {{ formatCurrency(getAvailableLoan(selectedItem).amount) }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Inputs -->
                <div class="space-y-3.5">
                    <Input
                        label="Bonus (Opsional)"
                        v-model="adjustForm.items[selectedItem.id].bonus"
                        currency
                        placeholder="0"
                    />

                    <Input
                        label="Potongan Lainnya (Opsional)"
                        v-model="adjustForm.items[selectedItem.id].other_deductions"
                        currency
                        placeholder="0"
                    />

                    <Input
                        label="Keterangan / Catatan (Opsional)"
                        v-model="adjustForm.items[selectedItem.id].remarks"
                        placeholder="Contoh: Prorata gaji karyawan baru / bonus project"
                    />
                </div>

                <!-- Estimasi Gaji Bersih Real-time Card (Sesuai Referensi Gambar) -->
                <div class="p-4 rounded-xl bg-sky-50/70 border border-sky-200/80 flex items-center justify-between gap-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-800">
                        ESTIMASI GAJI BERSIH (TAKE HOME PAY)
                    </span>
                    <span class="text-lg font-bold text-sky-700 font-mono">
                        {{ formatCurrency(estimatedNetSalary) }}
                    </span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <Button variant="secondary" @click="showAdjustModal = false">Batal</Button>
                    <Button variant="primary" :disabled="adjustForm.processing" @click="submitAdjust">
                        {{ adjustForm.processing ? 'Menyimpan...' : 'Simpan Penyesuaian' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
