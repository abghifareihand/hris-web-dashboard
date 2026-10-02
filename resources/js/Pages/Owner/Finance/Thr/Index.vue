<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    thrs: Object,
})

const handleDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data perhitungan THR ini?')) {
        router.delete(route('owner.finance.thr.destroy', id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Tunjangan Hari Raya (THR) - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Tunjangan Hari Raya (THR)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Kalkulasi, pembagian, dan pencetakan slip THR karyawan sesuai masa kerja.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.thr.settings.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kebijakan THR
                        </Button>
                    </Link>
                    <Link :href="route('owner.finance.thr.create')">
                        <Button variant="primary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Hitung THR Baru
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Kode THR</th>
                                <th class="px-5 py-3">Hari Raya</th>
                                <th class="px-5 py-3">Tahun</th>
                                <th class="px-5 py-3">Tgl Pembayaran</th>
                                <th class="px-5 py-3">Cakupan Cabang</th>
                                <th class="px-5 py-3 text-center">Jml Karyawan</th>
                                <th class="px-5 py-3 text-right">Total Nominal</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="item in thrs.data" :key="item.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap font-mono font-bold text-primary-700">
                                    {{ item.code }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-semibold text-secondary-900">
                                    {{ item.holiday_name }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-secondary-800">
                                    {{ item.year }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-secondary-700">
                                    {{ item.payment_date }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-secondary-700">
                                    {{ item.branch ? item.branch.name : 'Semua Cabang' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center font-bold text-secondary-800">
                                    {{ item.total_employees }} org
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-emerald-600 text-base">
                                    {{ formatCurrency(item.total_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Badge v-if="item.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else-if="item.status === 'process'" variant="warning">Draft / Siap Bayar</Badge>
                                    <Badge v-else variant="secondary">{{ item.status }}</Badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('owner.finance.thr.show', item.id)">
                                            <Button variant="secondary" size="sm">Detail</Button>
                                        </Link>
                                        <button
                                            v-if="item.status === 'process'"
                                            class="text-secondary-400 hover:text-rose-600 transition p-1"
                                            title="Hapus Draft THR"
                                            @click="handleDelete(item.id)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="thrs.data.length === 0">
                                <td colspan="9" class="px-5 py-12 text-center text-secondary-500">
                                    Belum ada data perhitungan THR yang dibuat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="thrs.links && thrs.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="thrs.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
