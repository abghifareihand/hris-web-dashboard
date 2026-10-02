<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    employees: Object,
    bpjsTk: Object,
    bpjsKes: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const handleSearch = () => {
    router.get(route('owner.finance.payrolls.master.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Master Penggajian Karyawan - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Master Komponen Gaji Karyawan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Daftar konfigurasi gaji pokok, tunjangan, denda absensi, dan data kepesertaan BPJS per karyawan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.payrolls.index')">
                        <Button variant="secondary" size="md">
                            Kembali ke Payroll
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Search & Count -->
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
                    Total <span class="font-bold text-secondary-900">{{ employees.total }}</span> data karyawan
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3 text-right">Gaji Pokok</th>
                                <th class="px-5 py-3 text-right">Tunj. Tetap</th>
                                <th class="px-5 py-3 text-right">Uang Makan / Hr</th>
                                <th class="px-5 py-3 text-right">Upah Lembur / Jam</th>
                                <th class="px-5 py-3 text-center">PTKP Pajak</th>
                                <th class="px-5 py-3 text-center">Status BPJS</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ emp.name }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ emp.nip || '-' }} • {{ emp.division?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-secondary-900">
                                    <span v-if="emp.basic_salary">{{ formatCurrency(emp.basic_salary) }}</span>
                                    <span v-else class="text-xs text-rose-500 font-bold bg-rose-50 px-2 py-0.5 rounded">Belum Diatur</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800">
                                    {{ formatCurrency(emp.fixed_allowance) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800">
                                    {{ formatCurrency(emp.daily_allowance) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-secondary-800">
                                    {{ formatCurrency(emp.overtime_rate_per_hour) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-secondary-100 text-secondary-800">
                                        {{ emp.ptkp_status || 'TK/0' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span
                                            class="w-2.5 h-2.5 rounded-full"
                                            :class="emp.bpjs_kesehatan_no ? 'bg-emerald-500' : 'bg-secondary-300'"
                                            :title="emp.bpjs_kesehatan_no ? 'BPJS Kes: ' + emp.bpjs_kesehatan_no : 'BPJS Kes belum ada'"
                                        ></span>
                                        <span
                                            class="w-2.5 h-2.5 rounded-full"
                                            :class="emp.jht_no ? 'bg-blue-500' : 'bg-secondary-300'"
                                            :title="emp.jht_no ? 'BPJS TK: ' + emp.jht_no : 'BPJS TK belum ada'"
                                        ></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Link :href="route('owner.management.employees.edit', emp.id)">
                                        <Button variant="secondary" size="sm">
                                            Edit
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-secondary-500">
                                    Tidak ada data karyawan yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="employees.links && employees.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="employees.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
