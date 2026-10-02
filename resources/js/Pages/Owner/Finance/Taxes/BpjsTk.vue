<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    jht_active: Boolean(props.settings?.jht_active),
    jht_company_percent: props.settings?.jht_company_percent ?? 3.70,
    jht_employee_percent: props.settings?.jht_employee_percent ?? 2.00,
    jkk_active: Boolean(props.settings?.jkk_active),
    jkk_risk_level: props.settings?.jkk_risk_level || 'Sangat Rendah',
    jkk_percent: props.settings?.jkk_percent ?? 0.24,
    jkm_active: Boolean(props.settings?.jkm_active),
    jkm_percent: props.settings?.jkm_percent ?? 0.30,
    jp_active: Boolean(props.settings?.jp_active),
    jp_company_percent: props.settings?.jp_company_percent ?? 2.00,
    jp_employee_percent: props.settings?.jp_employee_percent ?? 1.00,
    jp_maximum_wage: props.settings?.jp_maximum_wage ?? 10042300,
    effective_year: props.settings?.effective_year || new Date().getFullYear(),
    notes: props.settings?.notes || '',
})

const updateJkkRisk = (e) => {
    const val = e.target.value
    form.jkk_risk_level = val
    if (val === 'Sangat Rendah') form.jkk_percent = 0.24
    else if (val === 'Rendah') form.jkk_percent = 0.54
    else if (val === 'Sedang') form.jkk_percent = 0.89
    else if (val === 'Tinggi') form.jkk_percent = 1.27
    else if (val === 'Sangat Tinggi') form.jkk_percent = 1.74
}

const submit = () => {
    form.post(route('owner.finance.taxes.bpjs-tk.update'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan BPJS Ketenagakerjaan - Frans HRIS" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Pengaturan BPJS Ketenagakerjaan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Konfigurasi persentase tarif JHT, JKK, JKM, dan JP untuk kalkulasi payroll otomatis.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.taxes.pph21')">
                        <Button variant="secondary" size="md">PPh 21 & PTKP</Button>
                    </Link>
                    <Link :href="route('owner.finance.taxes.bpjs-kes')">
                        <Button variant="secondary" size="md">BPJS Kesehatan</Button>
                    </Link>
                </div>
            </div>

            <!-- Settings Form Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- JHT Section -->
                    <div class="p-5 rounded-xl border border-secondary-200 bg-secondary-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-secondary-900">Jaminan Hari Tua (JHT)</h3>
                                <p class="text-xs text-secondary-500">Standar nasional: 3.70% Perusahaan & 2.00% Karyawan</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.jht_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div v-if="form.jht_active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Ditanggung Perusahaan (%)</label>
                                <input type="number" step="0.01" v-model="form.jht_company_percent" class="input" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Dipotong Karyawan (%)</label>
                                <input type="number" step="0.01" v-model="form.jht_employee_percent" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- JKK Section -->
                    <div class="p-5 rounded-xl border border-secondary-200 bg-secondary-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-secondary-900">Jaminan Kecelakaan Kerja (JKK)</h3>
                                <p class="text-xs text-secondary-500">Ditanggung penuh oleh perusahaan berdasarkan tingkat risiko kerja</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.jkk_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div v-if="form.jkk_active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Tingkat Risiko Kerja</label>
                                <select :value="form.jkk_risk_level" @change="updateJkkRisk" class="input">
                                    <option value="Sangat Rendah">Sangat Rendah (0.24%)</option>
                                    <option value="Rendah">Rendah (0.54%)</option>
                                    <option value="Sedang">Sedang (0.89%)</option>
                                    <option value="Tinggi">Tinggi (1.27%)</option>
                                    <option value="Sangat Tinggi">Sangat Tinggi (1.74%)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Tarif Efektif JKK (%)</label>
                                <input type="number" step="0.01" v-model="form.jkk_percent" class="input" />
                            </div>
                        </div>
                    </div>

                    <!-- JKM Section -->
                    <div class="p-5 rounded-xl border border-secondary-200 bg-secondary-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-secondary-900">Jaminan Kematian (JKM)</h3>
                                <p class="text-xs text-secondary-500">Ditanggung penuh oleh perusahaan (Standar: 0.30%)</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.jkm_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div v-if="form.jkm_active" class="pt-2">
                            <label class="block text-xs font-semibold text-secondary-700 mb-1">Ditanggung Perusahaan (%)</label>
                            <input type="number" step="0.01" v-model="form.jkm_percent" class="input sm:w-1/2" />
                        </div>
                    </div>

                    <!-- JP Section -->
                    <div class="p-5 rounded-xl border border-secondary-200 bg-secondary-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-secondary-900">Jaminan Pensiun (JP)</h3>
                                <p class="text-xs text-secondary-500">Standar: 2.00% Perusahaan & 1.00% Karyawan dengan batas plafon upah maksimal</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.jp_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div v-if="form.jp_active" class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Perusahaan (%)</label>
                                <input type="number" step="0.01" v-model="form.jp_company_percent" class="input" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Karyawan (%)</label>
                                <input type="number" step="0.01" v-model="form.jp_employee_percent" class="input" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">Plafon Upah Maks (Rp)</label>
                                <input type="number" v-model="form.jp_maximum_wage" class="input" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan BPJS TK' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
