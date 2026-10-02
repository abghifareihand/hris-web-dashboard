<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'

const props = defineProps({
    loan: Object,
})

const toggleInstallment = (installmentId) => {
    router.patch(route('owner.finance.loans.update-installment', {
        id: props.loan.id,
        installmentId: installmentId,
    }), {}, { preserveScroll: true })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head :title="`Detail Pinjaman ${loan.employee?.name || ''} - Frans HRIS`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Detail Pinjaman Karyawan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Informasi pinjaman dan jadwal angsuran bulanan.
                    </p>
                </div>
                <Link :href="route('owner.finance.loans.index')">
                    <Button variant="secondary" size="md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </Button>
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Employee Info -->
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm space-y-3">
                    <h3 class="text-xs font-bold text-secondary-500 uppercase tracking-wider">Data Karyawan</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 font-bold text-lg flex items-center justify-center">
                            {{ loan.employee?.name?.charAt(0) || 'E' }}
                        </div>
                        <div>
                            <div class="text-base font-bold text-secondary-900">{{ loan.employee?.name }}</div>
                            <div class="text-xs text-secondary-500 font-mono">{{ loan.employee?.nip }} • {{ loan.employee?.division?.name || '-' }}</div>
                            <div class="text-xs text-secondary-600 mt-0.5">{{ loan.employee?.position?.name || '-' }} ({{ loan.employee?.branch?.name || '-' }})</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-secondary-100 flex justify-between text-sm">
                        <span class="text-secondary-500">Gaji Pokok:</span>
                        <span class="font-semibold text-secondary-900">{{ formatCurrency(loan.employee?.basic_salary) }}</span>
                    </div>
                </div>

                <!-- Loan Overview -->
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xs font-bold text-secondary-500 uppercase tracking-wider">Status Pinjaman</h3>
                        <Badge v-if="loan.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                        <Badge v-else-if="loan.status === 'approved'" variant="warning">Berjalan</Badge>
                        <Badge v-else variant="secondary">{{ loan.status }}</Badge>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs text-secondary-500">Total Pinjaman:</div>
                            <div class="text-xl font-bold text-secondary-900 mt-0.5">{{ formatCurrency(loan.amount) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-secondary-500">Tenor:</div>
                            <div class="text-xl font-bold text-primary-700 mt-0.5">{{ loan.tenor }} Bulan</div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-secondary-100 flex justify-between text-sm">
                        <span class="text-secondary-500">Cicilan per Bulan:</span>
                        <span class="font-bold text-emerald-600">{{ formatCurrency(loan.amount / loan.tenor) }}</span>
                    </div>
                </div>
            </div>

            <!-- Installment Schedule Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-secondary-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-secondary-900">Jadwal Angsuran Cicilan Bulanan</h3>
                        <p class="text-xs text-secondary-500 mt-0.5">Cicilan otomatis terpotong saat proses penggajian (Payroll) atau dapat ditandai manual.</p>
                    </div>
                    <div class="text-xs font-medium text-secondary-600">
                        {{ loan.installments?.filter(i => i.status === 'paid').length }}/{{ loan.installments?.length }} Lunas
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-center">Bulan Ke-</th>
                                <th class="px-5 py-3">Jatuh Tempo</th>
                                <th class="px-5 py-3 text-right">Nominal Cicilan</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Aksi / Switch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="(inst, idx) in loan.installments" :key="inst.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap text-center font-bold text-secondary-700">
                                    #{{ idx + 1 }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-medium text-secondary-900">
                                    {{ inst.due_date }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-secondary-900">
                                    {{ formatCurrency(inst.amount) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <Badge v-if="inst.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else variant="danger">Belum Lunas</Badge>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <Button
                                        :variant="inst.status === 'paid' ? 'secondary' : 'primary'"
                                        size="sm"
                                        @click="toggleInstallment(inst.id)"
                                    >
                                        {{ inst.status === 'paid' ? 'Tandai Belum' : 'Tandai Lunas' }}
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
