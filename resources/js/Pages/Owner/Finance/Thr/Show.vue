<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'

const props = defineProps({
    thr: Object,
})

const handlePay = () => {
    if (confirm('Tandai perhitungan THR ini sebagai PAID / Sudah Dibayarkan kepada seluruh karyawan?')) {
        router.post(route('owner.finance.thr.pay', props.thr.id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head :title="`Detail THR ${thr.code} - Frans HRIS`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Rincian Pembagian THR: {{ thr.holiday_name }}</h1>
                        <Badge v-if="thr.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                        <Badge v-else variant="warning">Draft / Siap Bayar</Badge>
                    </div>
                    <p class="text-sm text-secondary-500 mt-1">
                        Kode: <span class="font-mono font-bold text-secondary-700">{{ thr.code }}</span> • Tanggal Bayar: {{ thr.payment_date }} • Cakupan: {{ thr.branch?.name || 'Semua Cabang' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.thr.index')">
                        <Button variant="secondary" size="md">Kembali</Button>
                    </Link>
                    <a :href="route('owner.finance.thr.slips', thr.id)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Semua Slip
                        </Button>
                    </a>
                    <Button
                        v-if="thr.status === 'process'"
                        variant="primary"
                        size="md"
                        @click="handlePay"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Tandai Selesai / Lunas (Paid)
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Total Alokasi Dana THR</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1">{{ formatCurrency(thr.total_amount) }}</div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Jumlah Penerima</div>
                    <div class="text-2xl font-bold text-secondary-900 mt-1">{{ thr.total_employees }} Karyawan</div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm">
                    <div class="text-xs text-secondary-500 uppercase tracking-wider font-semibold">Rata-rata per Karyawan</div>
                    <div class="text-2xl font-bold text-primary-700 mt-1">
                        {{ formatCurrency(thr.total_employees > 0 ? thr.total_amount / thr.total_employees : 0) }}
                    </div>
                </div>
            </div>

            <!-- Items Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-secondary-200 flex items-center justify-between">
                    <h3 class="font-bold text-secondary-900">Daftar Rincian THR per Karyawan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3 text-center">Masa Kerja</th>
                                <th class="px-5 py-3 text-right">Basis Gaji</th>
                                <th class="px-5 py-3 text-center">Pengali Prorata</th>
                                <th class="px-5 py-3 text-right">Kotor (Gross)</th>
                                <th class="px-5 py-3 text-right">Pajak</th>
                                <th class="px-5 py-3 text-right font-bold text-secondary-900">THR Diterima (Net)</th>
                                <th class="px-5 py-3 text-center">Slip</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="item in thr.items" :key="item.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ item.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ item.employee?.nip || '-' }} • {{ item.employee?.division?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center font-medium text-secondary-800">
                                    {{ item.tenure_months }} bln
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-900">
                                    {{ formatCurrency(item.basis_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="item.prorate_multiplier >= 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                    >
                                        {{ (item.prorate_multiplier * 100).toFixed(0) }}%
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800">
                                    {{ formatCurrency(item.thr_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-500">
                                    {{ formatCurrency(item.tax_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-emerald-600 text-base">
                                    {{ formatCurrency(item.net_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <a :href="route('owner.finance.thr.slip-single', { id: thr.id, itemId: item.id })" target="_blank">
                                        <Button variant="secondary" size="sm">Cetak</Button>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
