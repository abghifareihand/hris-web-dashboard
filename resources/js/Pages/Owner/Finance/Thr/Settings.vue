<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    is_active: Boolean(props.settings?.is_active ?? true),
    min_months_tenure: props.settings?.min_months_tenure || 1,
    full_thr_months_tenure: props.settings?.full_thr_months_tenure || 12,
    include_fixed_allowance: Boolean(props.settings?.include_fixed_allowance ?? false),
})

const submit = () => {
    form.post(route('owner.finance.thr.settings.store'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan Kebijakan THR - Frans HRIS" />

        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Kebijakan Tunjangan Hari Raya (THR)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Atur syarat minimum masa kerja dan komponen dasar perhitungan THR perusahaan.
                    </p>
                </div>
                <Link :href="route('owner.finance.thr.index')">
                    <Button variant="secondary" size="md">Kembali</Button>
                </Link>
            </div>

            <!-- Settings Form Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="p-4 rounded-xl border border-secondary-200 bg-secondary-50/50 flex items-center justify-between">
                        <div>
                            <span class="block text-sm font-bold text-secondary-900">Aktifkan Modul THR</span>
                            <span class="block text-xs text-secondary-500 mt-0.5">Izinkan pembuatan kalkulasi dan distribusi THR.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                            <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Syarat Minimal Masa Kerja Berhak Menerima THR (Bulan) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="form.min_months_tenure"
                            min="1"
                            class="input"
                            required
                        />
                        <p class="text-xs text-secondary-400 mt-1">
                            Standar Permenaker: Karyawan yang telah bekerja minimal 1 bulan terus-menerus berhak menerima THR secara prorata.
                        </p>
                        <span v-if="form.errors.min_months_tenure" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.min_months_tenure }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Masa Kerja untuk Mendapatkan THR Penuh (Bulan) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="form.full_thr_months_tenure"
                            min="1"
                            class="input"
                            required
                        />
                        <p class="text-xs text-secondary-400 mt-1">
                            Masa kerja 12 bulan atau lebih mendapatkan 1x upah sebulan penuh.
                        </p>
                        <span v-if="form.errors.full_thr_months_tenure" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.full_thr_months_tenure }}
                        </span>
                    </div>

                    <div class="p-4 rounded-xl border border-secondary-200 bg-secondary-50/50 flex items-center justify-between">
                        <div>
                            <span class="block text-sm font-bold text-secondary-900">Sertakan Tunjangan Tetap (Fixed Allowance)</span>
                            <span class="block text-xs text-secondary-500 mt-0.5">Jika aktif, basis hitung THR = Gaji Pokok + Tunjangan Tetap.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.include_fixed_allowance" class="sr-only peer" />
                            <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Link :href="route('owner.finance.thr.index')">
                            <Button variant="secondary" type="button">Batal</Button>
                        </Link>
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan THR' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
