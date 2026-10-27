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
    early_clock_in_minutes: props.setting.early_clock_in_minutes ?? 60,
    camera_mode: props.setting.camera_mode ?? 'both',
    status_very_good: props.setting.status_very_good ?? 'Sangat Baik',
    status_good: props.setting.status_good ?? 'Baik',
    status_fair: props.setting.status_fair ?? 'Cukup',
    status_poor: props.setting.status_poor ?? 'Kurang',
});

const cameraOptions = [
    {
        value: 'both',
        title: 'Bebas (Depan / Belakang)',
        desc: 'Karyawan bebas memilih kamera depan untuk selfie atau belakang sesuai kondisi.',
        icon: 'camera-both',
    },
    {
        value: 'front',
        title: 'Kamera Depan Saja',
        desc: 'Wajib selfie wajah langsung untuk validasi visual kehadiran karyawan.',
        icon: 'camera-front',
    },
    {
        value: 'back',
        title: 'Kamera Belakang Saja',
        desc: 'Memotret lingkungan area kerja atau meja kerja sebagai bukti kehadiran.',
        icon: 'camera-back',
    },
];

const submit = () => {
    form.put(route('owner.management.attendance.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Presensi & Kehadiran" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Absensi</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Konfigurasi batas toleransi keterlambatan, metode verifikasi kamera, serta label evaluasi kedisiplinan karyawan.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Parameter Aturan Presensi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Parameter Aturan Presensi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tentukan kelonggaran batas waktu shift dan mode kamera yang diizinkan untuk clock-in/out.
                    </p>
                </div>

                <!-- Baris 1: Parameter Waktu (Grid 2 Kolom Berdampingan Simetris) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <!-- Toleransi Keterlambatan -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">
                            Toleransi Keterlambatan <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.late_tolerance_minutes"
                            type="number"
                            min="0"
                            max="120"
                            suffix="Menit"
                            placeholder="10"
                            :error="form.errors.late_tolerance_minutes"
                            required
                        />
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-600 leading-relaxed space-y-1">
                            <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Kompensasi Keterlambatan
                            </div>
                            <p>
                                Karyawan yang melakukan clock-in dalam rentang waktu toleransi setelah jam shift dimulai tidak akan dihitung sebagai terlambat.
                            </p>
                        </div>
                    </div>

                    <!-- Batas Awal Absen Masuk -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">
                            Batas Awal Absen Masuk <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            v-model="form.early_clock_in_minutes"
                            type="number"
                            min="0"
                            max="240"
                            suffix="Menit"
                            placeholder="60"
                            :error="form.errors.early_clock_in_minutes"
                            required
                        />
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-600 leading-relaxed space-y-1">
                            <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Jendela Buka Absen Masuk
                            </div>
                            <p>
                                Karyawan hanya diizinkan mulai melakukan clock-in paling cepat sekian menit sebelum jam shift dimulai.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Mode Kamera Presensi (Full-width dengan 3 Kartu Proporsional) -->
                <div class="space-y-2.5">
                    <label class="block text-sm font-semibold text-slate-700">
                        Mode Kamera Presensi <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                            v-for="opt in cameraOptions"
                            :key="opt.value"
                            @click="form.camera_mode = opt.value"
                            class="relative p-4 rounded-xl border text-left cursor-pointer transition-all duration-150 flex flex-col justify-between select-none"
                            :class="[
                                form.camera_mode === opt.value
                                    ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20 shadow-xs'
                                    : 'border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50/50'
                            ]"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                        :class="form.camera_mode === opt.value ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'"
                                    >
                                        <svg v-if="opt.value === 'front'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <svg v-else-if="opt.value === 'back'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <span
                                        class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                        :class="form.camera_mode === opt.value ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300'"
                                    >
                                        <span v-if="form.camera_mode === opt.value" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    </span>
                                </div>
                                <div class="text-sm font-bold text-slate-800 leading-snug">{{ opt.title }}</div>
                                <div class="text-xs text-slate-500 leading-normal">{{ opt.desc }}</div>
                            </div>
                        </div>
                    </div>
                    <span v-if="form.errors.camera_mode" class="text-xs text-rose-500 mt-1 block">
                        {{ form.errors.camera_mode }}
                    </span>
                </div>
            </div>

            <!-- Label Kategori Kedisiplinan Kehadiran -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Label Kategori Kedisiplinan Kehadiran</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kustomisasi penamaan tingkat kedisiplinan yang akan tampil pada laporan kinerja dan rekap presensi.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Sangat Baik -->
                    <div class="p-4 rounded-xl border border-emerald-200/80 bg-emerald-50/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                Tingkat 1 (≥ 95%)
                            </span>
                            <span class="text-[10px] uppercase font-bold text-emerald-600 bg-emerald-100/60 px-2 py-0.5 rounded-md">Optimal</span>
                        </div>
                        <label class="block text-xs font-semibold text-slate-700">Label Tampilan</label>
                        <Input
                            v-model="form.status_very_good"
                            placeholder="Sangat Baik"
                            :error="form.errors.status_very_good"
                            required
                        />
                    </div>

                    <!-- Baik -->
                    <div class="p-4 rounded-xl border border-blue-200/80 bg-blue-50/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                Tingkat 2 (85% - 94%)
                            </span>
                            <span class="text-[10px] uppercase font-bold text-blue-600 bg-blue-100/60 px-2 py-0.5 rounded-md">Standar</span>
                        </div>
                        <label class="block text-xs font-semibold text-slate-700">Label Tampilan</label>
                        <Input
                            v-model="form.status_good"
                            placeholder="Baik"
                            :error="form.errors.status_good"
                            required
                        />
                    </div>

                    <!-- Cukup -->
                    <div class="p-4 rounded-xl border border-amber-200/80 bg-amber-50/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Tingkat 3 (75% - 84%)
                            </span>
                            <span class="text-[10px] uppercase font-bold text-amber-600 bg-amber-100/60 px-2 py-0.5 rounded-md">Peringatan</span>
                        </div>
                        <label class="block text-xs font-semibold text-slate-700">Label Tampilan</label>
                        <Input
                            v-model="form.status_fair"
                            placeholder="Cukup"
                            :error="form.errors.status_fair"
                            required
                        />
                    </div>

                    <!-- Kurang -->
                    <div class="p-4 rounded-xl border border-rose-200/80 bg-rose-50/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-800">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                Tingkat 4 (< 75%)
                            </span>
                            <span class="text-[10px] uppercase font-bold text-rose-600 bg-rose-100/60 px-2 py-0.5 rounded-md">Kritis</span>
                        </div>
                        <label class="block text-xs font-semibold text-slate-700">Label Tampilan</label>
                        <Input
                            v-model="form.status_poor"
                            placeholder="Kurang"
                            :error="form.errors.status_poor"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Bottom Save Bar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                    class="shadow-sm shadow-emerald-600/20"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Pengaturan Absensi
                </Button>
            </div>
        </form>
    </div>
</template>
