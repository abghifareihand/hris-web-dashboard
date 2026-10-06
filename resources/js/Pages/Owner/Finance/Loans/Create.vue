<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Textarea from '@/Components/UI/Textarea.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    loanSetting: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    employee_id: '',
    date: new Date().toISOString().split('T')[0],
    amount: '',
    tenor: 6,
    description: '',
});

const employeeOptions = computed(() => {
    return props.employees.map((emp) => ({
        value: emp.id,
        label: `${emp.name} (${emp.nip || 'Tanpa NIP'})`,
    }));
});

const selectedEmployee = computed(() => {
    return props.employees.find((e) => e.id === Number(form.employee_id));
});

const installmentPerMonth = computed(() => {
    const amt = Number(form.amount) || 0;
    const ten = Number(form.tenor) || 1;
    return Math.round(amt / ten);
});

const salaryRatio = computed(() => {
    const basic = Number(selectedEmployee.value?.basic_salary) || 0;
    if (basic === 0) return 0;
    return ((installmentPerMonth.value / basic) * 100).toFixed(1);
});

const isExceedingPolicy = computed(() => {
    return Number(salaryRatio.value) > 35;
});

const submit = () => {
    form.post(route('owner.finance.loans.store'));
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <Head title="Input Pinjaman Baru - Frans HRIS" />

    <div class="w-full space-y-6">
        <!-- Header with Back Button -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.finance.loans.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Manajemen Pinjaman"
            >
                <svg
                    class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Input Pinjaman Baru (Kasbon)
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Pemberian fasilitas pinjaman karyawan dengan kalkulasi cicilan otomatis.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Form Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <!-- Row 1: Pilih Karyawan & Tanggal Pinjaman -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <Select
                            label="Pilih Karyawan"
                            v-model="form.employee_id"
                            :options="employeeOptions"
                            placeholder="-- Pilih Karyawan --"
                            required
                            :error="form.errors.employee_id"
                        />

                        <!-- Info Gaji Pokok & Batas Cicilan -->
                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                        >
                            <div
                                v-if="selectedEmployee"
                                class="mt-1.5 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200/80 flex flex-wrap items-center justify-between gap-2 text-xs"
                            >
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <span class="text-slate-400">Gaji Pokok:</span>
                                    <span class="font-bold text-slate-800">
                                        {{ formatCurrency(selectedEmployee.basic_salary) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <span class="text-slate-400">Batas Cicilan (35%):</span>
                                    <span class="font-bold text-emerald-600">
                                        {{ formatCurrency(Math.round((selectedEmployee.basic_salary || 0) * 0.35)) }}/bulan
                                    </span>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <div>
                        <DatePicker
                            label="Tanggal Pinjaman"
                            v-model="form.date"
                            placeholder="Pilih Tanggal Pinjaman"
                            required
                            :error="form.errors.date"
                        />
                    </div>
                </div>

                <!-- Row 2: Nominal Pinjaman & Tenor Cicilan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <Input
                            label="Nominal Pinjaman"
                            v-model="form.amount"
                            currency
                            placeholder="0"
                            required
                            :error="form.errors.amount"
                            :help="loanSetting?.max_loan_amount ? `Maksimal plafon perusahaan: ${formatCurrency(loanSetting.max_loan_amount)}` : ''"
                        />
                    </div>

                    <div>
                        <Input
                            label="Tenor Cicilan"
                            type="number"
                            v-model="form.tenor"
                            suffix="Bulan"
                            placeholder="6"
                            min="1"
                            :max="loanSetting?.max_tenor_months || 60"
                            required
                            :error="form.errors.tenor"
                            :help="loanSetting?.max_tenor_months ? `Maksimal kebijakan perusahaan: ${loanSetting.max_tenor_months} bulan` : ''"
                        />
                    </div>
                </div>

                <!-- Realtime Installment Preview Card -->
                <div
                    v-if="form.amount && form.tenor"
                    class="p-4 rounded-xl border transition-colors space-y-2.5"
                    :class="isExceedingPolicy ? 'bg-rose-50/70 border-rose-200' : 'bg-emerald-50/60 border-emerald-200'"
                >
                    <div class="flex items-center justify-between text-sm">
                        <span
                            class="font-medium"
                            :class="isExceedingPolicy ? 'text-rose-900/80' : 'text-emerald-900/80'"
                        >
                            Estimasi Cicilan per Bulan:
                        </span>
                        <span
                            class="text-base font-bold"
                            :class="isExceedingPolicy ? 'text-rose-700' : 'text-emerald-700'"
                        >
                            {{ formatCurrency(installmentPerMonth) }} / bulan
                        </span>
                    </div>

                    <div
                        v-if="selectedEmployee?.basic_salary"
                        class="flex items-center justify-between text-xs pt-2.5 border-t"
                        :class="isExceedingPolicy ? 'border-rose-200/70 text-rose-900/80' : 'border-emerald-200/70 text-emerald-900/80'"
                    >
                        <span class="font-medium">Beban Potongan Gaji Pokok:</span>
                        <div
                            class="flex items-center gap-1 font-semibold"
                            :class="isExceedingPolicy ? 'text-rose-700' : 'text-emerald-700'"
                        >
                            <span class="font-bold">{{ salaryRatio }}%</span>
                            <span>({{ isExceedingPolicy ? 'Melebihi batas 35%' : 'Aman' }})</span>
                        </div>
                    </div>

                    <p v-if="isExceedingPolicy" class="text-xs text-rose-700 font-medium leading-relaxed pt-1">
                        ⚠️ Peringatan: Cicilan melebihi 35% dari gaji pokok karyawan. Disarankan memperpanjang tenor atau menurunkan nominal pinjaman agar tidak memberatkan keuangan karyawan.
                    </p>
                </div>

                <!-- Row 4: Alasan / Keperluan Pinjaman -->
                <div>
                    <Textarea
                        label="Alasan / Keperluan Pinjaman"
                        v-model="form.description"
                        rows="3"
                        placeholder="Tuliskan tujuan atau keperluan permohonan pinjaman..."
                        :error="form.errors.description"
                    />
                </div>

                <!-- Catatan Panel Owner -->
                <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-start gap-2.5">
                    <svg
                        class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"
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
                    <p class="text-xs text-amber-800 leading-relaxed">
                        <span class="font-semibold">Catatan:</span> Pinjaman yang dibuat langsung melalui panel Owner ini akan otomatis berstatus Disetujui (Approved) dan langsung menjadwalkan cicilan bulanan yang terhubung ke payroll.
                    </p>
                </div>
            </div>

            <!-- Action Buttons Outside Card -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link :href="route('owner.finance.loans.index')">
                    <Button
                        type="button"
                        variant="ghost"
                        class="text-slate-600 hover:text-slate-900"
                    >
                        Batal
                    </Button>
                </Link>
                <Button
                    type="submit"
                    variant="primary"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
