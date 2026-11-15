<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Checkbox from "@/Components/UI/Checkbox.vue";
import {
    POSITION_MENU_PERMISSIONS,
    createDefaultPermissions,
} from "@/data/positionPermissions";

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
    permissions: createDefaultPermissions(),
});

// Update specific checkbox
const updateAction = (menuKey, action, checked) => {
    if (!form.permissions[menuKey]) {
        form.permissions[menuKey] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
        };
    }
    form.permissions[menuKey][action] = checked;
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
                    Buat jabatan baru dan atur batasan akses cabang serta divisi kerja.
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
                    <div class="mb-3">
                        <h3 class="text-xs font-semibold text-slate-700">
                            Hak Akses Cabang
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih cabang yang dapat diakses oleh jabatan ini. Jika dikosongkan, tidak ada akses ke cabang mana pun.
                        </p>
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
                    <div class="mb-3">
                        <h3 class="text-xs font-semibold text-slate-700">
                            Hak Akses Divisi
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih divisi kerja yang dapat diakses oleh jabatan ini. Jika dikosongkan, tidak ada akses ke divisi mana pun.
                        </p>
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

                <!-- Hak Akses Menu -->
                <div class="pt-5 border-t border-slate-100">
                    <div class="mb-3">
                        <h3 class="text-xs font-semibold text-slate-700">
                            Hak Akses Menu
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih menu yang dapat diakses oleh jabatan ini. Jika dikosongkan, tidak ada akses ke menu mana pun.
                        </p>
                    </div>

                    <!-- Table Matrix Container -->
                    <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                        <th class="py-3 px-4 min-w-[280px]">
                                            Menu & Deskripsi Hak Akses
                                        </th>
                                        <th class="py-3 px-3 text-center w-24">
                                            View
                                        </th>
                                        <th class="py-3 px-3 text-center w-24">
                                            Create
                                        </th>
                                        <th class="py-3 px-3 text-center w-24">
                                            Edit
                                        </th>
                                        <th class="py-3 px-3 text-center w-24">
                                            Delete
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <tr
                                        v-for="item in POSITION_MENU_PERMISSIONS"
                                        :key="item.key"
                                        class="hover:bg-slate-50/60 transition-colors"
                                    >
                                        <!-- Menu Title, Badge & Description -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-semibold text-slate-800 leading-snug">
                                                    {{ item.title }}
                                                </span>
                                                <!-- Platform Badge -->
                                                <span
                                                    v-if="item.platform === 'WEB & MOBILE'"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80"
                                                >
                                                    WEB & MOBILE
                                                </span>
                                                <span
                                                    v-else-if="item.platform === 'WEB'"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-sky-50 text-sky-700 border border-sky-200/80"
                                                >
                                                    WEB
                                                </span>
                                                <span
                                                    v-else-if="item.platform === 'MOBILE'"
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200/80"
                                                >
                                                    MOBILE
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                {{ item.desc }}
                                            </div>
                                        </td>

                                        <!-- View Action -->
                                        <td class="py-3 px-3 text-center align-middle">
                                            <div class="flex items-center justify-center">
                                                <Checkbox
                                                    v-if="!item.disabled.view"
                                                    :id="`perm-${item.key}-view`"
                                                    :modelValue="Boolean(form.permissions[item.key]?.view)"
                                                    @update:modelValue="val => updateAction(item.key, 'view', val)"
                                                />
                                                <span v-else class="text-slate-300 font-bold select-none">-</span>
                                            </div>
                                        </td>

                                        <!-- Create Action -->
                                        <td class="py-3 px-3 text-center align-middle">
                                            <div class="flex items-center justify-center">
                                                <Checkbox
                                                    v-if="!item.disabled.create"
                                                    :id="`perm-${item.key}-create`"
                                                    :modelValue="Boolean(form.permissions[item.key]?.create)"
                                                    @update:modelValue="val => updateAction(item.key, 'create', val)"
                                                />
                                                <span v-else class="text-slate-300 font-bold select-none">-</span>
                                            </div>
                                        </td>

                                        <!-- Edit Action -->
                                        <td class="py-3 px-3 text-center align-middle">
                                            <div class="flex items-center justify-center">
                                                <Checkbox
                                                    v-if="!item.disabled.edit"
                                                    :id="`perm-${item.key}-edit`"
                                                    :modelValue="Boolean(form.permissions[item.key]?.edit)"
                                                    @update:modelValue="val => updateAction(item.key, 'edit', val)"
                                                />
                                                <span v-else class="text-slate-300 font-bold select-none">-</span>
                                            </div>
                                        </td>

                                        <!-- Delete Action -->
                                        <td class="py-3 px-3 text-center align-middle">
                                            <div class="flex items-center justify-center">
                                                <Checkbox
                                                    v-if="!item.disabled.delete"
                                                    :id="`perm-${item.key}-delete`"
                                                    :modelValue="Boolean(form.permissions[item.key]?.delete)"
                                                    @update:modelValue="val => updateAction(item.key, 'delete', val)"
                                                />
                                                <span v-else class="text-slate-300 font-bold select-none">-</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <p
                        v-if="form.errors.permissions"
                        class="text-xs text-rose-500 mt-1"
                    >
                        {{ form.errors.permissions }}
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
