<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Checkbox from "@/Components/UI/Checkbox.vue";

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
    name: "",
    branches: [],
    divisions: [],
});

const toggleSelectAllBranches = () => {
    if (form.branches.length === props.branches.length) {
        form.branches = [];
    } else {
        form.branches = props.branches.map((b) => b.id);
    }
};

const toggleSelectAllDivisions = () => {
    if (form.divisions.length === props.divisions.length) {
        form.divisions = [];
    } else {
        form.divisions = props.divisions.map((d) => d.id);
    }
};

const submit = () => {
    form.post(route("owner.management.company.positions.store"));
};
</script>

<template>
    <Head title="Tambah Jabatan" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.company.positions.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Daftar Jabatan"
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
                    Tambah Jabatan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Buat jabatan baru dan atur batasan akses cabang serta divisi
                    kerja.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Card -->
            <div
                class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6"
            >
                <!-- Nama Jabatan -->
                <div>
                        <Input
                            label="Nama Jabatan"
                            v-model="form.name"
                            placeholder="Contoh: HR Manager, Lead Software Engineer"
                            :error="form.errors.name"
                            required
                        />
                    </div>

                    <!-- Hak Akses Cabang -->
                    <div class="pt-5 border-t border-slate-100">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3"
                        >
                            <div>
                                <h3
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Hak Akses Cabang
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pilih cabang yang dapat diisi oleh jabatan
                                    ini. Jika dikosongkan, berlaku untuk semua
                                    cabang.
                                </p>
                            </div>
                            <button
                                v-if="branches.length > 0"
                                type="button"
                                @click="toggleSelectAllBranches"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition cursor-pointer self-start sm:self-auto"
                            >
                                {{
                                    form.branches.length === branches.length
                                        ? "Batal Pilih Semua"
                                        : "Pilih Semua Cabang"
                                }}
                            </button>
                        </div>

                        <div
                            v-if="branches.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1 max-h-56 overflow-y-auto"
                        >
                            <Checkbox
                                v-for="branch in branches"
                                :key="branch.id"
                                :id="`branch-${branch.id}`"
                                :value="branch.id"
                                v-model="form.branches"
                                :label="branch.name"
                                class="p-2.5 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50/70 transition w-full"
                            />
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">
                            Belum ada cabang kantor yang terdaftar.
                        </p>
                        <p
                            v-if="form.errors.branches"
                            class="text-xs text-rose-500 mt-1"
                        >
                            {{ form.errors.branches }}
                        </p>
                    </div>

                    <!-- Hak Akses Divisi -->
                    <div class="pt-5 border-t border-slate-100">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3"
                        >
                            <div>
                                <h3
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Hak Akses Divisi
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pilih divisi kerja terkait jabatan ini. Jika
                                    dikosongkan, berlaku untuk semua divisi.
                                </p>
                            </div>
                            <button
                                v-if="divisions.length > 0"
                                type="button"
                                @click="toggleSelectAllDivisions"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition cursor-pointer self-start sm:self-auto"
                            >
                                {{
                                    form.divisions.length === divisions.length
                                        ? "Batal Pilih Semua"
                                        : "Pilih Semua Divisi"
                                }}
                            </button>
                        </div>

                        <div
                            v-if="divisions.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1 max-h-56 overflow-y-auto"
                        >
                            <Checkbox
                                v-for="division in divisions"
                                :key="division.id"
                                :id="`division-${division.id}`"
                                :value="division.id"
                                v-model="form.divisions"
                                :label="division.name"
                                class="p-2.5 bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50/70 transition w-full"
                            />
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">
                            Belum ada divisi kerja yang terdaftar.
                        </p>
                        <p
                            v-if="form.errors.divisions"
                            class="text-xs text-rose-500 mt-1"
                        >
                            {{ form.errors.divisions }}
                        </p>
                    </div>
                </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.company.positions.index')">
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
