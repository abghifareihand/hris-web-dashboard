<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Select from '@/Components/UI/Select.vue';

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
});

const form = useForm({
    title: '',
    start_date: '',
    end_date: '',
    branch: 'all',
    division: 'all',
});

const branchOptions = computed(() => [
    { value: 'all', label: 'Semua Cabang Kantor' },
    ...props.branches.map((b) => ({ value: b.id, label: b.name })),
]);

const divisionOptions = computed(() => [
    { value: 'all', label: 'Semua Divisi Kerja' },
    ...props.divisions.map((d) => ({ value: d.id, label: d.name })),
]);

const submit = () => {
    form.post(route('owner.management.schedules.holidays.store'));
};
</script>

<template>
    <Head title="Tambah Hari Libur" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.schedules.holidays.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Daftar Hari Libur"
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
                    Tambah Hari Libur
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Buat jadwal hari libur atau cuti bersama dan tentukan apakah berlaku untuk semua cabang/divisi.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Hari Libur
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Nama Hari Libur -->
                    <Input
                        label="Nama Hari Libur / Acara"
                        v-model="form.title"
                        placeholder="Contoh: Hari Raya Idul Fitri, Tahun Baru Masehi"
                        :error="form.errors.title"
                        required
                    />

                    <!-- Rentang Tanggal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <DatePicker
                            label="Tanggal Mulai"
                            v-model="form.start_date"
                            placeholder="Pilih Tanggal Mulai"
                            :error="form.errors.start_date"
                            required
                        />

                        <DatePicker
                            label="Tanggal Selesai"
                            v-model="form.end_date"
                            placeholder="Pilih Tanggal Selesai"
                            :error="form.errors.end_date"
                            required
                        />
                    </div>

                    <!-- Batasan Cabang & Divisi -->
                    <div class="pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <Select
                            label="Cabang yang Libur"
                            v-model="form.branch"
                            :options="branchOptions"
                            placeholder="Pilih Cabang"
                            :error="form.errors.branch"
                        />

                        <Select
                            label="Divisi yang Libur"
                            v-model="form.division"
                            :options="divisionOptions"
                            placeholder="Pilih Divisi"
                            :error="form.errors.division"
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.schedules.holidays.index')">
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
