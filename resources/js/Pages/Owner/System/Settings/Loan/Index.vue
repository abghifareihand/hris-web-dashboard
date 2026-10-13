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
    max_tenor_months: props.setting?.max_tenor_months ?? '',
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
                Atur batas maksimal plafon kasbon, durasi tenor angsuran, serta jadwal jatuh tempo pemotongan gaji karyawan.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Batas & Parameter Kasbon</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfigurasi nominal dan jadwal cicilan pinjaman bagi seluruh karyawan perusahaan.
                    </p>
                </div>

                <div class="space-y-5">
                    <!-- Plafon Maksimal -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">
                            Batas Maksimal Pinjaman per Karyawan (Plafon Rp)
                        </label>
                        <Input
                            v-model="form.max_loan_amount"
                            :currency="true"
                            placeholder="Contoh: 10.000.000"
                            :error="form.errors.max_loan_amount"
                        />
                        <p class="text-xs text-slate-500">
                            Biarkan kosong jika perusahaan tidak membatasi nominal pinjaman (Tidak Terbatas).
                        </p>
                    </div>

                    <!-- Tenor & Due Date (Grid 2 cols) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                        <!-- Tenor Maksimal -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">
                                Batas Maksimal Tenor Angsuran
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
                            <p class="text-xs text-slate-500">
                                Biarkan kosong jika durasi tenor cicilan fleksibel / tidak dibatasi.
                            </p>
                        </div>

                        <!-- Tanggal Jatuh Tempo -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">
                                Tanggal Jatuh Tempo Potongan (Cut-Off)
                            </label>
                            <Input
                                v-model="form.due_date_day"
                                type="number"
                                min="1"
                                max="30"
                                suffix="Setiap Bulan"
                                placeholder="Contoh: 25"
                                :error="form.errors.due_date_day"
                            />
                            <p class="text-xs text-slate-500">
                                Biarkan kosong untuk menggunakan Tanggal 1 sebagai default tanggal jatuh tempo setiap bulannya.
                            </p>
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
                    Simpan Pengaturan Pinjaman
                </Button>
            </div>
        </form>
    </div>
</template>
