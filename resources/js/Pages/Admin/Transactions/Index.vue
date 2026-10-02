<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';

const props = defineProps({
    transactions: Array,
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'paid':
            return { label: 'Lunas', variant: 'success' };
        case 'pending':
            return { label: 'Menunggu Pembayaran', variant: 'warning' };
        default:
            return { label: 'Gagal / Dibatalkan', variant: 'danger' };
    }
};
</script>

<template>
    <Head title="Transaksi & Tagihan SaaS" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Transaksi & Tagihan Klien</h1>
                    <p class="text-sm text-slate-500 mt-1">Pantau seluruh invoice langganan, metode pembayaran gateway, dan status penerimaan dana.</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Invoice Lunas</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ transactions.filter(t => t.status === 'paid').length }}
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Menunggu Pembayaran</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ transactions.filter(t => t.status === 'pending').length }}
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Volume</p>
                        <p class="text-xl font-black text-slate-900 mt-0.5">
                            {{ formatRupiah(transactions.reduce((acc, curr) => acc + curr.amount, 0)) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Table Card Header -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Riwayat Tagihan & Pembayaran</h2>
                    <p class="text-xs text-slate-500">Daftar transaksi langganan bulanan / tahunan perusahaan klien</p>
                </div>
            </div>

            <!-- Data Table -->
            <DataTable :headers="['No. Invoice', 'Perusahaan Klien', 'Paket Langganan', 'Nominal', 'Metode Bayar', 'Status', { label: 'Tanggal', class: 'text-right' }]">
                <template v-if="transactions && transactions.length > 0">
                    <tr v-for="item in transactions" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap font-mono font-bold text-slate-900 text-xs">
                            #{{ item.id }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="font-bold text-slate-900">{{ item.company_name }}</p>
                            <p class="text-xs text-slate-400">{{ item.company_email }}</p>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ item.plan_name }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-extrabold text-slate-900">
                            {{ formatRupiah(item.amount) }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-600">
                            {{ item.payment_method }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <Badge :variant="getStatusBadge(item.status).variant" size="sm">
                                {{ getStatusBadge(item.status).label }}
                            </Badge>
                        </td>
                        <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">
                            <p>{{ item.paid_at }}</p>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <TableEmpty
                        title="Belum ada transaksi"
                        message="Belum ada transaksi invoice tagihan yang diterbitkan."
                        :colspan="7"
                    />
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
