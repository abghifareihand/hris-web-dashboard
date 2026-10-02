<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    reimbursements: Object,
    branches: Array,
    divisions: Array,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const branchId = ref(props.filters?.branch_id || '')
const divisionId = ref(props.filters?.division_id || '')
const status = ref(props.filters?.status || '')
const payoutMethod = ref(props.filters?.payout_method || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')

const statusOptions = [
    { value: '', label: 'Semua Status', dotClass: 'bg-slate-300' },
    { value: 'approved', label: 'Disetujui (Approved)', dotClass: 'bg-emerald-500' },
    { value: 'paid', label: 'Terbayar (Paid)', dotClass: 'bg-blue-500' },
    { value: 'rejected', label: 'Ditolak (Rejected)', dotClass: 'bg-rose-500' },
]

const applyFilters = () => {
    router.get(route('owner.finance.reimbursements.index'), {
        search: search.value || undefined,
        branch_id: branchId.value || undefined,
        division_id: divisionId.value || undefined,
        status: status.value || undefined,
        payout_method: payoutMethod.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    search.value = ''
    branchId.value = ''
    divisionId.value = ''
    status.value = ''
    payoutMethod.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

const handleDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data klaim biaya ini?')) {
        router.delete(route('owner.finance.reimbursements.destroy', id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Klaim Biaya - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Manajemen Klaim Biaya (Reimbursement)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Kelola dan pantau seluruh riwayat penggantian biaya operasional karyawan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.reimbursements.pending.index')">
                        <Button variant="secondary" size="md" class="relative">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Klaim Pending
                            <span v-if="stats.pending_count > 0" class="ml-2 px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                                {{ stats.pending_count }}
                            </span>
                        </Button>
                    </Link>
                    <Link :href="route('owner.finance.reimbursements.create')">
                        <Button variant="primary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Klaim Baru
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Menunggu Approval</div>
                        <div class="text-2xl font-bold text-secondary-900 mt-0.5">{{ stats.pending_count }} <span class="text-xs font-normal text-secondary-500">klaim</span></div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Disetujui (Payroll)</div>
                        <div class="text-xl font-bold text-primary-700 mt-0.5">{{ formatCurrency(stats.total_approved) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Terbayar (Lunas)</div>
                        <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ formatCurrency(stats.total_paid) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Ditolak</div>
                        <div class="text-2xl font-bold text-rose-600 mt-0.5">{{ stats.total_rejected }} <span class="text-xs font-normal text-secondary-500">klaim</span></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-secondary-200 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <Input
                        v-model="search"
                        placeholder="Cari karyawan / NIP..."
                        @keyup.enter="applyFilters"
                    />

                    <Select
                        v-model="branchId"
                        :options="branches.map(b => ({ value: b.id, label: b.name }))"
                        all-label="Semua Cabang"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="divisionId"
                        :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                        all-label="Semua Divisi"
                        @change="applyFilters"
                    />

                    <Select
                        v-model="status"
                        :options="statusOptions"
                        @change="applyFilters"
                    />
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-secondary-100">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="date" v-model="startDate" class="input !py-1.5 text-xs" @change="applyFilters" />
                        <span class="text-secondary-400 text-xs">s/d</span>
                        <input type="date" v-model="endDate" class="input !py-1.5 text-xs" @change="applyFilters" />
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <Button variant="secondary" size="sm" @click="resetFilters">Reset</Button>
                        <Button variant="primary" size="sm" @click="applyFilters">Terapkan Filter</Button>
                    </div>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3">Deskripsi & Catatan</th>
                                <th class="px-5 py-3 text-right">Nominal</th>
                                <th class="px-5 py-3 text-center">Metode</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Lampiran</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="item in reimbursements.data" :key="item.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-secondary-900">
                                    {{ item.date }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ item.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ item.employee?.nip || '-' }} • {{ item.employee?.branch?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="text-secondary-800 line-clamp-1">{{ item.reason }}</p>
                                    <p v-if="item.reject_reason" class="text-xs text-rose-500 italic mt-0.5">Alasan tolak: {{ item.reject_reason }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-secondary-900">
                                    {{ formatCurrency(item.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span v-if="item.payout_method === 'payroll'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        Payroll
                                    </span>
                                    <span v-else-if="item.payout_method === 'direct'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                        Transfer
                                    </span>
                                    <span v-else class="text-secondary-400 text-xs">-</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Badge v-if="item.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else-if="item.status === 'approved'" variant="info">Disetujui</Badge>
                                    <Badge v-else-if="item.status === 'rejected'" variant="danger">Ditolak</Badge>
                                    <Badge v-else variant="warning">{{ item.status }}</Badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <a
                                        v-if="item.attachment"
                                        :href="'/storage/' + item.attachment"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-primary-600 hover:underline"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        File
                                    </a>
                                    <span v-else class="text-xs text-secondary-400">-</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button
                                        v-if="item.status !== 'paid' && !item.payroll_item_id"
                                        class="text-secondary-400 hover:text-rose-600 transition p-1"
                                        title="Hapus Klaim"
                                        @click="handleDelete(item.id)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <span v-else class="text-xs text-secondary-400 italic">Terkunci</span>
                                </td>
                            </tr>
                            <tr v-if="reimbursements.data.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-secondary-500">
                                    Belum ada data klaim biaya yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reimbursements.links && reimbursements.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="reimbursements.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
