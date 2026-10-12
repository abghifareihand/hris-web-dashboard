<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    thrs: Object,
})

const handleDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data perhitungan THR ini?')) {
        router.delete(route('owner.finance.thr.destroy', id))
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Tunjangan Hari Raya (THR) - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tunjangan Hari Raya (THR)</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kalkulasi, pembagian, dan pencetakan slip THR karyawan sesuai masa kerja.
                    </p>
                </div>
                <div class="flex items-center gap-3">
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

            <!-- Standard Data Table Component -->
            <DataTable
                :headers="[
                    'Kode THR',
                    'Hari Raya',
                    'Tahun',
                    'Tgl Pembayaran',
                    'Cakupan Cabang',
                    'Karyawan',
                    'Nominal THR',
                    'Status',
                    '',
                ]"
            >
                <template v-if="thrs.data && thrs.data.length > 0">
                    <tr v-for="item in thrs.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                        <!-- Kode THR -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-mono font-bold text-xs tracking-wider">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ item.code }}
                            </span>
                        </td>

                        <!-- Hari Raya -->
                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-slate-900">
                            {{ item.holiday_name }}
                        </td>

                        <!-- Tahun -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ item.year }}
                            </span>
                        </td>

                        <!-- Tgl Pembayaran (Formatted) -->
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-700">
                            {{ formatDate(item.payment_date) }}
                        </td>

                        <!-- Cakupan Cabang -->
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-700">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ item.branch ? item.branch.name : 'Semua Cabang' }}
                            </span>
                        </td>

                        <!-- Jml Karyawan -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ item.total_employees }} karyawan
                            </span>
                        </td>

                        <!-- Nominal THR -->
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-emerald-600 text-sm">
                            {{ formatCurrency(item.total_amount) }}
                        </td>

                        <!-- Status -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <Badge v-if="item.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="item.status === 'process'" variant="warning">Draft / Siap Bayar</Badge>
                            <Badge v-else variant="secondary">{{ item.status }}</Badge>
                        </td>

                        <!-- Aksi -->
                        <td class="px-5 py-3.5 whitespace-nowrap text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <Link
                                    :href="route('owner.finance.thr.show', item.id)"
                                    class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                    title="Lihat Detail THR"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </Link>
                                <button
                                    v-if="item.status === 'process'"
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
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
                </template>
                <template v-else>
                    <TableEmpty
                        title="Belum ada data perhitungan THR"
                        message="Belum ada data THR yang dibuat. Klik 'Hitung THR Baru' untuk memulai."
                        :colspan="9"
                    />
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="thrs" />
        </div>
    </AppLayout>
</template>
