<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Checkbox from '@/Components/UI/Checkbox.vue'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    is_active: Boolean(props.settings?.is_active ?? true),
    min_months_tenure: Number(props.settings?.min_months_tenure || 1),
    full_thr_months_tenure: Number(props.settings?.full_thr_months_tenure || 12),
    include_fixed_allowance: Boolean(props.settings?.include_fixed_allowance ?? false),
})

const submit = () => {
    form.post(route('owner.finance.thr.settings.store'))
}

const toggleFixedAllowance = () => {
    if (form.is_active) {
        form.include_fixed_allowance = !form.include_fixed_allowance
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan Kebijakan THR - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kebijakan Tunjangan Hari Raya (THR)</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Atur syarat minimum masa kerja dan komponen dasar perhitungan THR perusahaan.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Permenaker No. 6 Tahun 2016
                    </span>
                </div>
            </div>

            <!-- Form Pengaturan Kebijakan THR -->
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Settings Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Konfigurasi Modul THR</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Izinkan pembuatan kalkulasi dan distribusi THR bagi karyawan</p>
                        </div>
                        <Checkbox v-model="form.is_active" label="Aktifkan Modul THR" />
                    </div>

                    <!-- Card Body -->
                    <div
                        class="p-6 space-y-6 transition-all duration-200"
                        :class="{ 'opacity-40 pointer-events-none select-none': !form.is_active }"
                    >
                        <!-- Grid 2 Kolom: Syarat Masa Kerja -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <Input
                                type="number"
                                min="1"
                                v-model="form.min_months_tenure"
                                label="Syarat Minimal Masa Kerja (Bulan)"
                                :disabled="!form.is_active"
                                placeholder="1"
                                help="Standar Permenaker: Karyawan yang telah bekerja minimal 1 bulan terus-menerus berhak menerima THR secara prorata."
                                :error="form.errors.min_months_tenure"
                                required
                            />

                            <Input
                                type="number"
                                min="1"
                                v-model="form.full_thr_months_tenure"
                                label="Masa Kerja untuk THR Penuh (Bulan)"
                                :disabled="!form.is_active"
                                placeholder="12"
                                help="Masa kerja 12 bulan atau lebih mendapatkan 1x upah sebulan penuh."
                                :error="form.errors.full_thr_months_tenure"
                                required
                            />
                        </div>

                        <!-- Checkbox Sertakan Tunjangan Tetap (Di Sebelah Kiri) -->
                        <div
                            class="p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer select-none"
                            :class="form.include_fixed_allowance 
                                ? 'bg-emerald-50/40 border-emerald-200/90 shadow-2xs' 
                                : 'bg-slate-50/60 border-slate-200/80'"
                            @click="toggleFixedAllowance"
                        >
                            <div class="flex items-start gap-3">
                                <Checkbox
                                    v-model="form.include_fixed_allowance"
                                    :disabled="!form.is_active"
                                    id="include_fixed_allowance"
                                    class="mt-0.5 shrink-0"
                                    @click.stop
                                />
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        Sertakan Tunjangan Tetap (Fixed Allowance)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Jika dicentang, dasar perhitungan THR = <strong class="text-slate-800 font-semibold">Gaji Pokok + Tunjangan Tetap</strong>. Bila tidak, sistem hanya menggunakan Gaji Pokok.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Simulasi Prorata Otomatis (Simple Sesuai BpjsTk.vue) -->
                        <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-3.5 text-xs text-amber-950 space-y-2 leading-relaxed">
                            <div>
                                <strong class="font-bold text-amber-950">Simulasi Prorata Otomatis:</strong>
                                Jika karyawan memiliki masa kerja di bawah batas penuh (misal: bekerja 6 bulan), sistem otomatis menghitung menggunakan rumus <strong class="font-semibold text-amber-950">(Masa Kerja / 12) × Upah 1 Bulan</strong>.
                            </div>
                            <ul class="space-y-1.5 text-amber-900">
                                <li class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                    <span><strong class="font-semibold text-amber-950">Contoh 1 (Hanya Gaji Pokok Rp 6Jt):</strong> (6/12) × Rp 6.000.000 = Rp 3.000.000</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-700 mt-1.5 shrink-0"></span>
                                    <span><strong class="font-semibold text-amber-950">Contoh 2 (Ditambah Tunjangan Rp 1Jt):</strong> (6/12) × Rp 7.000.000 = Rp 3.500.000</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan Berada di Luar Card (Di Bawah) Sesuai BpjsTk.vue -->
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
        </div>
    </AppLayout>
</template>
