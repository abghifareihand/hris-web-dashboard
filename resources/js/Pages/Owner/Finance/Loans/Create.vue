<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const props = defineProps({
    employees: Array,
    loanSetting: Object,
})

const form = useForm({
    employee_id: '',
    date: new Date().toISOString().split('T')[0],
    amount: '',
    tenor: 6,
    description: '',
})

const selectedEmployee = computed(() => {
    return props.employees.find(e => e.id === Number(form.employee_id))
})

const installmentPerMonth = computed(() => {
    const amt = Number(form.amount) || 0
    const ten = Number(form.tenor) || 1
    return Math.round(amt / ten)
})

const salaryRatio = computed(() => {
    const basic = Number(selectedEmployee.value?.basic_salary) || 0
    if (basic === 0) return 0
    return ((installmentPerMonth.value / basic) * 100).toFixed(1)
})

const isExceedingPolicy = computed(() => {
    return Number(salaryRatio.value) > 35
})

const submit = () => {
    form.post(route('owner.finance.loans.store'))
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Input Pinjaman Baru - Frans HRIS" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Input Pinjaman Baru (Kasbon)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Pemberian fasilitas pinjaman karyawan dengan kalkulasi cicilan otomatis.
                    </p>
                </div>
                <Link :href="route('owner.finance.loans.index')">
                    <Button variant="secondary" size="md">Kembali</Button>
                </Link>
            </div>

            <!-- Form Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Pilih Karyawan <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.employee_id"
                            class="input"
                            required
                        >
                            <option value="" disabled>-- Pilih Karyawan --</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.name }} ({{ emp.nip || 'Tanpa NIP' }}) - Gaji: {{ formatCurrency(emp.basic_salary) }}
                            </option>
                        </select>
                        <span v-if="form.errors.employee_id" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.employee_id }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-secondary-700 mb-1">
                                Tanggal Pencairan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.date"
                                class="input"
                                required
                            />
                            <span v-if="form.errors.date" class="text-xs text-rose-500 mt-1 block">
                                {{ form.errors.date }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-secondary-700 mb-1">
                                Durasi Tenor (Bulan) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                v-model="form.tenor"
                                min="1"
                                :max="loanSetting?.max_tenor_months || 60"
                                class="input"
                                required
                            />
                            <p class="text-xs text-secondary-400 mt-1" v-if="loanSetting?.max_tenor_months">
                                Maksimal kebijakan perusahaan: {{ loanSetting.max_tenor_months }} bulan
                            </p>
                            <span v-if="form.errors.tenor" class="text-xs text-rose-500 mt-1 block">
                                {{ form.errors.tenor }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Nominal Pinjaman (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="form.amount"
                            placeholder="Contoh: 3000000"
                            min="1"
                            :max="loanSetting?.max_loan_amount || undefined"
                            class="input"
                            required
                        />
                        <p class="text-xs text-secondary-400 mt-1" v-if="loanSetting?.max_loan_amount">
                            Maksimal plafon perusahaan: {{ formatCurrency(loanSetting.max_loan_amount) }}
                        </p>
                        <span v-if="form.errors.amount" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.amount }}
                        </span>
                    </div>

                    <!-- Realtime Installment Preview Card -->
                    <div v-if="form.amount && form.tenor" class="p-4 rounded-xl border transition" :class="isExceedingPolicy ? 'bg-rose-50/60 border-rose-200' : 'bg-primary-50/50 border-primary-100'">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-secondary-600 font-medium">Estimasi Cicilan per Bulan:</span>
                            <span class="text-base font-bold" :class="isExceedingPolicy ? 'text-rose-700' : 'text-primary-700'">
                                {{ formatCurrency(installmentPerMonth) }} / bulan
                            </span>
                        </div>
                        <div v-if="selectedEmployee?.basic_salary" class="mt-2 flex items-center justify-between text-xs pt-2 border-t" :class="isExceedingPolicy ? 'border-rose-200 text-rose-700' : 'border-primary-200/60 text-secondary-600'">
                            <span>Beban Potongan Gaji Pokok:</span>
                            <span class="font-bold font-mono">{{ salaryRatio }}% (Batas Maksimal Kebijakan: 35%)</span>
                        </div>
                        <p v-if="isExceedingPolicy" class="text-xs text-rose-600 font-medium mt-2">
                            ⚠️ Peringatan: Cicilan melebihi 35% gaji pokok karyawan. Disarankan memperpanjang tenor atau menurunkan nominal pinjaman agar pengajuan dapat disetujui.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Alasan / Keperluan Pinjaman
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Tuliskan tujuan permohonan pinjaman..."
                            class="w-full rounded-xl border border-secondary-300 p-3 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition outline-none"
                        ></textarea>
                        <span v-if="form.errors.description" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.description }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Link :href="route('owner.finance.loans.index')">
                            <Button variant="secondary" type="button">Batal</Button>
                        </Link>
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan & Setujui Pinjaman' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
