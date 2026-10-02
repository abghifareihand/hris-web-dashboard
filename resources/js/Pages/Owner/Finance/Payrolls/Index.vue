<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    payrolls: Object,
    branches: Array,
    divisions: Array,
    stats: Object,
    filters: Object,
})

const branchId = ref(props.filters?.branch_id || '')
const divisionId = ref(props.filters?.division_id || '')
const status = ref(props.filters?.status || '')
const startDate = ref(props.filters?.start_date || '')
const endDate = ref(props.filters?.end_date || '')

const applyFilters = () => {
    router.get(route('owner.finance.payrolls.index'), {
        branch_id: branchId.value || undefined,
        division_id: divisionId.value || undefined,
        status: status.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    branchId.value = ''
    divisionId.value = ''
    status.value = ''
    startDate.value = ''
    endDate.value = ''
    applyFilters()
}

// Generate Modal State
const showGenerateModal = ref(false)

const getDefaultDates = () => {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth()
    const start = new Date(year, month, 1).toISOString().split('T')[0]
    const end = new Date(year, month + 1, 0).toISOString().split('T')[0]
    return { start, end }
}

const defaultDates = getDefaultDates()

const generateForm = useForm({
    start_date: defaultDates.start,
    end_date: defaultDates.end,
    payroll_cycle: 'all',
    branch_id: '',
    division_id: '',
})

const submitGenerate = () => {
    generateForm.post(route('owner.finance.payrolls.store'), {
        onSuccess: () => {
            showGenerateModal.value = false
        }
    })
}

const handleDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data penggajian ini?')) {
        router.delete(route('owner.finance.payrolls.destroy', id))
    }
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Penggajian (Payroll) - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Manajemen Penggajian (Payroll)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Kalkulasi gaji bulanan otomatis terintegrasi absensi, lembur, kasbon, BPJS & PPh 21.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.payrolls.master.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Master Gaji Karyawan
                        </Button>
                    </Link>
                    <a :href="route('owner.finance.payrolls.export-excel', filters)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Rekap
                        </Button>
                    </a>
                    <Button variant="primary" size="md" @click="showGenerateModal = true">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Generate Gaji Baru
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Gaji Dibayarkan (Paid)</div>
                        <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ formatCurrency(stats.total_disbursed) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Draft / Dalam Proses</div>
                        <div class="text-xl font-bold text-amber-700 mt-0.5">{{ formatCurrency(stats.total_in_process) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-secondary-200 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-secondary-500 uppercase tracking-wider">Total Batch Penggajian</div>
                        <div class="text-2xl font-bold text-primary-700 mt-0.5">{{ stats.total_payrolls_count }} <span class="text-xs font-normal text-secondary-500">periode</span></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-secondary-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 w-full sm:w-auto">
                    <select v-model="branchId" class="input !py-1.5 text-xs" @change="applyFilters">
                        <option value="">Semua Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>

                    <select v-model="divisionId" class="input !py-1.5 text-xs" @change="applyFilters">
                        <option value="">Semua Divisi</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>

                    <select v-model="status" class="input !py-1.5 text-xs" @change="applyFilters">
                        <option value="">Semua Status</option>
                        <option value="process">Dalam Proses (Process)</option>
                        <option value="paid">Sudah Dibayar (Paid)</option>
                        <option value="cancelled">Dibatalkan (Cancelled)</option>
                    </select>

                    <div class="flex items-center gap-1.5">
                        <input type="date" v-model="startDate" class="input !py-1.5 text-xs" @change="applyFilters" />
                        <span class="text-secondary-400 text-xs">-</span>
                        <input type="date" v-model="endDate" class="input !py-1.5 text-xs" @change="applyFilters" />
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <Button variant="secondary" size="sm" @click="resetFilters">Reset</Button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Kode Penggajian</th>
                                <th class="px-5 py-3">Periode Cut-Off</th>
                                <th class="px-5 py-3">Cakupan Cabang & Divisi</th>
                                <th class="px-5 py-3 text-center">Penerima</th>
                                <th class="px-5 py-3 text-right">Total Netto Gaji</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap font-mono font-bold text-primary-700">
                                    {{ payroll.code }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-medium text-secondary-900">{{ payroll.start_date }} s/d {{ payroll.end_date }}</div>
                                    <div v-if="payroll.paid_at" class="text-xs text-emerald-600 font-mono">Dibayar: {{ payroll.paid_at }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-secondary-700 text-xs">
                                    <div>{{ payroll.branch ? payroll.branch.name : 'Semua Cabang' }}</div>
                                    <div class="text-secondary-500">{{ payroll.division ? payroll.division.name : 'Semua Divisi' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center font-bold text-secondary-800">
                                    {{ payroll.total_employees }} org
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-black text-emerald-600 text-base">
                                    {{ formatCurrency(payroll.total_amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <Badge v-if="payroll.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                                    <Badge v-else-if="payroll.status === 'process'" variant="warning">Draft / Proses</Badge>
                                    <Badge v-else-if="payroll.status === 'cancelled'" variant="danger">Batal</Badge>
                                    <Badge v-else variant="secondary">{{ payroll.status }}</Badge>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('owner.finance.payrolls.show', payroll.id)">
                                            <Button variant="secondary" size="sm">
                                                Detail
                                            </Button>
                                        </Link>
                                        <button
                                            v-if="payroll.status !== 'paid'"
                                            class="text-secondary-400 hover:text-rose-600 transition p-1"
                                            title="Hapus Penggajian"
                                            @click="handleDelete(payroll.id)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="payrolls.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-secondary-500">
                                    Belum ada data penggajian yang tercatat. Klik "Generate Gaji Baru" untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payrolls.links && payrolls.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="payrolls.links" />
                </div>
            </div>
        </div>

        <!-- Modal Generate Payroll Baru -->
        <Modal :show="showGenerateModal" title="Generate Periode Penggajian Baru" @close="showGenerateModal = false">
            <form @submit.prevent="submitGenerate" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">
                            Tanggal Awal Periode <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" v-model="generateForm.start_date" class="input" required />
                        <span v-if="generateForm.errors.start_date" class="text-xs text-rose-500 mt-1 block">
                            {{ generateForm.errors.start_date }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">
                            Tanggal Akhir Cut-Off <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" v-model="generateForm.end_date" class="input" required />
                        <span v-if="generateForm.errors.end_date" class="text-xs text-rose-500 mt-1 block">
                            {{ generateForm.errors.end_date }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Cakupan Cabang</label>
                        <select v-model="generateForm.branch_id" class="input">
                            <option value="">Semua Cabang Perusahaan</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Cakupan Divisi</label>
                        <select v-model="generateForm.division_id" class="input">
                            <option value="">Semua Divisi</option>
                            <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-primary-50 border border-primary-100 flex items-start gap-3">
                    <svg class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-xs text-primary-800 leading-relaxed">
                        Sistem akan mengkalkulasi jam kerja, absensi, denda keterlambatan/alpha, lembur yang disetujui, klaim reimbursement, cicilan pinjaman kasbon, serta BPJS dan PPh 21 secara otomatis.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-secondary-200">
                    <Button variant="secondary" type="button" @click="showGenerateModal = false">Batal</Button>
                    <Button variant="primary" type="submit" :disabled="generateForm.processing">
                        {{ generateForm.processing ? 'Menghitung Penggajian...' : 'Generate Penggajian Sekarang' }}
                    </Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
