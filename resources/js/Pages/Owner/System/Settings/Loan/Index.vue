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
    max_loan_amount: props.setting?.max_loan_amount || '',
    max_loan_amount_weekly: props.setting?.max_loan_amount_weekly || '',
    max_loan_amount_daily: props.setting?.max_loan_amount_daily || '',
    max_tenor_months: props.setting?.max_tenor_months ?? '',
    max_tenor_weeks: props.setting?.max_tenor_weeks ?? '',
    max_tenor_days: props.setting?.max_tenor_days ?? '',
    due_date_day: props.setting?.due_date_day ?? '',
});

const submit = () => {
    form.post(route('owner.finance.loans.settings.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Kebijakan Pinjaman (Kasbon)" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Pinjaman</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Atur batas maksimal plafon kasbon, durasi tenor cicilan berurutan per siklus gaji, serta jadwal pemotongan payroll karyawan.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Card 1: Plafon Maksimal Pinjaman per Siklus Gaji -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">1. Plafon Maksimal Pinjaman</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Tentukan batas tertinggi nominal pinjaman kasbon berdasarkan sistem penggajian karyawan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Plafon Bulanan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Plafon Karyawan Bulanan
                        </label>
                        <Input
                            v-model="form.max_loan_amount"
                            :currency="true"
                            placeholder="Contoh: 10.000.000"
                            :error="form.errors.max_loan_amount"
                        />
                    </div>

                    <!-- Plafon Mingguan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Plafon Karyawan Mingguan
                        </label>
                        <Input
                            v-model="form.max_loan_amount_weekly"
                            :currency="true"
                            placeholder="Contoh: 2.000.000"
                            :error="form.errors.max_loan_amount_weekly"
                        />
                    </div>

                    <!-- Plafon Harian -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Plafon Karyawan Harian
                        </label>
                        <Input
                            v-model="form.max_loan_amount_daily"
                            :currency="true"
                            placeholder="Contoh: 500.000"
                            :error="form.errors.max_loan_amount_daily"
                        />
                    </div>
                </div>
            </div>

            <!-- Card 2: Batas Tenor Cicilan (Berurutan) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">2. Batas Maksimal Tenor Angsuran</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pilihan cicilan di aplikasi karyawan akan otomatis tersusun berurutan dari 1 sampai batas maksimal di bawah ini.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Tenor Bulanan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Maks. Tenor Bulanan
                        </label>
                        <Input
                            v-model="form.max_tenor_months"
                            type="number"
                            min="1"
                            max="60"
                            suffix="Bulan"
                            placeholder="Contoh: 12"
                            :error="form.errors.max_tenor_months"
                        />
                    </div>

                    <!-- Tenor Mingguan -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Maks. Tenor Mingguan
                        </label>
                        <Input
                            v-model="form.max_tenor_weeks"
                            type="number"
                            min="1"
                            max="52"
                            suffix="Minggu"
                            placeholder="Contoh: 4"
                            :error="form.errors.max_tenor_weeks"
                        />
                    </div>

                    <!-- Tenor Harian -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Maks. Tenor Harian
                        </label>
                        <Input
                            v-model="form.max_tenor_days"
                            type="number"
                            min="1"
                            max="90"
                            suffix="Hari"
                            placeholder="Contoh: 14"
                            :error="form.errors.max_tenor_days"
                        />
                    </div>
                </div>

                <!-- Info Hint -->
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-600">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        <strong>Dinamis & Terintegrasi:</strong> Karyawan hanya akan melihat pilihan tenor sesuai jenis gajinya. Misalnya, karyawan bergaji mingguan hanya melihat pilihan minggu secara berurutan dan tidak bisa memilih tenor bulan.
                    </span>
                </div>
            </div>

            <!-- Card 3: Jadwal Jatuh Tempo & Ketentuan Potongan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">3. Jadwal Pemotongan Gaji</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Penetapan tanggal jatuh tempo angsuran bulanan yang otomatis ditarik saat penggajian.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <!-- Tanggal Jatuh Tempo Bulanan -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">
                            Tanggal Jatuh Tempo Bulanan
                        </label>
                        <Input
                            v-model="form.due_date_day"
                            type="number"
                            min="1"
                            max="31"
                            suffix="Setiap Bulan"
                            placeholder="Contoh: 25"
                            :error="form.errors.due_date_day"
                        />
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Jika dikosongkan, default jatuh tempo pemotongan adalah tanggal 1 setiap bulannya.
                        </p>
                    </div>

                    <!-- Ringkasan Mekanisme Otomatis -->
                    <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-100 text-xs text-emerald-900 space-y-2">
                        <p class="font-bold flex items-center gap-1.5 text-emerald-800">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Mekanisme Otomatis Potong Gaji:
                        </p>
                        <ul class="list-disc list-inside space-y-1 text-emerald-800/90 pl-1">
                            <li><strong>Bulanan:</strong> Dipotong pada slip gaji bulanan sesuai tanggal di samping.</li>
                            <li><strong>Mingguan:</strong> Dipotong otomatis setiap akhir pekan pada slip gaji mingguan.</li>
                            <li><strong>Harian:</strong> Dipotong otomatis dari upah kehadiran saat penggajian harian.</li>
                            <li><strong>Proteksi Cicilan:</strong> Maksimal cicilan dibatasi aman 35% dari upah per periode.</li>
                        </ul>
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
                    Simpan Pengaturan Pinjaman
                </Button>
            </div>
        </form>
    </div>
</template>
