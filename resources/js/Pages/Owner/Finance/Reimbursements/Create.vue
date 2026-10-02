<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const props = defineProps({
    employees: Array,
})

const form = useForm({
    employee_id: '',
    date: new Date().toISOString().split('T')[0],
    amount: '',
    reason: '',
    payout_method: 'payroll',
    attachment: null,
})

const submit = () => {
    form.post(route('owner.finance.reimbursements.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Input Klaim Biaya Baru - Frans HRIS" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Input Klaim Biaya Baru</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Buat pengajuan reimbursement langsung atas nama karyawan.
                    </p>
                </div>
                <Link :href="route('owner.finance.reimbursements.index')">
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
                                {{ emp.name }} ({{ emp.nip || 'Tanpa NIP' }})
                            </option>
                        </select>
                        <span v-if="form.errors.employee_id" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.employee_id }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-secondary-700 mb-1">
                                Tanggal Pengeluaran <span class="text-rose-500">*</span>
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
                                Nominal Pengeluaran (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                v-model="form.amount"
                                placeholder="Contoh: 150000"
                                min="1"
                                class="input"
                                required
                            />
                            <span v-if="form.errors.amount" class="text-xs text-rose-500 mt-1 block">
                                {{ form.errors.amount }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Keterangan / Keperluan Biaya <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.reason"
                            rows="3"
                            placeholder="Deskripsikan tujuan dan detail pengeluaran biaya..."
                            class="w-full rounded-xl border border-secondary-300 p-3 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition outline-none"
                            required
                        ></textarea>
                        <span v-if="form.errors.reason" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.reason }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-2">Metode Pencairan Awal</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                                :class="form.payout_method === 'payroll' ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-secondary-200 hover:bg-secondary-50'"
                            >
                                <input type="radio" value="payroll" v-model="form.payout_method" class="mt-1 text-primary-600 focus:ring-primary-500" />
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-secondary-900">Gabung Payroll</span>
                                    <span class="block text-xs text-secondary-500 mt-0.5">Dicairkan bersamaan dengan slip gaji bulanan karyawan.</span>
                                </div>
                            </label>
                            <label
                                class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                                :class="form.payout_method === 'direct' ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-secondary-200 hover:bg-secondary-50'"
                            >
                                <input type="radio" value="direct" v-model="form.payout_method" class="mt-1 text-primary-600 focus:ring-primary-500" />
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-secondary-900">Transfer Langsung</span>
                                    <span class="block text-xs text-secondary-500 mt-0.5">Tandai langsung sebagai lunas (Paid).</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary-700 mb-1">
                            Lampiran Struk / Nota (Opsional)
                        </label>
                        <input
                            type="file"
                            accept="image/*,application/pdf"
                            @input="form.attachment = $event.target.files[0]"
                            class="block w-full text-xs text-secondary-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer"
                        />
                        <p class="text-xs text-secondary-400 mt-1">Maksimal 5MB (.jpg, .png, .pdf).</p>
                        <span v-if="form.errors.attachment" class="text-xs text-rose-500 mt-1 block">
                            {{ form.errors.attachment }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
                        <Link :href="route('owner.finance.reimbursements.index')">
                            <Button variant="secondary" type="button">Batal</Button>
                        </Link>
                        <Button variant="primary" type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Klaim Biaya' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
