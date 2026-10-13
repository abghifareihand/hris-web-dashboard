<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Checkbox from '@/Components/UI/Checkbox.vue'
import Textarea from '@/Components/UI/Textarea.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    is_active: Boolean(props.settings?.is_active ?? true),
    company_percent: Number(props.settings?.company_percent ?? 4.00),
    employee_percent: Number(props.settings?.employee_percent ?? 1.00),
    minimum_wage: Number(props.settings?.minimum_wage ?? 2500000),
    maximum_wage: Number(props.settings?.maximum_wage ?? 12000000),
    effective_year: props.settings?.effective_year || new Date().getFullYear(),
    notes: props.settings?.notes || 'Konfigurasi BPJS Kesehatan sesuai Perpres No. 64 Tahun 2020. Berlaku untuk seluruh karyawan tetap dan kontrak.',
})

// Format currency helper
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}

// Total persen iuran (Perusahaan + Karyawan)
const totalPercent = computed(() => {
    return (Number(form.company_percent || 0) + Number(form.employee_percent || 0)).toFixed(2)
})

// ==========================================
// KALKULATOR SIMULASI BPJS KESEHATAN
// ==========================================
const simWage = ref(5000000) // Default Rp 5.000.000
const activeSimWage = ref(5000000)
const showResults = ref(false)

const handleCalculate = () => {
    activeSimWage.value = Math.max(0, Number(simWage.value) || 0)
    showResults.value = true
}

const simResult = computed(() => {
    const rawWage = activeSimWage.value
    const minWage = Math.max(0, Number(form.minimum_wage) || 0)
    const maxWage = Math.max(0, Number(form.maximum_wage) || 12000000)

    // Tentukan dasar pengenaan iuran (clamp antara min dan max)
    let wageBasis = rawWage
    if (minWage > 0 && wageBasis < minWage) {
        wageBasis = minWage
    }
    if (maxWage > 0 && wageBasis > maxWage) {
        wageBasis = maxWage
    }

    const companyPercent = Number(form.company_percent || 0)
    const employeePercent = Number(form.employee_percent || 0)

    const companyAmount = form.is_active ? Math.round(wageBasis * (companyPercent / 100)) : 0
    const employeeAmount = form.is_active ? Math.round(wageBasis * (employeePercent / 100)) : 0
    const totalAmount = companyAmount + employeeAmount

    return {
        rawWage,
        wageBasis,
        companyPercent,
        employeePercent,
        companyAmount,
        employeeAmount,
        totalAmount,
    }
})

const submit = () => {
    form.post(route('owner.finance.taxes.bpjs-kes.update'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan BPJS Kesehatan - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan BPJS Kesehatan</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola tarif iuran dan batas upah BPJS Kesehatan tingkat perusahaan secara global.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Perpres No. 64 Tahun 2020
                    </span>
                </div>
            </div>

            <!-- 4 Summary / Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Iuran Perusahaan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">IURAN PERUSAHAAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">{{ Number(form.company_percent || 0).toFixed(2) }}%</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">Ditanggung perusahaan</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Iuran Karyawan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">IURAN KARYAWAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">{{ Number(form.employee_percent || 0).toFixed(2) }}%</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">Dipotong dari gaji</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Total Iuran -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL IURAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">{{ totalPercent }}%</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">Total keseluruhan iuran</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Batas Upah Maks -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">BATAS UPAH MAKS</div>
                        <div class="text-xl font-bold text-slate-900 mt-1 truncate">{{ formatCurrency(form.maximum_wage) }}</div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">Batas dasar iuran tertinggi</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Form Pengaturan BPJS Kesehatan -->
            <form @submit.prevent="submit" class="space-y-5">
                <!-- 2 Kolom Grid Form: Tarif & Batas Dasar Upah -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- 1. Tarif Iuran BPJS Kesehatan -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-900">Tarif Iuran BPJS Kesehatan</h2>
                            <Checkbox v-model="form.is_active" label="Aktif" />
                        </div>

                        <div class="p-6">
                            <!-- Section Content (Pudar saat kondisi tidak aktif) -->
                            <div 
                                class="space-y-4 transition-all duration-200"
                                :class="{ 'opacity-40 pointer-events-none select-none': !form.is_active }"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.company_percent"
                                        label="Perusahaan (%)"
                                        :disabled="!form.is_active"
                                        placeholder="4"
                                    />
                                    <Input
                                        type="number"
                                        step="0.01"
                                        v-model="form.employee_percent"
                                        label="Karyawan (%)"
                                        :disabled="!form.is_active"
                                        placeholder="1"
                                    />
                                </div>

                                <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-3.5 text-xs text-amber-950 leading-relaxed">
                                    <strong class="font-bold text-amber-950">Catatan:</strong> Sesuai aturan BPJS Kesehatan, total iuran adalah <strong class="font-semibold text-amber-950">5%</strong> dari gaji dengan pembagian <strong class="font-semibold text-amber-950">4%</strong> ditanggung perusahaan dan <strong class="font-semibold text-amber-950">1%</strong> ditanggung karyawan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Batas Dasar Upah -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                        <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                            <h2 class="text-base font-bold text-slate-900">Batas Dasar Upah</h2>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Input
                                    currency
                                    v-model="form.minimum_wage"
                                    label="Upah Minimum"
                                    placeholder="2.500.000"
                                />
                                <Input
                                    currency
                                    v-model="form.maximum_wage"
                                    label="Upah Maksimum"
                                    placeholder="12.000.000"
                                />
                            </div>

                            <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-3.5 text-xs text-amber-950 leading-relaxed">
                                <strong class="font-bold text-amber-950">Catatan:</strong> Upah di bawah minimum akan dihitung berdasarkan batas minimum, dan upah di atas maksimum akan dihitung berdasarkan batas maksimum.
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
                                placeholder="Konfigurasi BPJS Kesehatan sesuai Perpres No. 64 Tahun 2020. Berlaku untuk seluruh karyawan tetap dan kontrak."
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
            <!-- SECTION: KALKULATOR SIMULASI BPJS KESEHATAN -->
            <!-- ============================================================== -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">Kalkulator Simulasi BPJS Kesehatan</h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Input Gaji Pokok & Tombol Hitung Sesuai Pola BPJS TK -->
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

                    <!-- Kotak Hasil Simulasi BPJS Kesehatan -->
                    <div v-if="showResults" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                        <!-- 1. Dasar Pengenaan Upah -->
                        <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-slate-600">Dasar Pengenaan Upah</div>
                            <div class="text-lg font-bold text-slate-900 mt-1">{{ formatCurrency(simResult.wageBasis) }}</div>
                            <div class="text-xs text-slate-400 mt-1">Disesuaikan batas min/maks</div>
                        </div>

                        <!-- 2. Iuran Perusahaan -->
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-blue-600">Iuran Perusahaan ({{ simResult.companyPercent }}%)</div>
                            <div class="text-lg font-bold text-blue-700 mt-1">{{ formatCurrency(simResult.companyAmount) }}</div>
                            <div class="text-xs text-blue-500/80 mt-1">Ditanggung perusahaan</div>
                        </div>

                        <!-- 3. Iuran Karyawan -->
                        <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-amber-600">Iuran Karyawan ({{ simResult.employeePercent }}%)</div>
                            <div class="text-lg font-bold text-slate-800 mt-1">{{ formatCurrency(simResult.employeeAmount) }}</div>
                            <div class="text-xs text-amber-600/80 mt-1">Dipotong dari gaji</div>
                        </div>

                        <!-- 4. Total Iuran -->
                        <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4 transition">
                            <div class="text-xs font-semibold text-emerald-600">Total Iuran BPJS Kes</div>
                            <div class="text-lg font-bold text-emerald-800 mt-1">{{ formatCurrency(simResult.totalAmount) }}</div>
                            <div class="text-xs text-emerald-600/80 mt-1">Perusahaan + Karyawan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
