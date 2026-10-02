<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    branch: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.branch.name || '',
    code: props.branch.code || '',
    timezone: props.branch.timezone || 'WIB',
    radius: props.branch.radius || 100,
    latitude: props.branch.latitude || '',
    longitude: props.branch.longitude || '',
    address: props.branch.address || '',
});

const timezoneOptions = [
    { value: 'WIB', label: 'WIB (Waktu Indonesia Barat)' },
    { value: 'WITA', label: 'WITA (Waktu Indonesia Tengah)' },
    { value: 'WIT', label: 'WIT (Waktu Indonesia Timur)' },
];

const submit = () => {
    form.put(route('owner.management.company.branches.update', props.branch.id));
};
</script>

<template>
    <Head :title="`Edit Cabang - ${branch.name}`" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Edit Cabang Kantor
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui informasi lokasi, zona waktu, atau radius geolokasi.
                </p>
            </div>
            <Link :href="route('owner.management.company.branches.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <Input
                        label="Nama Cabang"
                        v-model="form.name"
                        placeholder="misal: Kantor Pusat Jakarta"
                        required
                        :error="form.errors.name"
                    />

                    <Input
                        label="Kode Cabang"
                        v-model="form.code"
                        placeholder="misal: JKT-01"
                        required
                        :error="form.errors.code"
                    />

                    <Select
                        label="Zona Waktu"
                        v-model="form.timezone"
                        :options="timezoneOptions"
                        required
                        :error="form.errors.timezone"
                    />

                    <Input
                        label="Radius Presensi (Meter)"
                        v-model="form.radius"
                        type="number"
                        min="1"
                        placeholder="100"
                        required
                        :error="form.errors.radius"
                    />

                    <Input
                        label="Latitude"
                        v-model="form.latitude"
                        placeholder="misal: -6.2088"
                        required
                        :error="form.errors.latitude"
                    />

                    <Input
                        label="Longitude"
                        v-model="form.longitude"
                        placeholder="misal: 106.8456"
                        required
                        :error="form.errors.longitude"
                    />

                    <div class="md:col-span-2">
                        <Textarea
                            label="Alamat Lengkap"
                            v-model="form.address"
                            rows="3"
                            placeholder="Tuliskan alamat lengkap kantor cabang..."
                            required
                            :error="form.errors.address"
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.company.branches.index')">
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
