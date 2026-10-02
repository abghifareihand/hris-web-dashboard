<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    division: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.division.name || '',
    is_attendance_schedule: !!props.division.is_attendance_schedule,
    is_attendance_radius: !!props.division.is_attendance_radius,
});

const submit = () => {
    form.put(route('owner.management.company.divisions.update', props.division.id));
};
</script>

<template>
    <Head :title="`Edit Divisi - ${division.name}`" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Divisi</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui nama divisi dan kebijakan presensi terkait.
                </p>
            </div>
            <Link :href="route('owner.management.company.divisions.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <Input
                        label="Nama Divisi"
                        v-model="form.name"
                        placeholder="misal: Teknologi Informasi, Keuangan, HRD"
                        required
                        :error="form.errors.name"
                    />
                </div>

                <!-- Aturan Presensi Divisi -->
                <div class="space-y-3 pt-2">
                    <label class="block text-xs font-semibold text-slate-700">
                        Aturan Presensi Divisi
                    </label>

                    <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                        <Checkbox
                            v-model="form.is_attendance_schedule"
                            label="Wajib mengikuti jadwal kerja & shift"
                        />
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            Jika diaktifkan, karyawan pada divisi ini harus presensi sesuai jam shift yang ditentukan.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                        <Checkbox
                            v-model="form.is_attendance_radius"
                            label="Wajib berada dalam radius lokasi kantor cabang"
                        />
                        <p class="text-xs text-slate-500 mt-1 ml-6">
                            Jika diaktifkan, presensi hanya dapat dilakukan jika karyawan berada dalam radius GPS kantor.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.company.divisions.index')">
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
