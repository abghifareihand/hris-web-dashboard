<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    setting: Object,
});

const form = useForm({
    default_payroll_cycle: props.setting?.default_payroll_cycle || 'monthly',
    monthly_working_days: props.setting?.monthly_working_days ?? 25,
    weekly_working_days: props.setting?.weekly_working_days ?? 6,
    notes: props.setting?.notes || '',
});

const cycleOptions = [
    {
        value: 'monthly',
        title: 'Bulanan',
        desc: 'Gaji tetap per bulan. Pemotongan absen dihitung proporsional berbasis hari kerja efektif.',
    },
    {
        value: 'weekly',
        title: 'Mingguan',
        desc: 'Upah mingguan (standar 6 hari kerja). Pembayaran dilakukan rutin setiap pekan.',
    },
    {
        value: 'daily',
        title: 'Harian',
        desc: 'Murni upah harian (Hari Masuk × Upah/Hari). Tanpa gaji pokok bulanan, tidak masuk tidak dibayar.',
    },
];

const submit = () => {
    form.post(route('owner.finance.payrolls.settings.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Kebijakan Penggajian - Frans HRIS" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Penggajian</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola kebijakan siklus penggajian perusahaan (Bulanan, Mingguan, Harian) dan pembagi hari kerja untuk pemotongan ketidakhadiran.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. Pilihan Periode Penggajian Default -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Siklus Periode Penggajian Default</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pilih standar periode pembayaran upah yang umum diterapkan di perusahaan. Anda tetap dapat mengatur periode per karyawan di data pegawai.
                    </p>
                </div>

                <!-- 3 Card Radio Options (Sama persis dengan gaya Mode Kamera Presensi) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="opt in cycleOptions"
                        :key="opt.value"
                        @click="form.default_payroll_cycle = opt.value"
                        class="relative p-4 rounded-xl border text-left cursor-pointer transition-all duration-150 flex flex-col justify-between select-none"
                        :class="[
                            form.default_payroll_cycle === opt.value
                                ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20 shadow-xs'
                                : 'border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50/50'
                        ]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                    :class="form.default_payroll_cycle === opt.value ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500'"
                                >
                                    <!-- Monthly Icon (Calendar) -->
                                    <svg v-if="opt.value === 'monthly'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <!-- Weekly Icon (Clock) -->
                                    <svg v-else-if="opt.value === 'weekly'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <!-- Daily Icon (Sun) -->
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <span
                                    class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                                    :class="form.default_payroll_cycle === opt.value ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300'"
                                >
                                    <span v-if="form.default_payroll_cycle === opt.value" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                            <div class="text-sm font-bold text-slate-800 leading-snug">{{ opt.title }}</div>
                            <div class="text-xs text-slate-500 leading-normal">{{ opt.desc }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Parameter Hari Kerja & Perhitungan Ketidakhadiran -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Dasar Pembagi Hari Kerja</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menentukan rumus pembagi nominal upah harian saat karyawan tidak hadir (Alpha) tanpa surat cuti resmi.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Hari Kerja Bulanan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Standar Hari Kerja Periode Bulanan
                        </label>
                        <Input
                            v-model="form.monthly_working_days"
                            type="number"
                            min="1"
                            max="31"
                            suffix="Hari Kerja"
                            placeholder="Contoh: 25"
                            :error="form.errors.monthly_working_days"
                        />
                        <p class="text-xs text-slate-500">
                            Dasar pembagi gaji bulanan untuk potongan absen 1 hari: <span class="font-mono font-medium text-slate-700">Gaji Pokok / {{ form.monthly_working_days || 25 }} hari</span>.
                        </p>
                    </div>

                    <!-- Hari Kerja Mingguan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Standar Hari Kerja Periode Mingguan
                        </label>
                        <Input
                            v-model="form.weekly_working_days"
                            type="number"
                            min="1"
                            max="7"
                            suffix="Hari Kerja"
                            placeholder="Contoh: 6"
                            :error="form.errors.weekly_working_days"
                        />
                        <p class="text-xs text-slate-500">
                            Standar hari kerja mingguan (umumnya 6 hari kerja, Senin &ndash; Sabtu).
                        </p>
                    </div>
                </div>

                <!-- Info Box: Logika Perhitungan -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Ketentuan Penggajian Sesuai Kehadiran (No Work No Pay)</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-600">
                        <div class="p-3 bg-white rounded-lg border border-slate-200/70 space-y-1">
                            <div class="font-bold text-slate-900">1. Karyawan Bulanan</div>
                            <p>Gaji pokok bulanan tetap utuh. Jika tidak hadir 1 hari, dipotong otomatis sebesar: <br><span class="font-mono text-emerald-700 font-medium">1 &times; (Gaji Pokok / {{ form.monthly_working_days || 25 }})</span>.</p>
                        </div>
                        <div class="p-3 bg-white rounded-lg border border-slate-200/70 space-y-1">
                            <div class="font-bold text-slate-900">2. Karyawan Mingguan</div>
                            <p>Upah per minggu dihitung dari <span class="font-mono text-emerald-700 font-medium">Upah/Hari &times; {{ form.weekly_working_days || 6 }} Hari</span>. Jika absen, dipotong proporsional per hari absen.</p>
                        </div>
                        <div class="p-3 bg-white rounded-lg border border-slate-200/70 space-y-1">
                            <div class="font-bold text-slate-900">3. Karyawan Harian</div>
                            <p>Murni dibayarkan sesuai kehadiran: <span class="font-mono text-emerald-700 font-medium">Hari Masuk &times; Upah Harian</span>. Tanpa gaji pokok bulanan.</p>
                        </div>
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
                    Simpan Pengaturan Penggajian
                </Button>
            </div>
        </form>
    </div>
</template>
