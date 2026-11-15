<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Checkbox from "@/Components/UI/Checkbox.vue";
import {
    POSITION_MENU_PERMISSIONS,
    normalizePermissions,
} from "@/data/positionPermissions";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    position: {
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

const form = useForm({
    name: props.position.name || "",
    branches: (props.position.branches || []).map((b) => b.id),
    divisions: (props.position.divisions || []).map((d) => d.id),
    permissions: normalizePermissions(props.position.permissions),
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
    form.put(
        route("owner.management.company.positions.update", props.position.id)
    );
};
</script>

<template>
    <Head :title="`Edit Jabatan - ${position.name}`" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Edit Jabatan
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui data jabatan dan sesuaikan akses cabang maupun divisi terkait.
                </p>
            </div>
            <Link :href="route('owner.management.company.positions.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
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
                        Belum ada cabang terdaftar.
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
                        Belum ada divisi terdaftar.
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
