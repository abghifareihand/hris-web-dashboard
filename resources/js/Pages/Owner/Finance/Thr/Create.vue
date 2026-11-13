<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import DatePicker from '@/Components/UI/DatePicker.vue'
import Alert from '@/Components/UI/Alert.vue'
import Modal from '@/Components/UI/Modal.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    payrollSetting: {
        type: Object,
        default: () => ({}),
    },
    previewData: {
        type: Array,
        default: null,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()

// Form Filter / Preview
const normalizeDate = (val) => {
    if (!val || typeof val !== 'string') return ''
    val = val.trim()
    if (val.includes('/')) {
        const parts = val.split('/')
        if (parts.length === 3) {
            if (parts[2].length === 4) {
                return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
            }
            if (parts[0].length === 4) {
                return `${parts[0]}-${parts[1].padStart(2, '0')}-${parts[2].padStart(2, '0')}`
            }
        }
    }
    return val
}

const filterForm = useForm({
    holiday_name: props.params?.holiday_name || 'Hari Raya Idul Fitri',
    year: props.params?.year || new Date().getFullYear(),
    payment_date: props.params?.payment_date || new Date().toISOString().split('T')[0],
    branch_id: props.params?.branch_id || '',
})

// Sync year when payment_date changes
watch(
    () => filterForm.payment_date,
    (newDate) => {
        if (!newDate || typeof newDate !== 'string') return
        const normalized = normalizeDate(newDate)
        if (normalized.includes('-')) {
            const y = parseInt(normalized.split('-')[0], 10)
            if (!isNaN(y) && y > 2000) {
                filterForm.year = y
            }
        }
    }
)

// Branch options for Select component
const branchOptions = computed(() => {
    return (props.branches || []).map((b) => ({
        value: b.id,
        label: b.name,
    }))
})

// Handle Preview
const isCalculating = ref(false)

const handlePreview = () => {
    isCalculating.value = true
    filterForm.payment_date = normalizeDate(filterForm.payment_date)

    filterForm
        .transform((data) => ({
            ...data,
            branch_id: data.branch_id || '',
        }))
        .post(route('owner.finance.thr.preview'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                isCalculating.value = false
            },
            onError: () => {
                isCalculating.value = false
            },
            onCancel: () => {
                isCalculating.value = false
            },
            onFinish: () => {
                isCalculating.value = false
            },
        })
}

// Final Save Form & Selection
const selectedEmployeeIds = ref([])
const isConfirmSaveModalOpen = ref(false)
const isNoSelectionModalOpen = ref(false)

// Sync selected IDs when previewData updates
watch(
    () => props.previewData,
    (newData) => {
        if (newData && Array.isArray(newData)) {
            selectedEmployeeIds.value = newData.map((p) => p.employee_id)
        } else {
            selectedEmployeeIds.value = []
        }
    },
    { immediate: true }
)

const selectAll = (e) => {
    if (e.target.checked && props.previewData) {
        selectedEmployeeIds.value = props.previewData.map((p) => p.employee_id)
    } else {
        selectedEmployeeIds.value = []
    }
}

const isAllSelected = computed(() => {
    return (
        props.previewData &&
        props.previewData.length > 0 &&
        selectedEmployeeIds.value.length === props.previewData.length
    )
})

// Computed Summary Stats
const selectedTotalAmount = computed(() => {
    if (!props.previewData) return 0
    return props.previewData
        .filter((row) => selectedEmployeeIds.value.includes(row.employee_id))
        .reduce((sum, row) => sum + (row.thr_amount || 0), 0)
})

const storeForm = useForm({
    holiday_name: '',
    year: '',
    payment_date: '',
    branch_id: null,
    employee_ids: [],
})

const openSaveConfirm = () => {
    if (selectedEmployeeIds.value.length === 0) {
        isNoSelectionModalOpen.value = true
        return
    }
    isConfirmSaveModalOpen.value = true
}

const executeStore = () => {
    storeForm.holiday_name = filterForm.holiday_name
    storeForm.year = parseInt(filterForm.year, 10)
    storeForm.payment_date = normalizeDate(filterForm.payment_date)
    storeForm.branch_id = filterForm.branch_id ? parseInt(filterForm.branch_id, 10) : null
    storeForm.employee_ids = selectedEmployeeIds.value

    storeForm.post(route('owner.finance.thr.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isConfirmSaveModalOpen.value = false
        },
        onError: () => {
            isConfirmSaveModalOpen.value = false
        },
    })
}

// Formatters
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0)
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Hitung & Generate THR - Frans HRIS" />

        <div class="space-y-6">
            <!-- Flash Message Banner -->
            <div v-if="page.props.flash?.error" class="transition-all duration-300">
                <Alert variant="danger" :title="page.props.flash.error" dismissible />
            </div>
            <div v-if="storeForm.errors && Object.keys(storeForm.errors).length > 0" class="transition-all duration-300">
                <Alert
                    variant="danger"
                    :title="Object.values(storeForm.errors)[0]"
                    dismissible
                />
            </div>

            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3.5 sm:gap-4">
                    <Link
                        :href="route('owner.finance.thr.index')"
                        class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                        title="Kembali ke Daftar THR"
                    >
                        <svg
                            class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>

                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Hitung & Generate THR</h1>
                        <p class="text-sm text-slate-500 mt-1">
                            Kalkulasi otomatis hak Tunjangan Hari Raya (THR) bagi karyawan bulanan, mingguan, dan harian sesuai regulasi masa kerja.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.thr.settings.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-1.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Kebijakan THR
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Card Langkah 1: Parameter Hari Raya & Pembayaran -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-xs border border-emerald-200/70">
                        1
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Langkah 1: Parameter Hari Raya & Tanggal Pembayaran</h2>
                        <p class="text-xs text-slate-500">Tentukan periode hari raya keagamaan dan target tanggal pembayaran untuk menghitung masa kerja karyawan.</p>
                    </div>
                </div>

                <!-- Inputs Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Nama Hari Raya -->
                    <div>
                        <Input
                            label="Nama Hari Raya"
                            v-model="filterForm.holiday_name"
                            placeholder="Contoh: Hari Raya Idul Fitri 1447 H"
                            required
                            :error="filterForm.errors?.holiday_name"
                        />
                    </div>

                    <!-- Tahun Anggaran -->
                    <div>
                        <Input
                            label="Tahun Anggaran"
                            type="number"
                            v-model="filterForm.year"
                            placeholder="YYYY"
                            required
                            :error="filterForm.errors?.year"
                        />
                    </div>

                    <!-- Tanggal Pembayaran -->
                    <div>
                        <DatePicker
                            label="Tanggal Rencana Pembayaran"
                            v-model="filterForm.payment_date"
                            placeholder="Pilih Tanggal Pembayaran"
                            required
                            :error="filterForm.errors?.payment_date"
                        />
                    </div>

                    <!-- Cakupan Cabang -->
                    <div>
                        <Select
                            label="Cakupan Cabang"
                            v-model="filterForm.branch_id"
                            :options="branchOptions"
                            placeholder="Semua Cabang Perusahaan"
                            all-label="Semua Cabang Perusahaan"
                            :error="filterForm.errors?.branch_id"
                        />
                    </div>
                </div>

                <!-- Bottom Bar: Policy Badges (Left) + Primary Action (Right) -->
                <div class="pt-4 border-t border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 border border-slate-200/60 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Min. Kerja: <strong class="text-slate-800">{{ settings.min_months_tenure || 1 }} Bulan</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 border border-slate-200/60 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Standar Harian: <strong class="text-slate-800">{{ payrollSetting?.monthly_working_days || 25 }} Hari</strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 border border-slate-200/60 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                            Komponen: <strong class="text-slate-800">{{ settings.include_fixed_allowance ? 'Gaji + Tunjangan Tetap' : 'Hanya Gaji Pokok' }}</strong>
                        </span>
                    </div>

                    <div class="shrink-0 flex items-center justify-end">
                        <Button
                            variant="primary"
                            size="md"
                            :disabled="isCalculating || filterForm.processing"
                            @click="handlePreview"
                        >
                            <svg
                                v-if="!isCalculating && !filterForm.processing"
                                class="w-4 h-4 mr-1.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                            {{ (isCalculating || filterForm.processing) ? 'Menghitung...' : 'Hitung THR' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Card Langkah 2: Hasil Preview & Simpan -->
            <div v-if="previewData !== null" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden space-y-4 p-6">
                <!-- Header Card & Overview Stats -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-xs border border-emerald-200/70">
                            2
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Langkah 2: Hasil Kalkulasi THR</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Terpilih <strong class="text-slate-800">{{ selectedEmployeeIds.length }}</strong> dari {{ previewData.length }} karyawan
                                <span class="mx-1.5 text-slate-300">•</span>
                                Total Alokasi: <strong class="text-emerald-600 font-bold font-mono">{{ formatCurrency(selectedTotalAmount) }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <Button
                            variant="primary"
                            size="md"
                            :disabled="storeForm.processing || selectedEmployeeIds.length === 0"
                            @click="openSaveConfirm"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ storeForm.processing ? 'Menyimpan...' : 'Simpan THR' }}
                        </Button>
                    </div>
                </div>

                <!-- DataTable Preview -->
                <DataTable
                    :headers="[
                        '',
                        'Karyawan',
                        'Siklus & Upah Dasar',
                        'Tgl Bergabung & Masa Kerja',
                        'Basis Upah THR',
                        'Skema THR',
                        'Estimasi THR',
                    ]"
                >
                    <template v-if="previewData && previewData.length > 0">
                        <tr
                            v-for="row in previewData"
                            :key="row.employee_id"
                            class="hover:bg-slate-50/70 transition"
                            :class="selectedEmployeeIds.includes(row.employee_id) ? 'bg-emerald-50/20' : ''"
                        >
                            <!-- Checkbox -->
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <input
                                    type="checkbox"
                                    :value="row.employee_id"
                                    v-model="selectedEmployeeIds"
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                />
                            </td>

                            <!-- Karyawan -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ row.name?.charAt(0).toUpperCase() || 'E' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 text-sm truncate">
                                            {{ row.name }}
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5 truncate flex items-center gap-1.5">
                                            <span>{{ row.nip || '-' }}</span>
                                            <span>•</span>
                                            <span>{{ row.division }}</span>
                                            <span>•</span>
                                            <span class="text-slate-400">{{ row.branch }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Siklus & Upah Dasar -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div>
                                        <span
                                            v-if="row.payroll_cycle === 'daily'"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Harian
                                        </span>
                                        <span
                                            v-else-if="row.payroll_cycle === 'weekly'"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"
                                        >
                                            Mingguan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"
                                        >
                                            Bulanan
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">
                                        {{ formatCurrency(row.raw_basic_salary) }}
                                        <span class="text-[11px] text-slate-400">
                                            /{{ row.payroll_cycle === 'daily' ? 'hari' : (row.payroll_cycle === 'weekly' ? 'mgg' : 'bln') }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Tgl Masuk & Masa Kerja -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="text-xs text-slate-700 font-medium">
                                    {{ formatDate(row.joined_at) }}
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ row.months_tenure }} Bulan
                                    </span>
                                </div>
                            </td>

                            <!-- Basis Upah THR -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 text-sm">
                                    {{ formatCurrency(row.basis) }}
                                </div>
                                <div v-if="row.payroll_cycle !== 'monthly'" class="text-[11px] text-slate-400">
                                    Ekuivalen 1 bln ({{ payrollSetting?.monthly_working_days || 25 }} hari)
                                </div>
                            </td>

                            <!-- Skema THR (Penuh 1x / Prorata) -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="row.prorate_multiplier >= 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' : 'bg-amber-50 text-amber-700 border border-amber-200/80'"
                                >
                                    <svg v-if="row.prorate_multiplier >= 1" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ row.prorate_multiplier >= 1 ? 'Penuh (1x Gaji)' : `Prorata (${row.months_tenure}/12)` }}</span>
                                </span>
                            </td>

                            <!-- Estimasi THR -->
                            <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-emerald-600 text-base">
                                {{ formatCurrency(row.thr_amount) }}
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <TableEmpty
                            title="Tidak Ada Karyawan Memenuhi Syarat"
                            message="Tidak ditemukan karyawan aktif yang memenuhi syarat masa kerja minimal pada tanggal pembayaran yang dipilih."
                            :colspan="7"
                        />
                    </template>
                </DataTable>

                <!-- Table Footer Info -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800">{{ previewData.length }} karyawan</strong>
                    </div>
                    <div>
                        Terpilih: <strong class="text-emerald-600 font-semibold">{{ selectedEmployeeIds.length }}</strong> dari {{ previewData.length }} karyawan
                    </div>
                </div>
            </div>

            <!-- Placeholder Awal Sebelum Klik Preview -->
            <div v-else class="bg-white rounded-2xl border border-dashed border-slate-200 p-10 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 border border-slate-200/70 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Belum Ada Data Simulasi</h3>
                <p class="text-xs text-slate-400 mt-1 mb-4">
                    Tentukan parameter di atas lalu klik Hitung THR.
                </p>
                <Button
                    variant="secondary"
                    size="sm"
                    :disabled="isCalculating || filterForm.processing"
                    @click="handlePreview"
                >
                    {{ (isCalculating || filterForm.processing) ? 'Menghitung...' : 'Hitung THR' }}
                </Button>
            </div>
        </div>

        <!-- Modal Konfirmasi Simpan & Proses THR -->
        <Modal
            :show="isConfirmSaveModalOpen"
            maxWidth="md"
            @close="isConfirmSaveModalOpen = false"
        >
            <div>
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Konfirmasi Simpan THR
                        </h3>
                        <p class="text-xs text-slate-500">
                            Periksa ringkasan parameter dan nominal sebelum disimpan.
                        </p>
                    </div>
                </div>

                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70 text-xs space-y-2.5">
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-500">Hari Raya</span>
                        <strong class="text-slate-800 text-sm">{{ filterForm.holiday_name }}</strong>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-500">Tahun Anggaran</span>
                        <span class="font-semibold text-slate-800">{{ filterForm.year }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-500">Tanggal Rencana Bayar</span>
                        <span class="font-semibold text-slate-800">{{ formatDate(filterForm.payment_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-500">Jumlah Karyawan Terpilih</span>
                        <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">{{ selectedEmployeeIds.length }} Karyawan</span>
                    </div>
                    <div class="flex justify-between items-center pt-1.5">
                        <span class="text-slate-600 font-semibold">Total Alokasi THR</span>
                        <span class="font-bold text-emerald-600 text-base">{{ formatCurrency(selectedTotalAmount) }}</span>
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-3">
                    Setelah disimpan, status awal adalah <strong>Draft (Process)</strong> dan slip THR per karyawan dapat langsung dicetak atau ditandai selesai.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2.5">
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="storeForm.processing"
                        @click="isConfirmSaveModalOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        :loading="storeForm.processing"
                        :disabled="storeForm.processing"
                        @click="executeStore"
                    >
                        Simpan THR
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Modal Peringatan Belum Pilih Karyawan -->
        <Modal
            :show="isNoSelectionModalOpen"
            maxWidth="sm"
            @close="isNoSelectionModalOpen = false"
        >
            <div>
                <div class="flex items-center gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Peringatan
                        </h3>
                        <p class="text-xs text-slate-500">
                            Karyawan Belum Dipilih
                        </p>
                    </div>
                </div>
                <p class="text-sm text-slate-600">
                    Silakan centang minimal satu karyawan pada tabel hasil preview sebelum menyimpan perhitungan THR.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <Button
                        type="button"
                        variant="primary"
                        size="md"
                        @click="isNoSelectionModalOpen = false"
                    >
                        Mengerti
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
