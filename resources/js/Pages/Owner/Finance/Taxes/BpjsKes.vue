<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    is_active: Boolean(props.settings?.is_active ?? true),
    company_percent: props.settings?.company_percent ?? 4.00,
    employee_percent: props.settings?.employee_percent ?? 1.00,
    minimum_wage: props.settings?.minimum_wage ?? 2500000,
    maximum_wage: props.settings?.maximum_wage ?? 12000000,
    effective_year: props.settings?.effective_year || new Date().getFullYear(),
    notes: props.settings?.notes || '',
})

const submit = () => {
    form.post(route('owner.finance.taxes.bpjs-kes.update'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan BPJS Kesehatan - Frans HRIS" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Pengaturan BPJS Kesehatan</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Konfigurasi persentase iuran jaminan kesehatan dan batas upah minimum / maksimum.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.taxes.pph21')">
                        <Button variant="secondary" size="md">PPh 21 & PTKP</Button>
                    </Link>
                    <Link :href="route('owner.finance.taxes.bpjs-tk')">
                        <Button variant="secondary" size="md">BPJS Ketenagakerjaan</Button>
                    </Link>
                </div>
            </div>

            <!-- Settings Form Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="p-5 rounded-xl border border-secondary-200 bg-secondary-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-secondary-900">Status Fasilitas BPJS Kesehatan</h3>
                                <p class="text-xs text-secondary-500">Aktifkan agar pemotongan dan tunjangan BPJS otomatis dihitung dalam slip gaji</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div v-if="form.is_active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-secondary-200/60">
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">
                                    Ditanggung Perusahaan (%) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" v-model="form.company_percent" class="input" required />
                                <p class="text-xs text-secondary-400 mt-1">Standar regulasi: 4.00%</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">
                                    Dipotong dari Karyawan (%) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" v-model="form.employee_percent" class="input" required />
                                <p class="text-xs text-secondary-400 mt-1">Standar regulasi: 1.00%</p>
                            </div>
                        </div>

                        <div v-if="form.is_active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">
                                    Batas Upah Minimum / UMK (Rp)
                                </label>
                                <input type="number" v-model="form.minimum_wage" class="input" />
                                <p class="text-xs text-secondary-400 mt-1">Dasar perhitungan terendah iuran BPJS Kes</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-secondary-700 mb-1">
                                    Batas Plafon Upah Maksimal (Rp)
                                </label>
                                <input type="number" v-model="form.maximum_wage" class="input" />
                                <p class="text-xs text-secondary-400 mt-1">Standar nasional: Rp 12.000.000</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan BPJS Kes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
