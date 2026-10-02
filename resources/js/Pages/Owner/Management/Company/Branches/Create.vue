<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Textarea from "@/Components/UI/Textarea.vue";

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    name: "",
    code: "",
    timezone: "WIB",
    radius: 100,
    latitude: "",
    longitude: "",
    address: "",
});

const timezoneOptions = [
    { value: "WIB", label: "WIB (Waktu Indonesia Barat)" },
    { value: "WITA", label: "WITA (Waktu Indonesia Tengah)" },
    { value: "WIT", label: "WIT (Waktu Indonesia Timur)" },
];

const submit = () => {
    form.post(route("owner.management.company.branches.store"));
};
</script>

<template>
    <Head title="Tambah Cabang" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.company.branches.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Daftar Cabang"
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
                    Tambah Cabang Baru
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Tentukan nama kantor, koordinat GPS, dan radius presensi.
                </p>
            </div>
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
