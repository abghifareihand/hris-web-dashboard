<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    setting: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    late_tolerance_minutes: props.setting.late_tolerance_minutes ?? 10,
    camera_mode: props.setting.camera_mode ?? 'both',
    status_very_good: props.setting.status_very_good ?? 'Sangat Baik',
    status_good: props.setting.status_good ?? 'Baik',
    status_fair: props.setting.status_fair ?? 'Cukup',
    status_poor: props.setting.status_poor ?? 'Kurang',
});

const submit = () => {
    form.put(route('owner.management.attendance.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Presensi & Kehadiran" />

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Absensi & Presensi</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Atur toleransi batas waktu keterlambatan, kamera verifikasi wajah, serta label indikator kedisiplinan karyawan.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Parameter Toleransi & Kamera -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                    Parameter Aturan Presensi
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Toleransi Keterlambatan -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Toleransi Keterlambatan (Menit) <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.late_tolerance_minutes"
                            type="number"
                            min="0"
                            max="120"
                            placeholder="10"
                            :error="form.errors.late_tolerance_minutes"
                            required
                        />
                        <p class="text-xs text-slate-400 mt-1.5">
                            Batas waktu kompensasi keterlambatan setelah jam shift dimulai tanpa dianggap terlambat.
                        </p>
                    </div>

                    <!-- Mode Kamera Presensi -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Mode Kamera Presensi <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.camera_mode"
                            class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                        >
                            <option value="front">Kamera Depan Saja (Selfie Face)</option>
                            <option value="back">Kamera Belakang Saja (Environment)</option>
                            <option value="both">Bebas (Kamera Depan atau Belakang)</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1.5">
                            Karyawan wajib mengunggah foto selfie saat melakukan clock-in dan clock-out di mobile/web.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Label Evaluasi Disiplin -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                    Label Kategori Kedisiplinan Kehadiran
                </h2>
                <p class="text-xs text-slate-500 -mt-2">
                    Teks deskripsi kustom yang muncul pada kartu laporan kehadiran masing-masing karyawan.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Tingkat Sangat Baik (Very Good)
                        </label>
                        <Input v-model="form.status_very_good" :error="form.errors.status_very_good" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            Tingkat Baik (Good)
                        </label>
                        <Input v-model="form.status_good" :error="form.errors.status_good" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            Tingkat Cukup (Fair)
                        </label>
                        <Input v-model="form.status_fair" :error="form.errors.status_fair" required />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            Tingkat Kurang (Poor)
                        </label>
                        <Input v-model="form.status_poor" :error="form.errors.status_poor" required />
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <Button variant="primary" type="submit" :loading="form.processing">
                    Simpan Pengaturan Absensi
                </Button>
            </div>
        </form>
    </div>
</template>
