<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    balance: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    quota: props.balance.quota || 0,
    used: props.balance.used || 0,
});

const submit = () => {
    form.put(route('owner.management.leaves.balance.update', props.balance.id));
};
</script>

<template>
    <Head :title="`Edit Saldo Cuti - ${balance.employee?.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Saldo Cuti</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Sesuaikan total kuota cuti tahunan dan jumlah yang telah terpakai secara manual.
                </p>
            </div>
            <Link :href="route('owner.management.leaves.balance.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <!-- Info Card -->
        <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between gap-4">
            <div>
                <div class="font-bold text-slate-900">{{ balance.employee?.name }}</div>
                <div class="text-xs text-slate-500 mt-0.5">
                    NIP: {{ balance.employee?.nip || '-' }} &bull; Kategori: {{ balance.leave_category?.name }}
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold text-slate-500">Tahun Periode</div>
                <div class="text-sm font-bold font-mono text-emerald-800">{{ balance.year }}</div>
            </div>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Kuota Cuti Diberikan (Hari) <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.quota"
                        type="number"
                        min="0"
                        :error="form.errors.quota"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Jumlah Hari Terpakai <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.used"
                        type="number"
                        min="0"
                        :error="form.errors.used"
                        required
                    />
                </div>
            </div>

            <!-- Kalkulasi Sisa -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-sm">
                <span class="text-slate-600 font-medium">Sisa Kuota Cuti Akhir:</span>
                <span class="font-bold font-mono text-base text-emerald-700">
                    {{ (Number(form.quota) || 0) - (Number(form.used) || 0) }} Hari
                </span>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <Link :href="route('owner.management.leaves.balance.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Perbarui Saldo
                </Button>
            </div>
        </form>
    </div>
</template>
