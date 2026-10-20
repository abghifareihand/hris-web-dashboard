<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const props = defineProps({
    loan: Object,
})

// Confirmation Modal State
const isConfirmModalOpen = ref(false)
const selectedInstallment = ref(null)
const selectedIndex = ref(0)
const isProcessing = ref(false)

const openConfirmModal = (installment, index) => {
    selectedInstallment.value = installment
    selectedIndex.value = index
    isConfirmModalOpen.value = true
}

const executeToggleInstallment = () => {
    if (!selectedInstallment.value) return

    isProcessing.value = true
    router.patch(
        route('owner.finance.loans.update-installment', {
            id: props.loan.id,
            installmentId: selectedInstallment.value.id,
        }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false
                isConfirmModalOpen.value = false
                selectedInstallment.value = null
            },
        }
    )
}

// Formatters
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// Financial Computations
const paidAmount = computed(() => {
    return props.loan?.installments
        ?.filter(i => i.status === 'paid')
        .reduce((sum, i) => sum + Number(i.amount), 0) || 0
})

const remainingAmount = computed(() => {
    return props.loan?.installments
        ?.filter(i => i.status !== 'paid')
        .reduce((sum, i) => sum + Number(i.amount), 0) || 0
})

const paidCount = computed(() => {
    return props.loan?.installments?.filter(i => i.status === 'paid').length || 0
})

const totalCount = computed(() => {
    return props.loan?.installments?.length || props.loan?.tenor || 0
})

const unpaidCount = computed(() => {
    return Math.max(0, totalCount.value - paidCount.value)
})

const progressPercentage = computed(() => {
    if (!totalCount.value) return 0
    return Math.round((paidCount.value / totalCount.value) * 100)
})

const monthlyInstallment = computed(() => {
    if (!props.loan?.tenor) return 0
    return Math.round(Number(props.loan?.amount || 0) / Number(props.loan.tenor))
})
</script>

<template>
    <AppLayout>
        <Head :title="`Detail Pinjaman ${loan.employee?.name || ''} - Frans HRIS`" />

        <div class="w-full space-y-6">
            <!-- Header Section with Standard Back Button -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3.5 sm:gap-4">
                    <Link
                        :href="route('owner.finance.loans.index')"
                        class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5 cursor-pointer"
                        title="Kembali ke Daftar Pinjaman"
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
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                Detail Pinjaman Karyawan
                            </h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200/90 font-mono font-bold text-xs tracking-wider">
                                #LOAN-{{ loan.id }}
                            </span>
                            <Badge v-if="loan.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="loan.status === 'approved'" variant="warning">Sedang Berjalan</Badge>
                            <Badge v-else-if="loan.status === 'pending'" variant="secondary">Menunggu Persetujuan</Badge>
                            <Badge v-else variant="danger">Ditolak</Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 flex flex-wrap items-center gap-2">
                            <span class="font-medium text-slate-700">{{ loan.employee?.name || '-' }}</span>
                            <span class="text-slate-300">•</span>
                            <span class="font-mono text-slate-600">{{ loan.employee?.nip || '-' }}</span>
                            <span class="text-slate-300">•</span>
                            <span>Tanggal Pinjaman: {{ formatDate(loan.date) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Financial KPI Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Pinjaman -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Pinjaman</div>
                        <div class="text-xl font-bold text-slate-900 mt-1 font-mono">
                            {{ formatCurrency(loan.amount) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Tenor {{ loan.tenor }} Bulan
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Sudah Dibayar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sudah Dibayar</div>
                        <div class="text-xl font-bold text-emerald-600 mt-1 font-mono">
                            {{ formatCurrency(paidAmount) }}
                        </div>
                        <div class="text-xs text-emerald-600 font-medium mt-0.5">
                            {{ paidCount }} dari {{ totalCount }} Cicilan Lunas
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Sisa Tagihan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sisa Tagihan</div>
                        <div class="text-xl font-bold text-rose-600 mt-1 font-mono">
                            {{ formatCurrency(remainingAmount) }}
                        </div>
                        <div class="text-xs text-rose-600 font-medium mt-0.5">
                            {{ unpaidCount }} Cicilan Belum Lunas
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Angsuran / Bulan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Cicilan per Bulan</div>
                        <div class="text-xl font-bold text-primary-700 mt-1 font-mono">
                            {{ formatCurrency(monthlyInstallment) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Progres {{ progressPercentage }}%
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Two Column Overview Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Data Karyawan -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Informasi Karyawan</h3>
                        <span class="text-xs font-medium text-slate-500">Peminjam</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100 text-primary-700 font-bold text-xl flex items-center justify-center border border-primary-200/60 shadow-xs shrink-0">
                            {{ loan.employee?.name?.charAt(0) || 'E' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-base font-bold text-slate-900 truncate">
                                {{ loan.employee?.name }}
                            </div>
                            <div class="text-xs text-slate-500 font-mono mt-0.5">
                                NIP: {{ loan.employee?.nip }}
                            </div>
                            <div class="text-xs text-slate-600 mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="font-medium text-slate-700">{{ loan.employee?.position?.name || '-' }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ loan.employee?.division?.name || '-' }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ loan.employee?.branch?.name || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                            <span class="text-slate-500 block">Gaji Pokok:</span>
                            <span class="font-bold text-slate-900 font-mono text-sm mt-0.5 block">
                                {{ formatCurrency(loan.employee?.basic_salary) }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                            <span class="text-slate-500 block">Dibuat Pada:</span>
                            <span class="font-medium text-slate-800 text-xs mt-1 block">
                                {{ formatDateTime(loan.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Finansial & Progres -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Keterangan & Progres Pembayaran</h3>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
                            {{ progressPercentage }}% Tercapai
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Status Pelunasan Tenor</span>
                            <span class="font-semibold text-slate-700">{{ paidCount }} dari {{ totalCount }} Lunas</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Keterangan / Keperluan -->
                    <div class="space-y-1.5 pt-2">
                        <span class="text-xs font-medium text-slate-500 block">Keperluan / Keterangan:</span>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 text-xs text-slate-700 leading-relaxed italic">
                            "{{ loan.description || 'Tidak ada catatan atau keterangan pinjaman.' }}"
                        </div>
                    </div>

                    <!-- Info Integrasi Payroll -->
                    <div class="p-3 rounded-xl bg-sky-50/70 border border-sky-200/80 flex items-start gap-2.5 text-xs text-sky-800">
                        <svg class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="leading-relaxed">
                            Cicilan akan otomatis terdeteksi & dipotong saat pembuatan <strong>Payroll</strong> sesuai bulan jatuh tempo, atau dapat diselesaikan secara manual lewat tabel di bawah.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Full Width Installment Schedule Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight">Jadwal Angsuran Cicilan Bulanan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Rincian tanggal jatuh tempo, nominal cicilan per tenor, dan kontrol status pembayaran manual.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 shrink-0">
                        <span class="w-2 h-2 rounded-full" :class="paidCount === totalCount ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        {{ paidCount }} / {{ totalCount }} Tenor Lunas
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-center w-24">Tenor</th>
                                <th class="px-6 py-3.5">Jatuh Tempo</th>
                                <th class="px-6 py-3.5 text-right">Nominal Cicilan</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                <th class="px-6 py-3.5 text-center">Waktu Update</th>
                                <th class="px-6 py-3.5 text-center w-44">Aksi / Switch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(inst, idx) in loan.installments"
                                :key="inst.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Tenor Number -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs font-mono">
                                        #{{ idx + 1 }}
                                    </span>
                                </td>

                                <!-- Jatuh Tempo -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-semibold text-slate-800">
                                            {{ formatDate(inst.due_date) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Nominal Cicilan -->
                                <td class="px-6 py-4 whitespace-nowrap text-right font-mono font-bold text-slate-900">
                                    {{ formatCurrency(inst.amount) }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <Badge v-if="inst.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else variant="danger">Belum Lunas</Badge>
                                </td>

                                <!-- Waktu Update -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs text-slate-500">
                                    {{ inst.updated_at ? formatDateTime(inst.updated_at) : '-' }}
                                </td>

                                <!-- Action Button -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <Button
                                        v-if="inst.status !== 'paid'"
                                        variant="primary"
                                        size="sm"
                                        class="shadow-xs cursor-pointer"
                                        @click="openConfirmModal(inst, idx + 1)"
                                    >
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Tandai Lunas
                                    </Button>

                                    <Button
                                        v-else
                                        variant="secondary"
                                        size="sm"
                                        class="text-amber-700 hover:text-amber-800 hover:bg-amber-50 border-amber-200 cursor-pointer"
                                        @click="openConfirmModal(inst, idx + 1)"
                                    >
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Ubah Status
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Confirmation Dialog Modal -->
        <Modal
            :show="isConfirmModalOpen"
            :title="selectedInstallment?.status === 'paid' ? 'Konfirmasi Ubah Status Cicilan' : 'Konfirmasi Tandai Cicilan Lunas'"
            maxWidth="md"
            @close="isConfirmModalOpen = false"
        >
            <div v-if="selectedInstallment" class="space-y-4">
                <!-- Informative Banner -->
                <div
                    class="p-4 rounded-xl flex items-start gap-3 border text-xs leading-relaxed"
                    :class="selectedInstallment.status === 'paid' ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-emerald-50 text-emerald-900 border-emerald-200'"
                >
                    <svg
                        v-if="selectedInstallment.status === 'paid'"
                        class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <template v-if="selectedInstallment.status === 'paid'">
                            Apakah Anda yakin ingin mengembalikan status <strong>Cicilan Ke-{{ selectedIndex }}</strong> menjadi <strong>BELUM LUNAS</strong>?
                            Sisa tagihan pinjaman karyawan akan disesuaikan kembali.
                        </template>
                        <template v-else>
                            Apakah Anda yakin ingin menandai <strong>Cicilan Ke-{{ selectedIndex }}</strong> sebagai <strong>LUNAS</strong>?
                            Status pinjaman utama akan otomatis diperbarui.
                        </template>
                    </div>
                </div>

                <!-- Installment Summary Card -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Karyawan:</span>
                        <span class="font-bold text-slate-900">{{ loan.employee?.name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Cicilan / Tenor:</span>
                        <span class="font-semibold text-slate-800">Bulan Ke-{{ selectedIndex }} dari {{ totalCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Jatuh Tempo:</span>
                        <span class="font-semibold text-slate-800">{{ formatDate(selectedInstallment.due_date) }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">Nominal Cicilan:</span>
                        <span class="font-bold text-slate-900 font-mono text-base">
                            {{ formatCurrency(selectedInstallment.amount) }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <Button
                        variant="secondary"
                        :disabled="isProcessing"
                        @click="isConfirmModalOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        :variant="selectedInstallment.status === 'paid' ? 'warning' : 'primary'"
                        :loading="isProcessing"
                        :disabled="isProcessing"
                        @click="executeToggleInstallment"
                    >
                        {{ selectedInstallment.status === 'paid' ? 'Ya, Kembalikan Belum Lunas' : 'Ya, Tandai Lunas' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
