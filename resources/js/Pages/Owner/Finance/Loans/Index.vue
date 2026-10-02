<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'
import Dropdown from '@/Components/UI/Dropdown.vue'

const props = defineProps({
    loans: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')

const loanStatusOptions = [
    { value: '', label: 'Semua Status', dotClass: 'bg-slate-300' },
    { value: 'approved', label: 'Sedang Dicicil (Approved)', dotClass: 'bg-amber-500' },
    { value: 'paid', label: 'Lunas (Paid)', dotClass: 'bg-emerald-500' },
]

const applyFilters = () => {
    router.get(route('owner.finance.loans.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    search.value = ''
    status.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

const handleDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data pinjaman ini? Hanya pinjaman tanpa pembayaran yang dapat dihapus.')) {
        router.delete(route('owner.finance.loans.destroy', id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Pinjaman (Kasbon) - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Manajemen Pinjaman & Kasbon</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Pantau saldo pinjaman, jadwal cicilan, dan kebijakan kasbon karyawan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.loans.settings.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kebijakan Kasbon
                        </Button>
                    </Link>
                    <Link :href="route('owner.finance.loans.pending.index')">
                        <Button variant="secondary" size="md">
                            Klaim Pending
                            <span v-if="stats.pending_count > 0" class="ml-2 px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                                {{ stats.pending_count }}
                            </span>
                        </Button>
                    </Link>
                    <Link :href="route('owner.finance.loans.create')">
                        <Button variant="primary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Input Pinjaman
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Pinjaman Berjalan (Aktif)</div>
                        <div class="text-xl font-bold text-amber-700 mt-0.5">{{ formatCurrency(stats.total_active_loan) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Pinjaman Lunas</div>
                        <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ formatCurrency(stats.total_paid_loan) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Permohonan Pending</div>
                        <div class="text-xl font-bold text-primary-700 mt-0.5">{{ stats.pending_count }} <span class="text-xs font-normal text-secondary-500">menunggu</span></div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-xl border border-secondary-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-80">
                    <Input
                        v-model="search"
                        placeholder="Cari karyawan / NIP..."
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="w-52">
                        <Dropdown
                            v-model="status"
                            :options="loanStatusOptions"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>
                    <input type="date" v-model="startDate" class="input !py-1.5 !w-auto text-xs" @change="applyFilters" />
                    <span class="text-secondary-400 text-xs">s/d</span>
                    <input type="date" v-model="endDate" class="input !py-1.5 !w-auto text-xs" @change="applyFilters" />
                    <Button variant="secondary" size="sm" @click="resetFilters">Reset</Button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Tanggal Pinjam</th>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3 text-right">Nominal Pinjaman</th>
                                <th class="px-5 py-3 text-center">Tenor</th>
                                <th class="px-5 py-3 text-right">Cicilan/Bln</th>
                                <th class="px-5 py-3">Progres Cicilan</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-secondary-900">
                                    {{ loan.date }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ loan.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ loan.employee?.nip || '-' }} • {{ loan.employee?.branch?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-secondary-900">
                                    {{ formatCurrency(loan.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-800">
                                        {{ loan.tenor }} bln
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-semibold text-primary-700">
                                    {{ formatCurrency(loan.amount / loan.tenor) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap min-w-[160px]">
                                    <div v-if="loan.installments && loan.installments.length > 0">
                                        <div class="flex justify-between text-xs text-secondary-500 mb-1">
                                            <span>{{ loan.installments.filter(i => i.status === 'paid').length }}/{{ loan.installments.length }} Bulan</span>
                                            <span class="font-semibold">{{ Math.round((loan.installments.filter(i => i.status === 'paid').length / loan.installments.length) * 100) }}%</span>
                                        </div>
                                        <div class="w-full bg-secondary-100 rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-2 rounded-full transition-all duration-300"
                                                :class="loan.status === 'paid' ? 'bg-emerald-500' : 'bg-primary-500'"
                                                :style="{ width: `${Math.round((loan.installments.filter(i => i.status === 'paid').length / loan.installments.length) * 100)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-secondary-400 italic">Belum digenerate</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Badge v-if="loan.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else-if="loan.status === 'approved'" variant="warning">Berjalan</Badge>
                                    <Badge v-else variant="secondary">{{ loan.status }}</Badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('owner.finance.loans.show', loan.id)">
                                            <Button variant="secondary" size="sm">
                                                Detail
                                            </Button>
                                        </Link>
                                        <button
                                            v-if="loan.status !== 'paid' && loan.installments && loan.installments.filter(i => i.status === 'paid').length === 0"
                                            class="text-secondary-400 hover:text-rose-600 transition p-1"
                                            title="Hapus Pinjaman"
                                            @click="handleDelete(loan.id)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="loans.data.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-secondary-500">
                                    Belum ada data pinjaman yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="loans.links && loans.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="loans.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
