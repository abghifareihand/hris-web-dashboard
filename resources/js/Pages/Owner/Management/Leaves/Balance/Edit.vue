<script setup>
import { computed } from 'vue';
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

const defaultQuota = computed(() => {
    return props.balance.leave_category?.default_quota ?? props.balance.quota ?? 12;
});

const currentRemaining = computed(() => {
    return (Number(props.balance.quota) || 0) - (Number(props.balance.used) || 0);
});

const form = useForm({
    quota: props.balance.quota ?? 0,
});

const quotaValidationError = computed(() => {
    if (form.quota === '' || form.quota === null || form.quota === undefined) {
        return 'Jatah cuti yang diizinkan wajib diisi.';
    }
    const val = Number(form.quota);
    if (isNaN(val)) {
        return 'Jatah cuti harus berupa angka.';
    }
    if (val > defaultQuota.value) {
        return `Jatah cuti tidak boleh lebih dari jatah cuti perusahaan (${defaultQuota.value} hari)`;
    }
    if (val < (props.balance.used || 0)) {
        return `Jatah cuti tidak boleh kurang dari cuti terpakai (${props.balance.used || 0} hari)`;
    }
    return '';
});

const submit = () => {
    if (quotaValidationError.value) return;
    form.put(route('owner.management.leaves.balance.update', props.balance.id));
};
</script>

<template>
    <Head :title="`Edit Saldo Cuti - ${balance.employee?.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.leaves.balance.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Sisa Kuota Cuti"
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
                    Edit Saldo Cuti
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Sesuaikan alokasi jatah cuti karyawan untuk periode tahun ini.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Nama -->
                    <Input
                        label="Nama"
                        :model-value="balance.employee?.name ? `${balance.employee.name}${balance.employee.nip ? ' (' + balance.employee.nip + ')' : ''}` : '-'"
                        disabled
                    />

                    <!-- Kategori Cuti -->
                    <Input
                        label="Kategori Cuti"
                        :model-value="balance.leave_category?.name || '-'"
                        disabled
                    />

                    <!-- Tahun -->
                    <Input
                        label="Tahun"
                        :model-value="String(balance.year)"
                        disabled
                    />

                    <!-- Jatah Cuti Perusahaan -->
                    <Input
                        label="Jatah Cuti Perusahaan"
                        :model-value="`${defaultQuota} Hari`"
                        disabled
                    />

                    <!-- Cuti Terpakai -->
                    <Input
                        label="Cuti Terpakai"
                        :model-value="`${balance.used || 0} Hari`"
                        disabled
                    />

                    <!-- Sisa Cuti Saat Ini -->
                    <Input
                        label="Sisa Cuti Saat Ini"
                        :model-value="`${currentRemaining} Hari`"
                        disabled
                    />

                    <!-- Editable Field (Dipanjangin / Full Width) -->
                    <div class="md:col-span-2">
                        <Input
                            label="Jatah Cuti yang Diizinkan"
                            v-model="form.quota"
                            type="number"
                            :min="balance.used || 0"
                            :max="defaultQuota"
                            :error="quotaValidationError || form.errors.quota"
                            required
                        />
                        <p
                            v-if="!quotaValidationError && !form.errors.quota"
                            class="text-xs text-slate-500 mt-1.5"
                        >
                            Jatah cuti yang diizinkan tidak boleh kurang dari cuti terpakai yang telah diambil ({{ balance.used || 0 }} hari) dan tidak boleh lebih dari jatah cuti perusahaan ({{ defaultQuota }} hari).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.leaves.balance.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                    :disabled="Boolean(quotaValidationError)"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
