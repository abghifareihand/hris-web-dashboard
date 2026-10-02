<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    name: '',
    default_quota: 12,
});

const submit = () => {
    form.post(route('owner.management.leaves.categories.store'));
};
</script>

<template>
    <Head title="Tambah Kategori Cuti" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tambah Kategori Cuti</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Buat jenis cuti baru dan tentukan kuota standar tahunan yang otomatis diinisialisasi untuk karyawan.
                </p>
            </div>
            <Link :href="route('owner.management.leaves.categories.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Nama Kategori Cuti <span class="text-rose-500">*</span>
                </label>
                <Input
                    v-model="form.name"
                    placeholder="Contoh: Cuti Tahunan, Cuti Melahirkan, Cuti Menikah"
                    :error="form.errors.name"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Kuota Standar Tahunan (Hari) <span class="text-rose-500">*</span>
                </label>
                <Input
                    v-model="form.default_quota"
                    type="number"
                    min="0"
                    placeholder="12"
                    :error="form.errors.default_quota"
                    required
                />
                <p class="text-xs text-slate-400 mt-1.5">
                    Kuota ini akan otomatis dialokasikan ke semua karyawan aktif untuk tahun berjalan.
                </p>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <Link :href="route('owner.management.leaves.categories.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Simpan Kategori
                </Button>
            </div>
        </form>
    </div>
</template>
