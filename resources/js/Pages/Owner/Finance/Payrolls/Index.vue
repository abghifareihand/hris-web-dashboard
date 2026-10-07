<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import DatePicker from '@/Components/UI/DatePicker.vue'
import DatePickerRange from '@/Components/UI/DatePickerRange.vue'
import Modal from '@/Components/UI/Modal.vue'
import Badge from '@/Components/UI/Badge.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'
import { useDebounce } from '@/Composables/useDebounce'

const props = defineProps({
    payrolls: Object,
    branches: Array,
    divisions: Array,
    stats: Object,
    filters: Object,
    readiness: Object,
})

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

const applyFilters = () => {
    router.get(
        route('owner.finance.payrolls.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            status: filters.status || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const { debouncedFn: debouncedSearch } = useDebounce(() => {
    applyFilters()
}, 350)

const onSearchInput = (val) => {
    filters.search = val
    debouncedSearch()
}

const onFilterChange = () => {
    applyFilters()
}

const resetFilters = () => {
    filters.search = ''
    filters.branch_id = ''
    filters.division_id = ''
    filters.status = ''
    filters.start_date = ''
    filters.end_date = ''
    applyFilters()
}

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.status ||
        filters.start_date ||
        filters.end_date
    )
}

const exportUrl = computed(() => {
    return route('owner.finance.payrolls.export-excel', {
        search: filters.search || undefined,
        branch_id: filters.branch_id || undefined,
        division_id: filters.division_id || undefined,
        status: filters.status || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    })
})

const statusOptions = [
    { value: 'process', label: 'Dalam Proses (Draft)' },
    { value: 'paid', label: 'Sudah Dibayar (Paid)' },
    { value: 'cancelled', label: 'Dibatalkan (Cancelled)' },
]

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

// Generate Modal State
const showGenerateModal = ref(false)

const padZero = (n) => String(n).padStart(2, '0')

const getDefaultDates = () => {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth() // 0-indexed (9 untuk Oktober)

    // Tanggal 1 bulan berjalan
    const startDate = new Date(year, month, 1)
    const start = `${startDate.getFullYear()}-${padZero(startDate.getMonth() + 1)}-${padZero(startDate.getDate())}`

    // Tanggal terakhir bulan berjalan
    const endDate = new Date(year, month + 1, 0)
    const end = `${endDate.getFullYear()}-${padZero(endDate.getMonth() + 1)}-${padZero(endDate.getDate())}`

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

const openGenerateModal = () => {
    const dates = getDefaultDates()
    generateForm.start_date = dates.start
    generateForm.end_date = dates.end
    generateForm.payroll_cycle = 'all'
    generateForm.branch_id = ''
    generateForm.division_id = ''
    generateForm.clearErrors()
    showGenerateModal.value = true
}

const payrollCycleOptions = [
    { value: 'all', label: 'Semua Siklus (Bulanan & Mingguan)' },
    { value: 'monthly', label: 'Bulanan' },
    { value: 'weekly', label: 'Mingguan' },
]

const filteredMissingSalary = computed(() => {
    if (!props.readiness?.missing_salary_employees) return []
    return props.readiness.missing_salary_employees.filter(emp => {
        if (generateForm.payroll_cycle && generateForm.payroll_cycle !== 'all' && emp.payroll_cycle && emp.payroll_cycle !== generateForm.payroll_cycle) return false
        if (generateForm.branch_id && emp.branch_id != generateForm.branch_id) return false
        if (generateForm.division_id && emp.division_id != generateForm.division_id) return false
        return true
    })
})

const submitGenerate = () => {
    generateForm.post(route('owner.finance.payrolls.store'), {
        onSuccess: () => {
            showGenerateModal.value = false
        }
    })
}

const payrollToDelete = ref(null)
const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)

const openDeleteModal = (payroll) => {
    payrollToDelete.value = payroll
    isDeleteModalOpen.value = true
}

const executeDelete = () => {
    if (!payrollToDelete.value) return
    isDeleting.value = true
    router.delete(route('owner.finance.payrolls.destroy', payrollToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false
            isDeleteModalOpen.value = false
            payrollToDelete.value = null
        },
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Penggajian - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Penggajian</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Rekapitulasi data penggajian karyawan berdasarkan periode.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="exportUrl" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Rekap
                        </Button>
                    </a>
                    <Button variant="primary" size="md" @click="openGenerateModal">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Generate Gaji Baru
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Gaji Dibayarkan (Paid)</div>
                        <div class="text-xl font-bold text-emerald-700 mt-0.5 truncate">{{ formatCurrency(stats.total_disbursed) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Draft / Dalam Proses</div>
                        <div class="text-xl font-bold text-amber-700 mt-0.5 truncate">{{ formatCurrency(stats.total_in_process) }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Batch Penggajian</div>
                        <div class="text-2xl font-bold text-primary-700 mt-0.5 truncate">{{ stats.total_payrolls_count }} <span class="text-xs font-normal text-slate-500">periode</span></div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Data Penggajian
                    </h2>

                    <!-- Reset Filter Button -->
                    <transition
                        enter-active-class="transition-opacity duration-150 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <Button
                            v-if="hasActiveFilters()"
                            variant="secondary"
                            size="sm"
                            type="button"
                            @click="resetFilters"
                            class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </Button>
                    </transition>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Row 1: Dropdown Filters & Date Range -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Status -->
                        <div>
                            <Select
                                label="Status"
                                v-model="filters.status"
                                :options="statusOptions"
                                all-label="Semua Status"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Cabang -->
                        <div>
                            <Select
                                label="Cabang"
                                v-model="filters.branch_id"
                                :options="branches.map((b) => ({ value: b.id, label: b.name }))"
                                all-label="Semua Cabang"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Divisi -->
                        <div>
                            <Select
                                label="Divisi"
                                v-model="filters.division_id"
                                :options="divisions.map((d) => ({ value: d.id, label: d.name }))"
                                all-label="Semua Divisi"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Periode -->
                        <div>
                            <DatePickerRange
                                label="Periode"
                                v-model:startDate="filters.start_date"
                                v-model:endDate="filters.end_date"
                                placeholder="Pilih Periode"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Search field -->
                    <div class="pt-3 border-t border-slate-100">
                        <Input
                            label="Pencarian Kode Penggajian"
                            :modelValue="filters.search"
                            @update:modelValue="onSearchInput"
                            placeholder="Ketik kode penggajian (misal: PY-202610)..."
                            clearable
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :headers="[
                    'Kode Penggajian',
                    'Periode',
                    'Cakupan Cabang & Divisi',
                    'Penerima',
                    'Total Gaji',
                    'Status',
                    '',
                ]"
            >
                <template v-if="payrolls.data && payrolls.data.length > 0">
                    <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-slate-50/70 transition">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-mono font-bold text-xs tracking-wider">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ payroll.code }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-slate-700 text-sm">
                                {{ formatDate(payroll.start_date) }} - {{ formatDate(payroll.end_date) }}
                            </div>
                            <div v-if="payroll.paid_at" class="text-xs text-emerald-600 flex items-center gap-1 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Dibayar: {{ formatDate(payroll.paid_at) }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs">
                            <div class="font-medium text-slate-800 text-sm">{{ payroll.branch ? payroll.branch.name : 'Semua Cabang' }}</div>
                            <div class="text-slate-400 mt-0.5">{{ payroll.division ? payroll.division.name : 'Semua Divisi' }}</div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ payroll.total_employees }} karyawan
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900 text-sm">
                            {{ formatCurrency(payroll.total_amount) }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <Badge v-if="payroll.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="payroll.status === 'process'" variant="warning">Draft / Proses</Badge>
                            <Badge v-else-if="payroll.status === 'cancelled'" variant="danger">Batal</Badge>
                            <Badge v-else variant="secondary">{{ payroll.status }}</Badge>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <Link
                                    :href="route('owner.finance.payrolls.show', payroll.id)"
                                    class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                    title="Lihat Detail Penggajian"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </Link>
                                <button
                                    v-if="payroll.status !== 'paid'"
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                    title="Hapus Penggajian"
                                    @click="openDeleteModal(payroll)"
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
                        title="Belum ada data penggajian"
                        message="Belum ada data penggajian yang tercatat. Klik 'Generate Gaji Baru' untuk memulai."
                        :colspan="7"
                    />
                </template>
            </DataTable>

            <!-- Pagination -->
            <TablePagination :pagination="payrolls" />
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">
                            Hapus Data Penggajian
                        </h3>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data penggajian <strong class="font-mono text-slate-900">{{ payrollToDelete?.code }}</strong>? Semua data kalkulasi gaji karyawan dalam periode ini akan dihapus secara permanen.
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="isDeleting"
                    @click="isDeleteModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    :loading="isDeleting"
                    :disabled="isDeleting"
                    @click="executeDelete"
                >
                    Ya, Hapus Penggajian
                </Button>
            </template>
        </Modal>

        <!-- Modal Generate Payroll Baru -->
        <Modal
            :show="showGenerateModal"
            title="Generate Periode Penggajian"
            @close="showGenerateModal = false"
        >
            <form @submit.prevent="submitGenerate" class="space-y-4">
                <!-- 1. Siklus Penggajian -->
                <Select
                    label="Siklus Penggajian"
                    v-model="generateForm.payroll_cycle"
                    :options="payrollCycleOptions"
                    placeholder="Pilih Siklus Penggajian"
                />

                <!-- 2. Pilih Cabang -->
                <Select
                    label="Pilih Cabang"
                    v-model="generateForm.branch_id"
                    :options="branches.map(b => ({ value: b.id, label: b.name }))"
                    all-label="Semua Cabang Perusahaan"
                    placeholder="Semua Cabang Perusahaan"
                />

                <!-- 3. Pilih Divisi -->
                <Select
                    label="Pilih Divisi"
                    v-model="generateForm.division_id"
                    :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                    all-label="Semua Divisi Perusahaan"
                    placeholder="Semua Divisi Perusahaan"
                />

                <!-- 4. Periode Awal -->
                <DatePicker
                    label="Periode Awal"
                    v-model="generateForm.start_date"
                    :required="true"
                    :error="generateForm.errors.start_date"
                    placeholder="Pilih Tanggal Awal Periode"
                />

                <!-- 5. Periode Akhir -->
                <DatePicker
                    label="Periode Akhir"
                    v-model="generateForm.end_date"
                    :required="true"
                    :error="generateForm.errors.end_date"
                    placeholder="Pilih Tanggal Akhir Cut-Off"
                />

                <!-- Employee Readiness Indicator -->
                <div v-if="filteredMissingSalary.length > 0" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/90 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-xs flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-bold text-amber-900">
                                {{ filteredMissingSalary.length }} Karyawan Belum Ada Gaji Pokok
                            </div>
                            <Link
                                :href="route('owner.finance.payrolls.master.index', { salary_status: 'unconfigured' })"
                                class="text-[11px] font-semibold text-amber-800 hover:text-amber-950 underline shrink-0 inline-flex items-center gap-0.5"
                            >
                                <span>Atur Sekarang</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </Link>
                        </div>
                        <p class="text-amber-800 mt-1 leading-relaxed">
                            Karyawan berikut belum memiliki data gaji pokok dan <strong>otomatis dilewati</strong> saat generate:
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                            <span
                                v-for="emp in filteredMissingSalary.slice(0, 8)"
                                :key="emp.id"
                                class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 font-medium text-[11px] border border-amber-200/80"
                            >
                                {{ emp.name }}
                            </span>
                            <span v-if="filteredMissingSalary.length > 8" class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100/70 text-amber-800 text-[11px]">
                                +{{ filteredMissingSalary.length - 8 }} lainnya
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="text-xs">
                        <div class="font-bold text-emerald-800">Semua Data Karyawan Siap</div>
                        <p class="text-emerald-700 mt-0.5 leading-relaxed">
                            Seluruh karyawan aktif pada cakupan ini telah memiliki data Gaji Pokok dan siap dikalkulasi.
                        </p>
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
                    <Button variant="ghost" type="button" @click="showGenerateModal = false">Batal</Button>
                    <Button variant="primary" type="submit" :disabled="generateForm.processing">
                        {{ generateForm.processing ? 'Menghitung Penggajian...' : (filteredMissingSalary.length > 0 ? 'Tetap Generate Penggajian' : 'Generate Penggajian') }}
                    </Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
