<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    // 1. Informasi Karyawan
    name: "",
    nik: "",
    email: "",
    phone: "",
    gender: "",
    birth_date: "",
    address: "",

    // 2. Informasi Akun Login
    password: "",
    password_confirmation: "",

    // 3. Informasi Pekerjaan
    nip: "",
    joined_at: "",
    branch_id: "",
    division_id: "",
    position_id: "",

    // 4. Informasi Bank
    bank_name: "",
    bank_account_number: "",
    bank_account_name: "",

    // 5. Informasi Pajak
    is_taxable: "1",
    npwp_number: "",
    marital_status: "single",
    dependents: 0,

    // 6. BPJS Kesehatan
    bpjs_kesehatan_number: "",

    // 7. BPJS Ketenagakerjaan
    bpjs_ketenagakerjaan_number: "",

    // 8. Informasi Gaji
    payroll_cycle: "monthly",
    basic_salary: 0,
    fixed_allowance: 0,
    daily_allowance: 0,
    other_allowance: 0,
    overtime_rate_per_hour: 0,

    // 9. Ketentuan Potongan Absensi
    late_penalty_type: "prorate",
    late_penalty_nominal: 0,
    alpha_penalty_type: "prorate",
    alpha_penalty_nominal: 0,
});

const formatRupiah = (val) => new Intl.NumberFormat("id-ID").format(Math.round(val || 0));

const perMinuteLatePenalty = computed(() => {
    const nominal = Number(form.late_penalty_nominal || 0);
    const perMinute = Math.round(nominal / 60);
    return formatRupiah(perMinute);
});

const lateProrateRate = computed(() => {
    return Number(form.late_penalty_nominal) > 0 ? Number(form.late_penalty_nominal) : 20000;
});

const lateFlatRate = computed(() => {
    return Number(form.late_penalty_nominal) > 0 ? Number(form.late_penalty_nominal) : 20000;
});

const alphaProrateSalary = computed(() => {
    return Number(form.basic_salary) > 0 ? Number(form.basic_salary) : 3000000;
});

const alphaProratePerDay = computed(() => {
    return Math.round(alphaProrateSalary.value / 30);
});

const alphaFlatRate = computed(() => {
    return Number(form.alpha_penalty_nominal) > 0 ? Number(form.alpha_penalty_nominal) : 100000;
});

const submit = () => {
    form.post(route("owner.management.employees.store"));
};
</script>

<template>
    <Head title="Tambah Karyawan Baru" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Tambah Karyawan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Lengkapi data karyawan baru untuk pembuatan akun.
                </p>
            </div>
            <Link :href="route('owner.management.employees.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. Informasi Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Karyawan
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <Input
                            label="Nama Lengkap"
                            v-model="form.name"
                            placeholder="Nama Lengkap"
                            :error="form.errors.name"
                            required
                        />

                        <Input
                            label="NIK (Nomor Induk Kependudukan)"
                            v-model="form.nik"
                            placeholder="16 digit NIK"
                            :error="form.errors.nik"
                        />

                        <Input
                            label="Email"
                            v-model="form.email"
                            type="email"
                            placeholder="Email"
                            :error="form.errors.email"
                            required
                        />

                        <Input
                            label="No. Telepon"
                            v-model="form.phone"
                            placeholder="No. Telepon"
                            :error="form.errors.phone"
                            required
                        />

                        <Select
                            label="Jenis Kelamin"
                            v-model="form.gender"
                            :options="[
                                { value: 'male', label: 'Laki-laki' },
                                { value: 'female', label: 'Perempuan' },
                            ]"
                            placeholder="Pilih Jenis Kelamin"
                            :error="form.errors.gender"
                        />

                        <DatePicker
                            label="Tanggal Lahir"
                            v-model="form.birth_date"
                            placeholder="Pilih Tanggal Lahir"
                            :error="form.errors.birth_date"
                        />

                        <div class="md:col-span-2">
                            <Textarea
                                label="Alamat"
                                v-model="form.address"
                                rows="3"
                                placeholder="Alamat domisili lengkap..."
                                :error="form.errors.address"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Informasi Akun Login -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Akun Login
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Info Alert Banner -->
                    <div class="p-4 rounded-xl bg-sky-50 border border-sky-200/80 flex items-start gap-3 text-sky-900">
                        <svg
                            class="w-5 h-5 text-sky-600 shrink-0 mt-0.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <div>
                            <div class="font-semibold text-xs sm:text-sm text-sky-900">
                                Akun Login Karyawan
                            </div>
                            <div class="text-xs text-sky-700 mt-0.5">
                                Sistem akan membuat akun login untuk karyawan ini dengan menggunakan email di atas. Tentukan password awal di bawah ini.
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label form-label-required">
                                Password
                            </label>
                            <div class="relative">
                                <Input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Tentukan password awal"
                                    :error="form.errors.password"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-hidden"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label form-label-required">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <Input
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    placeholder="Ulangi password"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-hidden"
                                >
                                    <svg
                                        v-if="!showPasswordConfirmation"
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Informasi Pekerjaan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Pekerjaan
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <Input
                            label="NIP (Nomor Induk Pegawai)"
                            v-model="form.nip"
                            placeholder="Contoh: EMP-2026-001"
                            :error="form.errors.nip"
                        />

                        <DatePicker
                            label="Tanggal Bergabung"
                            v-model="form.joined_at"
                            placeholder="Pilih Tanggal Bergabung"
                            :error="form.errors.joined_at"
                        />

                        <Select
                            label="Cabang"
                            v-model="form.branch_id"
                            :options="branches.map((b) => ({ value: b.id, label: b.name }))"
                            placeholder="Pilih Cabang"
                            :error="form.errors.branch_id"
                        />

                        <Select
                            label="Divisi"
                            v-model="form.division_id"
                            :options="divisions.map((d) => ({ value: d.id, label: d.name }))"
                            placeholder="Pilih Divisi"
                            :error="form.errors.division_id"
                        />

                        <Select
                            label="Jabatan"
                            v-model="form.position_id"
                            :options="positions.map((p) => ({ value: p.id, label: p.name }))"
                            placeholder="Pilih Jabatan"
                            :error="form.errors.position_id"
                        />
                    </div>
                </div>
            </div>

            <!-- 4. Informasi Bank -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Bank
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <Input
                            label="Nama Bank"
                            v-model="form.bank_name"
                            placeholder="BCA, Mandiri, BRI, BNI..."
                            :error="form.errors.bank_name"
                        />

                        <Input
                            label="Nomor Rekening"
                            v-model="form.bank_account_number"
                            placeholder="Nomor rekening bank"
                            :error="form.errors.bank_account_number"
                        />

                        <Input
                            label="Atas Nama Rekening"
                            v-model="form.bank_account_name"
                            placeholder="Nama pemilik buku rekening"
                            :error="form.errors.bank_account_name"
                        />
                    </div>
                </div>
            </div>

            <!-- 5. Informasi Pajak -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Pajak
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <Select
                            label="Penghasilan Kena Pajak"
                            v-model="form.is_taxable"
                            :options="[
                                { value: '0', label: 'Tidak Kena Pajak' },
                                { value: '1', label: 'Kena Pajak' },
                            ]"
                            placeholder="Pilih Status Pajak"
                            :error="form.errors.is_taxable"
                        />

                        <Input
                            label="Nomor NPWP"
                            v-model="form.npwp_number"
                            placeholder="15/16 digit NPWP"
                            :error="form.errors.npwp_number"
                        />

                        <Select
                            label="Status Pernikahan"
                            v-model="form.marital_status"
                            :options="[
                                { value: 'single', label: 'Belum Menikah' },
                                { value: 'married', label: 'Sudah Menikah' },
                            ]"
                            placeholder="Pilih Status Pernikahan"
                            :error="form.errors.marital_status"
                        />

                        <Input
                            label="Jumlah Tanggungan"
                            v-model="form.dependents"
                            type="number"
                            min="0"
                            max="3"
                            placeholder="0"
                        />
                    </div>
                </div>
            </div>

            <!-- 6. Informasi BPJS Kesehatan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi BPJS Kesehatan
                    </h2>
                </div>

                <div class="p-6">
                    <Input
                        label="Nomor BPJS Kesehatan"
                        v-model="form.bpjs_kesehatan_number"
                        placeholder="Nomor kartu BPJS Kesehatan"
                        :error="form.errors.bpjs_kesehatan_number"
                    />
                </div>
            </div>

            <!-- 7. Informasi BPJS Ketenagakerjaan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi BPJS Ketenagakerjaan
                    </h2>
                </div>

                <div class="p-6">
                    <Input
                        label="Nomor BPJS Ketenagakerjaan"
                        v-model="form.bpjs_ketenagakerjaan_number"
                        placeholder="Nomor kartu BPJS Ketenagakerjaan"
                        :error="form.errors.bpjs_ketenagakerjaan_number"
                    />
                </div>
            </div>

            <!-- 8. Informasi Gaji -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Gaji
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <Select
                            label="Siklus Penggajian"
                            v-model="form.payroll_cycle"
                            :options="[
                                { value: 'monthly', label: 'Bulanan' },
                                { value: 'weekly', label: 'Mingguan' },
                            ]"
                            placeholder="Pilih Siklus"
                            :error="form.errors.payroll_cycle"
                        />

                        <Input
                            label="Gaji Pokok (Rp)"
                            v-model="form.basic_salary"
                            currency
                            placeholder="0"
                            :error="form.errors.basic_salary"
                        />

                        <Input
                            label="Tunjangan Tetap (Rp)"
                            v-model="form.fixed_allowance"
                            currency
                            placeholder="0"
                            :error="form.errors.fixed_allowance"
                        />

                        <Input
                            label="Tunjangan Harian / Kehadiran (Rp)"
                            v-model="form.daily_allowance"
                            currency
                            placeholder="0"
                            :error="form.errors.daily_allowance"
                        />

                        <Input
                            label="Tunjangan Lainnya (Rp)"
                            v-model="form.other_allowance"
                            currency
                            placeholder="0"
                            :error="form.errors.other_allowance"
                        />

                        <Input
                            label="Tarif Lembur per Jam (Rp)"
                            v-model="form.overtime_rate_per_hour"
                            currency
                            placeholder="0"
                            :error="form.errors.overtime_rate_per_hour"
                        />
                    </div>
                </div>
            </div>

            <!-- 9. Ketentuan Potongan Absensi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Ketentuan Potongan Absensi
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Atur skema pemotongan upah saat karyawan mengalami keterlambatan atau mangkir kerja
                    </p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <!-- Kolom Kiri: Denda Keterlambatan & Alpha (lg:col-span-2) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Card: Denda Keterlambatan -->
                        <div class="p-4 bg-slate-50/60 rounded-2xl border border-slate-200/80 space-y-4">
                            <!-- Header Denda Keterlambatan -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <!-- Sleek Timer / Stopwatch Icon -->
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="13" r="8" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4l2.5 2.5M10 2h4M12 2v3" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-900">
                                        Denda Keterlambatan
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Dikenakan jika karyawan melakukan keterlambatan masuk kerja atau pulang cepat.
                                    </p>
                                </div>
                            </div>

                            <!-- Pilihan Tipe: Hitung per Jam vs By Nominal -->
                            <div class="space-y-2.5">
                                <!-- Option 1: Hitung per Jam (Proporsional) -->
                                <div
                                    @click="form.late_penalty_type = 'prorate'"
                                    :class="[
                                        'p-3.5 rounded-xl border transition-all cursor-pointer select-none',
                                        form.late_penalty_type === 'prorate'
                                            ? 'border-emerald-500 bg-emerald-50/20 shadow-2xs'
                                            : 'border-slate-200 hover:border-slate-300 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-900">
                                                Hitung per Jam (Proporsional)
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Denda dihitung berdasarkan total jam keterlambatan.
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-1">
                                                Contoh: telat 1 jam 30 menit = 1,5 x denda per jam.
                                            </p>
                                        </div>
                                        <div
                                            :class="[
                                                'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-all',
                                                form.late_penalty_type === 'prorate'
                                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                                    : 'border-slate-300 bg-white',
                                            ]"
                                        >
                                            <svg v-if="form.late_penalty_type === 'prorate'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option 2: By Nominal (Flat) -->
                                <div
                                    @click="form.late_penalty_type = 'flat'"
                                    :class="[
                                        'p-3.5 rounded-xl border transition-all cursor-pointer select-none',
                                        form.late_penalty_type === 'flat'
                                            ? 'border-emerald-500 bg-emerald-50/20 shadow-2xs'
                                            : 'border-slate-200 hover:border-slate-300 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-900">
                                                By Nominal (Flat)
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Denda dikenakan dengan jumlah tetap sekali terkena keterlambatan.
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-1">
                                                Contoh: telat 5 menit atau 2 jam = denda sama.
                                            </p>
                                        </div>
                                        <div
                                            :class="[
                                                'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-all',
                                                form.late_penalty_type === 'flat'
                                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                                    : 'border-slate-300 bg-white',
                                            ]"
                                        >
                                            <svg v-if="form.late_penalty_type === 'flat'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Nominal Denda Keterlambatan -->
                            <div class="space-y-1.5">
                                <Input
                                    :label="form.late_penalty_type === 'prorate' ? 'Nominal Denda Keterlambatan (per jam)' : 'Nominal Denda Keterlambatan (per hari)'"
                                    v-model="form.late_penalty_nominal"
                                    currency
                                    placeholder="0"
                                />

                                <!-- Dinamis Helper Text Perhitungan -->
                                <p v-if="form.late_penalty_type === 'prorate'" class="text-xs text-slate-500">
                                    Akan dikenakan potongan <span class="font-medium text-slate-700">Rp {{ perMinuteLatePenalty }}</span> pada setiap menit.
                                </p>
                                <p v-else class="text-xs text-slate-500">
                                    Denda yang dikenakan sekali pada setiap shift jika karyawan melakukan keterlambatan masuk kerja atau pulang cepat.
                                </p>
                            </div>

                            <!-- Catatan Kuning / Amber Note -->
                            <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                                <p class="text-xs text-amber-800 leading-relaxed">
                                    <span class="font-bold">Catatan:</span>
                                    <template v-if="form.late_penalty_type === 'prorate'">
                                        Denda dihitung proporsional berdasarkan total menit keterlambatan atau pulang cepat pada setiap shift.
                                    </template>
                                    <template v-else>
                                        Denda akan dikenakan setiap shift yang terlambat atau pulang cepat, sehingga dalam satu hari bisa dikenakan lebih dari sekali.
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- Card: Denda Alpha (Tidak Masuk / Bolos) -->
                        <div class="p-4 bg-slate-50/60 rounded-2xl border border-slate-200/80 space-y-4">
                            <!-- Header Denda Alpha -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                                    <!-- Sleek User-X (Karyawan Tidak Hadir / Alpha) Icon -->
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <line x1="17" y1="8" x2="22" y2="13" stroke-linecap="round" />
                                        <line x1="22" y1="8" x2="17" y2="13" stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-900">
                                        Denda Alpha (Tidak Masuk / Bolos)
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Dikenakan jika karyawan tidak masuk kerja tanpa keterangan.
                                    </p>
                                </div>
                            </div>

                            <!-- Pilihan Tipe Alpha -->
                            <div class="space-y-2.5">
                                <div
                                    @click="form.alpha_penalty_type = 'prorate'"
                                    :class="[
                                        'p-3.5 rounded-xl border transition-all cursor-pointer select-none',
                                        form.alpha_penalty_type === 'prorate'
                                            ? 'border-emerald-500 bg-emerald-50/20 shadow-2xs'
                                            : 'border-slate-200 hover:border-slate-300 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-900">
                                                Hitung Prorate dari Gaji Pokok
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Denda dihitung berdasarkan gaji pokok dibagi jumlah hari kerja.
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-1">
                                                Contoh: gaji pokok Rp 3.000.000 / 30 hari = Rp 100.000 per 1 hari alpha.
                                            </p>
                                        </div>
                                        <div
                                            :class="[
                                                'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-all',
                                                form.alpha_penalty_type === 'prorate'
                                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                                    : 'border-slate-300 bg-white',
                                            ]"
                                        >
                                            <svg v-if="form.alpha_penalty_type === 'prorate'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    @click="form.alpha_penalty_type = 'flat'"
                                    :class="[
                                        'p-3.5 rounded-xl border transition-all cursor-pointer select-none',
                                        form.alpha_penalty_type === 'flat'
                                            ? 'border-emerald-500 bg-emerald-50/20 shadow-2xs'
                                            : 'border-slate-200 hover:border-slate-300 bg-white',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-900">
                                                By Nominal (Flat)
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Denda dikenakan dengan jumlah tetap setiap 1 hari alpha.
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-1">
                                                Contoh: 1 hari alpha = denda sama.
                                            </p>
                                        </div>
                                        <div
                                            :class="[
                                                'w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-all',
                                                form.alpha_penalty_type === 'flat'
                                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                                    : 'border-slate-300 bg-white',
                                            ]"
                                        >
                                            <svg v-if="form.alpha_penalty_type === 'flat'" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 12 12">
                                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 6.586 3.707 5.293z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Nominal Denda Alpha (jika flat) -->
                            <div v-if="form.alpha_penalty_type === 'flat'" class="space-y-1.5">
                                <Input
                                    label="Nominal Denda Alpha (per hari)"
                                    v-model="form.alpha_penalty_nominal"
                                    currency
                                    placeholder="0"
                                />
                                <p class="text-xs text-slate-500">
                                    Denda yang dikenakan setiap 1 hari alpha.
                                </p>
                            </div>

                            <!-- Catatan Kuning / Amber Note Denda Alpha -->
                            <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                                <p class="text-xs text-amber-800 leading-relaxed">
                                    <span class="font-bold">Catatan:</span>
                                    <template v-if="form.alpha_penalty_type === 'prorate'">
                                        Denda alpha dihitung prorata dari gaji pokok sesuai jumlah hari kerja yang berlaku.
                                    </template>
                                    <template v-else>
                                        Denda alpha dikenakan dengan nominal tetap untuk setiap 1 hari karyawan tidak masuk kerja tanpa keterangan.
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Contoh Perhitungan Sidebar (lg:col-span-1) -->
                    <div class="space-y-6">
                        <!-- Card 1: Contoh Denda Keterlambatan -->
                        <div class="p-4 bg-slate-50/60 rounded-2xl border border-slate-200/80 space-y-4">
                            <!-- Header -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                    <!-- Sleek Calculator Icon -->
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <rect x="4" y="2" width="16" height="20" rx="2.5" />
                                        <line x1="8" y1="6" x2="16" y2="6" stroke-linecap="round" />
                                        <circle cx="8.5" cy="11" r="0.9" fill="currentColor" />
                                        <circle cx="12" cy="11" r="0.9" fill="currentColor" />
                                        <circle cx="15.5" cy="11" r="0.9" fill="currentColor" />
                                        <circle cx="8.5" cy="15" r="0.9" fill="currentColor" />
                                        <circle cx="12" cy="15" r="0.9" fill="currentColor" />
                                        <circle cx="15.5" cy="15" r="0.9" fill="currentColor" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900">
                                        Contoh Keterlambatan
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        Simulasi pemotongan denda keterlambatan.
                                    </p>
                                </div>
                            </div>

                            <!-- Box 1: Hitung per Jam (Proporsional) -->
                            <div
                                :class="[
                                    'p-3.5 rounded-xl space-y-2 border transition-all',
                                    form.late_penalty_type === 'prorate'
                                        ? 'bg-white border-emerald-400 shadow-2xs'
                                        : 'bg-white/70 border-slate-200/80 opacity-70',
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-slate-900">
                                        1. Hitung per Jam (Proporsional)
                                    </h5>
                                    <span v-if="form.late_penalty_type === 'prorate'" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600">
                                    Denda per jam = <span class="font-semibold text-slate-900">Rp {{ formatRupiah(lateProrateRate) }}</span>:
                                </p>
                                <ul class="text-xs text-slate-600 space-y-1 pl-3.5 list-disc marker:text-slate-400">
                                    <li>Telat 30 menit = Rp {{ formatRupiah(lateProrateRate * 0.5) }}</li>
                                    <li>Telat 1 jam = Rp {{ formatRupiah(lateProrateRate) }}</li>
                                    <li>Telat 2 jam 15 menit = Rp {{ formatRupiah(lateProrateRate * 2.25) }}</li>
                                </ul>
                            </div>

                            <!-- Box 2: By Nominal (Flat) -->
                            <div
                                :class="[
                                    'p-3.5 rounded-xl space-y-2 border transition-all',
                                    form.late_penalty_type === 'flat'
                                        ? 'bg-white border-emerald-400 shadow-2xs'
                                        : 'bg-white/70 border-slate-200/80 opacity-70',
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-slate-900">
                                        2. By Nominal (Flat)
                                    </h5>
                                    <span v-if="form.late_penalty_type === 'flat'" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600">
                                    Denda flat = <span class="font-semibold text-slate-900">Rp {{ formatRupiah(lateFlatRate) }}</span>:
                                </p>
                                <ul class="text-xs text-slate-600 space-y-1 pl-3.5 list-disc marker:text-slate-400">
                                    <li>Telat 5 menit = Rp {{ formatRupiah(lateFlatRate) }}</li>
                                    <li>Telat 1 jam = Rp {{ formatRupiah(lateFlatRate) }}</li>
                                    <li>Telat 3 jam = Rp {{ formatRupiah(lateFlatRate) }}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Card 2: Contoh Denda Alpha -->
                        <div class="p-4 bg-slate-50/60 rounded-2xl border border-slate-200/80 space-y-4">
                            <!-- Header -->
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                                    <!-- Sleek Receipt / Slip Potongan Icon -->
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4m-7 5V4a1 1 0 011-1h12a1 1 0 011 1v17l-3-1.5-3 1.5-3-1.5-3 1.5-3-1.5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900">
                                        Contoh Denda Alpha
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        Simulasi pemotongan ketidakhadiran.
                                    </p>
                                </div>
                            </div>

                            <!-- Box 1 Alpha: Prorate Gaji -->
                            <div
                                :class="[
                                    'p-3.5 rounded-xl space-y-2 border transition-all',
                                    form.alpha_penalty_type === 'prorate'
                                        ? 'bg-white border-emerald-400 shadow-2xs'
                                        : 'bg-white/70 border-slate-200/80 opacity-70',
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-slate-900">
                                        1. Prorate dari Gaji Pokok
                                    </h5>
                                    <span v-if="form.alpha_penalty_type === 'prorate'" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600">
                                    Gaji pokok = <span class="font-semibold text-slate-900">Rp {{ formatRupiah(alphaProrateSalary) }}</span> (30 hari):
                                </p>
                                <ul class="text-xs text-slate-600 space-y-1 pl-3.5 list-disc marker:text-slate-400">
                                    <li>Potongan/hari = Rp {{ formatRupiah(alphaProratePerDay) }}</li>
                                    <li>1 hari alpha = Rp {{ formatRupiah(alphaProratePerDay) }}</li>
                                    <li>2 hari alpha = Rp {{ formatRupiah(alphaProratePerDay * 2) }}</li>
                                </ul>
                            </div>

                            <!-- Box 2 Alpha: Flat -->
                            <div
                                :class="[
                                    'p-3.5 rounded-xl space-y-2 border transition-all',
                                    form.alpha_penalty_type === 'flat'
                                        ? 'bg-white border-emerald-400 shadow-2xs'
                                        : 'bg-white/70 border-slate-200/80 opacity-70',
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-slate-900">
                                        2. By Nominal (Flat)
                                    </h5>
                                    <span v-if="form.alpha_penalty_type === 'flat'" class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600">
                                    Denda per hari = <span class="font-semibold text-slate-900">Rp {{ formatRupiah(alphaFlatRate) }}</span>:
                                </p>
                                <ul class="text-xs text-slate-600 space-y-1 pl-3.5 list-disc marker:text-slate-400">
                                    <li>1 hari alpha = Rp {{ formatRupiah(alphaFlatRate) }}</li>
                                    <li>2 hari alpha = Rp {{ formatRupiah(alphaFlatRate * 2) }}</li>
                                    <li>3 hari alpha = Rp {{ formatRupiah(alphaFlatRate * 3) }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.employees.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
