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

                <!-- 3 Card Radio Options -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Bulanan -->
                    <label
                        class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all select-none"
                        :class="form.default_payroll_cycle === 'monthly'
                            ? 'border-emerald-600 bg-emerald-50/40 text-slate-900 shadow-xs ring-1 ring-emerald-600/20'
                            : 'border-slate-200/90 hover:border-slate-300 bg-white text-slate-600'"
                    >
                        <input
                            type="radio"
                            value="monthly"
                            v-model="form.default_payroll_cycle"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-900">Bulanan</span>
                            <span
                                v-if="form.default_payroll_cycle === 'monthly'"
                                class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-4 ring-emerald-100"
                            ></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Gaji tetap per bulan. Pemotongan absen dihitung proporsional berbasis hari kerja efektif.
                        </p>
                    </label>

                    <!-- Mingguan -->
                    <label
                        class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all select-none"
                        :class="form.default_payroll_cycle === 'weekly'
                            ? 'border-emerald-600 bg-emerald-50/40 text-slate-900 shadow-xs ring-1 ring-emerald-600/20'
                            : 'border-slate-200/90 hover:border-slate-300 bg-white text-slate-600'"
                    >
                        <input
                            type="radio"
                            value="weekly"
                            v-model="form.default_payroll_cycle"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-900">Mingguan</span>
                            <span
                                v-if="form.default_payroll_cycle === 'weekly'"
                                class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-4 ring-emerald-100"
                            ></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Upah mingguan (standar 6 hari kerja). Pembayaran dilakukan rutin setiap pekan.
                        </p>
                    </label>

                    <!-- Harian -->
                    <label
                        class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all select-none"
                        :class="form.default_payroll_cycle === 'daily'
                            ? 'border-emerald-600 bg-emerald-50/40 text-slate-900 shadow-xs ring-1 ring-emerald-600/20'
                            : 'border-slate-200/90 hover:border-slate-300 bg-white text-slate-600'"
                    >
                        <input
                            type="radio"
                            value="daily"
                            v-model="form.default_payroll_cycle"
                            class="sr-only"
                        />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-900">Harian</span>
                            <span
                                v-if="form.default_payroll_cycle === 'daily'"
                                class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-4 ring-emerald-100"
                            ></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Murni upah harian (Hari Masuk &times; Upah/Hari). Tanpa gaji pokok bulanan, tidak masuk tidak dibayar.
                        </p>
                    </label>
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
