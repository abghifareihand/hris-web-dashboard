<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const props = defineProps({
    branches: Array,
    settings: Object,
    previewData: Array,
    params: Object,
})

const filterForm = useForm({
    holiday_name: props.params?.holiday_name || 'Hari Raya Idul Fitri',
    year: props.params?.year || new Date().getFullYear(),
    payment_date: props.params?.payment_date || new Date().toISOString().split('T')[0],
    branch_id: props.params?.branch_id || '',
})

const handlePreview = () => {
    filterForm.post(route('owner.finance.thr.preview'), {
        preserveState: true,
    })
}

// Final Save Form
const selectedEmployeeIds = ref(props.previewData ? props.previewData.map(p => p.employee_id) : [])

const selectAll = (e) => {
    if (e.target.checked) {
        selectedEmployeeIds.value = props.previewData.map(p => p.employee_id)
    } else {
        selectedEmployeeIds.value = []
    }
}

const isAllSelected = () => {
    return props.previewData && selectedEmployeeIds.value.length === props.previewData.length
}

const storeForm = useForm({
    holiday_name: '',
    year: '',
    payment_date: '',
    branch_id: '',
    employee_ids: [],
})

const handleSave = () => {
    if (selectedEmployeeIds.value.length === 0) {
        alert('Pilih minimal satu karyawan untuk digenerate THR-nya.')
        return
    }
    storeForm.holiday_name = filterForm.holiday_name
    storeForm.year = filterForm.year
    storeForm.payment_date = filterForm.payment_date
    storeForm.branch_id = filterForm.branch_id
    storeForm.employee_ids = selectedEmployeeIds.value
    storeForm.post(route('owner.finance.thr.store'))
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Generate Perhitungan THR - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Hitung & Generate THR Karyawan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Kalkulasi hak Tunjangan Hari Raya otomatis berdasarkan masa kerja (prorata / penuh).
                    </p>
                </div>
                <Link :href="route('owner.finance.thr.index')">
                    <Button variant="secondary" size="md">Kembali</Button>
                </Link>
            </div>

            <!-- Param Form Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-secondary-900 uppercase tracking-wider">Langkah 1: Tentukan Parameter Hari Raya & Tanggal Pembayaran</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Nama Hari Raya</label>
                        <input type="text" v-model="filterForm.holiday_name" class="input" placeholder="Idul Fitri / Natal / dll" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Tahun Anggaran</label>
                        <input type="number" v-model="filterForm.year" class="input" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Tanggal Rencana Pembayaran</label>
                        <input type="date" v-model="filterForm.payment_date" class="input" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-secondary-700 mb-1">Cakupan Cabang</label>
                        <select v-model="filterForm.branch_id" class="input">
                            <option value="">Semua Cabang Perusahaan</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-secondary-100">
                    <Button variant="primary" size="md" :disabled="filterForm.processing" @click="handlePreview">
                        {{ filterForm.processing ? 'Menghitung...' : 'Preview Perhitungan Karyawan' }}
                    </Button>
                </div>
            </div>

            <!-- Preview Table Card -->
            <div v-if="previewData" class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden space-y-4 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-secondary-200">
                    <div>
                        <h3 class="font-bold text-secondary-900 text-base">Langkah 2: Periksa Rincian Perhitungan Masa Kerja & Nominal</h3>
                        <p class="text-xs text-secondary-500 mt-0.5">
                            Ditemukan {{ previewData.length }} karyawan yang memenuhi syarat minimal masa kerja ({{ settings.min_months_tenure }} bulan).
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="primary" size="md" :disabled="storeForm.processing" @click="handleSave">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ storeForm.processing ? 'Menyimpan...' : 'Simpan Perhitungan THR Ini' }}
                        </Button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-center">
                                    <input type="checkbox" :checked="isAllSelected()" @change="selectAll" class="rounded text-primary-600 focus:ring-primary-500" />
                                </th>
                                <th class="px-4 py-3">Karyawan</th>
                                <th class="px-4 py-3">Tgl Bergabung</th>
                                <th class="px-4 py-3 text-center">Masa Kerja</th>
                                <th class="px-4 py-3 text-right">Basis Gaji</th>
                                <th class="px-4 py-3 text-center">Tipe / Pengali</th>
                                <th class="px-4 py-3 text-right font-bold text-secondary-900">Estimasi THR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="row in previewData" :key="row.employee_id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <input type="checkbox" :value="row.employee_id" v-model="selectedEmployeeIds" class="rounded text-primary-600 focus:ring-primary-500" />
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-secondary-900">{{ row.name }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ row.nip || '-' }} • {{ row.division }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-secondary-700">
                                    {{ row.joined_at }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-secondary-100 text-secondary-800">
                                        {{ row.months_tenure }} Bulan
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-medium text-secondary-900">
                                    {{ formatCurrency(row.basis) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="row.prorate_multiplier >= 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                    >
                                        {{ row.prorate_multiplier >= 1 ? 'Penuh (1x Gaji)' : `Prorata (${row.months_tenure}/12)` }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-emerald-600 text-base">
                                    {{ formatCurrency(row.thr_amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
