<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.category.name || '',
    default_quota: props.category.default_quota || 0,
});

const submit = () => {
    form.put(route('owner.management.leaves.categories.update', props.category.id));
};
</script>

<template>
    <Head :title="`Edit Kategori Cuti - ${category.name}`" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.leaves.categories.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Kategori Cuti"
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
                    Edit Kategori Cuti
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui nama kategori atau kuota default cuti tahunan.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8 space-y-6">
                <div>
                    <Input
                        label="Nama Kategori Cuti"
                        v-model="form.name"
                        placeholder="Contoh: Cuti Tahunan, Cuti Melahirkan"
                        :error="form.errors.name"
                        required
                    />
                </div>

                <div>
                    <Input
                        label="Jatah Cuti Pertahun (Hari)"
                        v-model="form.default_quota"
                        type="number"
                        min="0"
                        placeholder="12"
                        :error="form.errors.default_quota"
                        required
                    />
                    <p class="text-xs text-slate-500 mt-1.5">
                        Perubahan kuota default akan menjadi standar alokasi cuti untuk karyawan.
                    </p>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.leaves.categories.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
