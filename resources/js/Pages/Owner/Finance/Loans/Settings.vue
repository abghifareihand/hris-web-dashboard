<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const props = defineProps({
    setting: Object,
})

const form = useForm({
    max_loan_amount: props.setting?.max_loan_amount || '',
    max_tenor_months: props.setting?.max_tenor_months || 12,
    due_date_day: props.setting?.due_date_day || 25,
})

const submit = () => {
    form.post(route('owner.finance.loans.settings.store'))
}
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan Kebijakan Pinjaman - Frans HRIS" />

        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Kebijakan Pinjaman (Kasbon)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Atur batas maksimal pinjaman, tenor angsuran, dan tanggal jatuh tempo cicilan.
                    </p>
                </div>
                <Link :href="route('owner.finance.loans.index')">
                    <Button variant="secondary" size="md">Kembali</Button>
                </Link>
            </div>

            <!-- Settings Card -->
            <div class="bg-white p-6 rounded-xl border border-secondary-200 shadow-sm">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Batas Maksimal Pinjaman per Karyawan (Plafon Rp)
                        </label>
                        <input
                            type="number"
                            v-model="form.max_loan_amount"
                            placeholder="Contoh: 10000000 (Kosongkan jika tidak dibatasi)"
                            class="input"
                        />
                        <p class="text-xs text-secondary-400 mt-1">
                            Karyawan tidak dapat meminjam melebihi batas nominal ini.
                        </p>
                        <span v-if="form.errors.max_loan_amount" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.max_loan_amount }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Batas Maksimal Tenor Angsuran (Bulan) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="form.max_tenor_months"
                            min="1"
                            max="60"
                            class="input"
                            required
                        />
                        <p class="text-xs text-secondary-400 mt-1">
                            Maksimal durasi cicilan yang diperbolehkan (misal: 12 bulan).
                        </p>
                        <span v-if="form.errors.max_tenor_months" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.max_tenor_months }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Tanggal Jatuh Tempo Potongan Setiap Bulan (Hari ke-) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="form.due_date_day"
                            min="1"
                            max="30"
                            class="input"
                            required
                        />
                        <p class="text-xs text-secondary-400 mt-1">
                            Tanggal cut-off penagihan cicilan pinjaman (disarankan sama dengan tanggal tutup buku gaji/payroll, misal: tanggal 25).
                        </p>
                        <span v-if="form.errors.due_date_day" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.due_date_day }}
                        </span>
                    </div>

                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-xs text-amber-800 leading-relaxed">
                                <strong>Ketentuan Proteksi Finansial:</strong> Sistem secara otomatis menerapkan aturan batas aman cicilan maksimal <strong>35% dari Gaji Pokok</strong> per bulan untuk mencegah defisit pendapatan karyawan.
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Link :href="route('owner.finance.loans.index')">
                            <Button variant="secondary" type="button">Batal</Button>
                        </Link>
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Kebijakan' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
