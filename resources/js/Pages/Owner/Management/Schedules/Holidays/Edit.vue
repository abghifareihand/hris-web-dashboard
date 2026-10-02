<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    holiday: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
});

const formatDateForInput = (val) => {
    if (!val) return '';
    return val.substring(0, 10);
};

const form = useForm({
    title: props.holiday.name || '',
    start_date: formatDateForInput(props.holiday.start_date),
    end_date: formatDateForInput(props.holiday.end_date),
    branch: props.holiday.branch_id ? String(props.holiday.branch_id) : 'all',
    division: props.holiday.division_id ? String(props.holiday.division_id) : 'all',
});

const submit = () => {
    form.put(route('owner.management.schedules.holidays.update', props.holiday.id));
};
</script>

<template>
    <Head :title="`Edit Hari Libur - ${holiday.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Hari Libur</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui nama, tanggal, atau cakupan cabang/divisi hari libur perusahaan.
                </p>
            </div>
            <Link :href="route('owner.management.schedules.holidays.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
            <!-- Nama Hari Libur -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Nama Hari Libur / Acara <span class="text-rose-500">*</span>
                </label>
                <Input
                    v-model="form.title"
                    placeholder="Contoh: Hari Raya Idul Fitri"
                    :error="form.errors.title"
                    required
                />
            </div>

            <!-- Rentang Tanggal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Tanggal Mulai <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.start_date"
                        type="date"
                        :error="form.errors.start_date"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Tanggal Selesai <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.end_date"
                        type="date"
                        :error="form.errors.end_date"
                        required
                    />
                </div>
            </div>

            <!-- Batasan Cabang & Divisi -->
            <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Cabang yang Libur</label>
                    <select
                        v-model="form.branch"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="all">Semua Cabang Kantor</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Divisi yang Libur</label>
                    <select
                        v-model="form.division"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="all">Semua Divisi Kerja</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <Link :href="route('owner.management.schedules.holidays.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Perbarui Hari Libur
                </Button>
            </div>
        </form>
    </div>
</template>
