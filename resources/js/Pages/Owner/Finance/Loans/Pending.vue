<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    pendingLoans: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const handleSearch = () => {
    router.get(route('owner.finance.loans.pending.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

const handleApprove = (id) => {
    if (confirm('Setujui pengajuan pinjaman/kasbon ini dan buat jadwal cicilannya?')) {
        router.post(route('owner.finance.loans.pending.approve', id))
    }
}

const handleReject = (id) => {
    if (confirm('Tolak pengajuan pinjaman/kasbon ini?')) {
        router.post(route('owner.finance.loans.pending.reject', id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Persetujuan Pinjaman (Kasbon) - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Persetujuan Pinjaman / Kasbon</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Daftar pengajuan pinjaman dana karyawan yang menunggu verifikasi manajemen.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.loans.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Riwayat Pinjaman
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Search -->
            <div class="bg-white p-4 rounded-xl border border-secondary-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-80">
                    <Input
                        v-model="search"
                        placeholder="Cari nama karyawan / NIP..."
                        @keyup.enter="handleSearch"
                    >
                        <template #prefix>
                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </template>
                    </Input>
                </div>
                <div class="text-sm text-secondary-600">
                    Menampilkan <span class="font-bold text-secondary-900">{{ pendingLoans.total }}</span> pengajuan pending
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3 text-right">Nominal Pinjaman</th>
                                <th class="px-5 py-3 text-center">Tenor</th>
                                <th class="px-5 py-3 text-right">Cicilan / Bln</th>
                                <th class="px-5 py-3 text-center">Rasio Gaji Pokok</th>
                                <th class="px-5 py-3">Keperluan</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="loan in pendingLoans.data" :key="loan.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-secondary-900">
                                    {{ loan.date }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ loan.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ loan.employee?.nip || '-' }} • {{ loan.employee?.division?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-secondary-900">
                                    {{ formatCurrency(loan.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-secondary-100 text-secondary-800">
                                        {{ loan.tenor }} Bulan
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-medium text-primary-700">
                                    {{ formatCurrency(loan.amount / loan.tenor) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span
                                        v-if="loan.employee?.basic_salary"
                                        class="text-xs font-bold px-2 py-1 rounded-lg"
                                        :class="((loan.amount / loan.tenor) / loan.employee.basic_salary) > 0.35 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                                    >
                                        {{ (((loan.amount / loan.tenor) / loan.employee.basic_salary) * 100).toFixed(1) }}%
                                    </span>
                                    <span v-else class="text-xs text-secondary-400">-</span>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="text-secondary-800 line-clamp-1">{{ loan.description || '-' }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            @click="handleApprove(loan.id)"
                                        >
                                            Setujui
                                        </Button>
                                        <Button
                                            variant="danger"
                                            size="sm"
                                            @click="handleReject(loan.id)"
                                        >
                                            Tolak
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pendingLoans.data.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-secondary-500">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <p class="font-medium text-secondary-700">Tidak ada pengajuan pinjaman pending</p>
                                        <p class="text-xs text-secondary-400 mt-1">Semua kasbon karyawan telah diproses.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pendingLoans.links && pendingLoans.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="pendingLoans.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
