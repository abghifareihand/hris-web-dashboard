<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import Checkbox from '@/Components/UI/Checkbox.vue'
import Textarea from '@/Components/UI/Textarea.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    jht_active: Boolean(props.settings?.jht_active ?? true),
    jht_company_percent: Number(props.settings?.jht_company_percent ?? 3.70),
    jht_employee_percent: Number(props.settings?.jht_employee_percent ?? 2.00),
    jkk_active: Boolean(props.settings?.jkk_active ?? true),
    jkk_risk_level: props.settings?.jkk_risk_level || 'Sangat Rendah',
    jkk_percent: Number(props.settings?.jkk_percent ?? 0.24),
    jkm_active: Boolean(props.settings?.jkm_active ?? true),
    jkm_percent: Number(props.settings?.jkm_percent ?? 0.30),
    jp_active: Boolean(props.settings?.jp_active ?? true),
    jp_company_percent: Number(props.settings?.jp_company_percent ?? 2.00),
    jp_employee_percent: Number(props.settings?.jp_employee_percent ?? 1.00),
    jp_maximum_wage: Number(props.settings?.jp_maximum_wage ?? 10042300),
    effective_year: props.settings?.effective_year || new Date().getFullYear(),
    notes: props.settings?.notes || 'Konfigurasi BPJS Ketenagakerjaan sesuai PP No. 37 Tahun 2021. Tingkat risiko JKK "Sangat Rendah" untuk perkantoran. Batas maksimal JP mengikuti ketentuan BPJS TK terbaru.',
})

// Options untuk Dropdown Tingkat Risiko JKK
const jkkOptions = [
    { value: 'Sangat Rendah', label: 'Sangat Rendah - 0.24%' },
    { value: 'Rendah', label: 'Rendah - 0.54%' },
    { value: 'Sedang', label: 'Sedang - 0.89%' },
    { value: 'Tinggi', label: 'Tinggi - 1.27%' },
    { value: 'Sangat Tinggi', label: 'Sangat Tinggi - 1.74%' },
]

const onJkkChange = (val) => {
    form.jkk_risk_level = val
    if (val === 'Sangat Rendah') form.jkk_percent = 0.24
    else if (val === 'Rendah') form.jkk_percent = 0.54
    else if (val === 'Sedang') form.jkk_percent = 0.89
    else if (val === 'Tinggi') form.jkk_percent = 1.27
    else if (val === 'Sangat Tinggi') form.jkk_percent = 1.74
}

watch(() => form.jkk_risk_level, (val) => {
    onJkkChange(val)
})

// Format currency helper
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}

// Computed Statistik Ringkasan Atas
const totalCompanyPercent = computed(() => {
    let total = 0
    if (form.jht_active) total += Number(form.jht_company_percent || 0)
    if (form.jkk_active) total += Number(form.jkk_percent || 0)
    if (form.jkm_active) total += Number(form.jkm_percent || 0)
    if (form.jp_active) total += Number(form.jp_company_percent || 0)
    return total.toFixed(2)
})

const totalEmployeePercent = computed(() => {
    let total = 0
    if (form.jht_active) total += Number(form.jht_employee_percent || 0)
    if (form.jp_active) total += Number(form.jp_employee_percent || 0)
    return total.toFixed(2)
})

// ==========================================
// KALKULATOR SIMULASI BPJS KETENAGAKERJAAN
// ==========================================
const simWage = ref(5000000) // Default 5.000.000
const activeSimWage = ref(5000000)
const showResults = ref(false)

const handleCalculate = () => {
    activeSimWage.value = Math.max(0, Number(simWage.value) || 0)
    showResults.value = true
}

const simResult = computed(() => {
    const wage = activeSimWage.value
    const jpCap = Math.max(0, Number(form.jp_maximum_wage) || 10042300)
    const jpBasis = Math.min(wage, jpCap)

    // JHT
    const jhtCompany = form.jht_active ? Math.round(wage * (Number(form.jht_company_percent || 0) / 100)) : 0
    const jhtEmployee = form.jht_active ? Math.round(wage * (Number(form.jht_employee_percent || 0) / 100)) : 0

    // JKK
    const jkkCompany = form.jkk_active ? Math.round(wage * (Number(form.jkk_percent || 0) / 100)) : 0

    // JKM
    const jkmCompany = form.jkm_active ? Math.round(wage * (Number(form.jkm_percent || 0) / 100)) : 0

    // JP
    const jpCompany = form.jp_active ? Math.round(jpBasis * (Number(form.jp_company_percent || 0) / 100)) : 0
    const jpEmployee = form.jp_active ? Math.round(jpBasis * (Number(form.jp_employee_percent || 0) / 100)) : 0

    // Total
    const totalCompany = jhtCompany + jkkCompany + jkmCompany + jpCompany
    const totalEmployee = jhtEmployee + jpEmployee

    return {
        wage,
        jhtCompany,
        jhtEmployee,
        jkkCompany,
        jkmCompany,
        jpCompany,
        jpEmployee,
        totalCompany,
        totalEmployee,
    }
})

const submit = () => {
    form.post(route('owner.finance.taxes.bpjs-tk.update'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan BPJS Ketenagakerjaan - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan BPJS Ketenagakerjaan</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola tarif iuran dan ketentuan BPJS Ketenagakerjaan tingkat perusahaan secara global.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        PP No. 44, 45, & 46 Tahun 2015
                    </span>
                </div>
            </div>

            <!-- 4 Summary / Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Perusahaan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL PERUSAHAAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">{{ totalCompanyPercent }}%</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">JHT + JKK + JKM + JP</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Total Karyawan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL KARYAWAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">{{ totalEmployeePercent }}%</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">JHT + JP</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Tingkat Risiko JKK -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TINGKAT RISIKO JKK</div>
                        <div class="text-xl font-bold text-slate-900 mt-1 truncate">{{ form.jkk_active ? form.jkk_risk_level : 'Nonaktif' }}</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">{{ form.jkk_active ? `${form.jkk_percent}% ditanggung perusahaan` : 'Program nonaktif' }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Batas Upah JP -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">BATAS UPAH JP</div>
                        <div class="text-xl font-bold text-slate-900 mt-1 truncate">{{ formatCurrency(form.jp_maximum_wage) }}</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">Maksimal dasar iuran JP</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Form Pengaturan BPJS Ketenagakerjaan -->
            <form @submit.prevent="submit" class="space-y-5">
                <!-- 4 Program BPJS TK Grid 2 Kolom -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- 1. JHT (Jaminan Hari Tua) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900">JHT (Jaminan Hari Tua)</h2>
                            <Checkbox v-model="form.jht_active" label="Aktif" />
                        </div>

                        <div class="p-6">
                            <!-- Section Content (Pudar saat kondisi tidak aktif) -->
                            <div 
                                class="space-y-4 transition-all duration-200"
                                :class="{ 'opacity-40 pointer-events-none select-none': !form.jht_active }"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.jht_company_percent"
                                        label="Perusahaan (%)"
                                        :disabled="!form.jht_active"
                                        placeholder="3.7"
                                    />
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.jht_employee_percent"
                                        label="Karyawan (%)"
                                        :disabled="!form.jht_active"
                                        placeholder="2"
                                    />
                                </div>

                                <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-3.5 text-xs text-amber-950 leading-relaxed">
                                    <strong class="font-bold text-amber-950">Catatan:</strong> Total iuran JHT adalah <strong class="font-semibold text-amber-950">5.70%</strong> dari upah dengan pembagian <strong class="font-semibold text-amber-950">3.70%</strong> ditanggung perusahaan dan <strong class="font-semibold text-amber-950">2.00%</strong> dipotong dari karyawan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. JKK (Jaminan Kecelakaan Kerja) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900">JKK (Jaminan Kecelakaan Kerja)</h2>
                            <Checkbox v-model="form.jkk_active" label="Aktif" />
                        </div>

                        <div class="p-6">
                            <!-- Section Content (Pudar saat kondisi tidak aktif) -->
                            <div 
                                class="space-y-4 transition-all duration-200"
                                :class="{ 'opacity-40 pointer-events-none select-none': !form.jkk_active }"
                            >
                                <div>
                                    <Select
                                        v-model="form.jkk_risk_level"
                                        :options="jkkOptions"
                                        label="Tingkat Risiko"
                                        :disabled="!form.jkk_active"
                                        @change="onJkkChange"
                                    />
                                </div>

                                <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-3.5 text-xs text-amber-950 space-y-2">
                                    <div class="font-bold text-amber-950">Kategori Risiko:</div>
                                    <ul class="space-y-1.5 text-amber-900">
                                        <li class="flex items-start gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                            <span><strong class="font-semibold text-amber-950">Sangat Rendah:</strong> Perkantoran, jasa konsultan</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                            <span><strong class="font-semibold text-amber-950">Rendah:</strong> Perdagangan, restoran</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                            <span><strong class="font-semibold text-amber-950">Sedang:</strong> Manufaktur ringan, pertanian</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                            <span><strong class="font-semibold text-amber-950">Tinggi:</strong> Konstruksi, transportasi</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                            <span><strong class="font-semibold text-amber-950">Sangat Tinggi:</strong> Pertambangan, pengeboran</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. JKM (Jaminan Kematian) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900">JKM (Jaminan Kematian)</h2>
                            <Checkbox v-model="form.jkm_active" label="Aktif" />
                        </div>

                        <div class="p-6">
                            <!-- Section Content (Pudar saat kondisi tidak aktif) -->
                            <div 
                                class="space-y-4 transition-all duration-200"
                                :class="{ 'opacity-40 pointer-events-none select-none': !form.jkm_active }"
                            >
                                <div>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.jkm_percent"
                                        label="Tarif Perusahaan (%)"
                                        :disabled="!form.jkm_active"
                                        placeholder="0.3"
                                        help="Seluruhnya ditanggung perusahaan"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. JP (Jaminan Pensiun) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900">JP (Jaminan Pensiun)</h2>
                            <Checkbox v-model="form.jp_active" label="Aktif" />
                        </div>

                        <div class="p-6">
                            <!-- Section Content (Pudar saat kondisi tidak aktif) -->
                            <div 
                                class="space-y-4 transition-all duration-200"
                                :class="{ 'opacity-40 pointer-events-none select-none': !form.jp_active }"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.jp_company_percent"
                                        label="Perusahaan (%)"
                                        :disabled="!form.jp_active"
                                        placeholder="2"
                                    />
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.jp_employee_percent"
                                        label="Karyawan (%)"
                                        :disabled="!form.jp_active"
                                        placeholder="1"
                                    />
                                </div>

                                <div>
                                    <Input
                                        currency
                                        v-model="form.jp_maximum_wage"
                                        label="Batas Maksimum Upah"
                                        :disabled="!form.jp_active"
                                        placeholder="10.042.300"
                                        help="Upah di atas batas ini tetap dihitung berdasarkan batas maksimum"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Pengaturan Tambahan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">Pengaturan Tambahan</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <Input
                                type="text"
                                v-model="form.effective_year"
                                label="Tahun Efektif"
                                placeholder="2026"
                            />
                            <Textarea
                                v-model="form.notes"
                                label="Catatan"
                                :rows="3"
                                placeholder="Konfigurasi BPJS Ketenagakerjaan..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan Berada di Luar Card (Di Bawah) Sesuai Permintaan User -->
                <div class="flex items-center justify-end pt-1">
                    <Button
                        variant="primary"
                        size="md"
                        type="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>

            <!-- ============================================================== -->
            <!-- SECTION: KALKULATOR SIMULASI BPJS KETENAGAKERJAAN (Gambar 1 & 2) -->
            <!-- ============================================================== -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">Kalkulator Simulasi BPJS Ketenagakerjaan</h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Input Gaji Pokok & Tombol Hitung Sesuai Gambar 1 & 2 -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                        <div class="w-full sm:w-64">
                            <Input
                                currency
                                v-model="simWage"
                                label="Gaji Pokok"
                                placeholder="5.000.000"
                                @keydown.enter="handleCalculate"
                            />
                        </div>
                        <Button
                            variant="primary"
                            size="md"
                            type="button"
                            @click="handleCalculate"
                            class="flex items-center gap-2 mb-0.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Hitung
                        </Button>
                    </div>

                    <!-- 8 Kotak Hasil Simulasi Informatif Sesuai Pola BPJS Kes -->
                    <div v-if="showResults" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                        <!-- 1. JHT Perusahaan -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-blue-600">
                                JHT Perusahaan {{ form.jht_active ? `(${form.jht_company_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatCurrency(simResult.jhtCompany) }}</div>
                            <div class="text-xs text-blue-500/80 mt-1">
                                {{ form.jht_active ? 'Ditanggung perusahaan' : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 2. JHT Karyawan -->
                        <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-amber-600">
                                JHT Karyawan {{ form.jht_active ? `(${form.jht_employee_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-slate-800 mt-1">{{ formatCurrency(simResult.jhtEmployee) }}</div>
                            <div class="text-xs text-amber-600/80 mt-1">
                                {{ form.jht_active ? 'Dipotong dari gaji' : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 3. JKK -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-blue-600">
                                JKK {{ form.jkk_active ? `(${form.jkk_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatCurrency(simResult.jkkCompany) }}</div>
                            <div class="text-xs text-blue-500/80 mt-1">
                                {{ form.jkk_active ? `Ditanggung perusahaan (${form.jkk_risk_level})` : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 4. JKM -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-blue-600">
                                JKM {{ form.jkm_active ? `(${form.jkm_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatCurrency(simResult.jkmCompany) }}</div>
                            <div class="text-xs text-blue-500/80 mt-1">
                                {{ form.jkm_active ? 'Ditanggung perusahaan' : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 5. JP Perusahaan -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-blue-600">
                                JP Perusahaan {{ form.jp_active ? `(${form.jp_company_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatCurrency(simResult.jpCompany) }}</div>
                            <div class="text-xs text-blue-500/80 mt-1">
                                {{ form.jp_active ? `Ditanggung perusahaan (Maks ${formatCurrency(form.jp_maximum_wage)})` : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 6. JP Karyawan -->
                        <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-amber-600">
                                JP Karyawan {{ form.jp_active ? `(${form.jp_employee_percent}%)` : '(Nonaktif)' }}
                            </div>
                            <div class="text-lg font-bold text-slate-800 mt-1">{{ formatCurrency(simResult.jpEmployee) }}</div>
                            <div class="text-xs text-amber-600/80 mt-1">
                                {{ form.jp_active ? `Dipotong dari gaji (Maks ${formatCurrency(form.jp_maximum_wage)})` : 'Program nonaktif' }}
                            </div>
                        </div>

                        <!-- 7. Total Perusahaan -->
                        <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-emerald-600">
                                Total Perusahaan ({{ totalCompanyPercent }}%)
                            </div>
                            <div class="text-lg font-bold text-emerald-800 mt-1">{{ formatCurrency(simResult.totalCompany) }}</div>
                            <div class="text-xs text-emerald-600/80 mt-1">
                                JHT + JKK + JKM + JP
                            </div>
                        </div>

                        <!-- 8. Total Karyawan -->
                        <div class="bg-rose-50/50 border border-rose-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-rose-600">
                                Total Karyawan ({{ totalEmployeePercent }}%)
                            </div>
                            <div class="text-lg font-bold text-rose-800 mt-1">{{ formatCurrency(simResult.totalEmployee) }}</div>
                            <div class="text-xs text-rose-600/80 mt-1">
                                JHT + JP dipotong dari gaji
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
