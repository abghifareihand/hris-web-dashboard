<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Checkbox from "@/Components/UI/Checkbox.vue";
import {
    MOBILE_HOME_MENUS,
    MOBILE_APPROVAL_MENUS,
    MOBILE_COMPANY_MENUS,
    WEB_MANAGERIAL_MENUS,
    normalizePermissions,
} from "@/data/positionPermissions";

defineOptions({
    layout: AppLayout,
});

const activePlatformTab = ref("mobile");

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

// Update specific checkbox on Mobile
const updateMobileAction = (menuKey, action, checked) => {
    if (!form.permissions.mobile) {
        form.permissions.mobile = {};
    }
    if (!form.permissions.mobile[menuKey]) {
        form.permissions.mobile[menuKey] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
        };
    }
    form.permissions.mobile[menuKey][action] = checked;
};

// Update specific checkbox on Web
const updateWebAction = (menuKey, action, checked) => {
    if (!form.permissions.web) {
        form.permissions.web = {};
    }
    if (!form.permissions.web[menuKey]) {
        form.permissions.web[menuKey] = {
            view: false,
            create: false,
            edit: false,
            delete: false,
        };
    }
    form.permissions.web[menuKey][action] = checked;
};

const submit = () => {
    form.put(
        route("owner.management.company.positions.update", props.position.id),
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
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 space-y-6">
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
                            Pilih cabang yang dapat diakses oleh jabatan ini. Jika dikosongkan, pemegang jabatan ini tidak memiliki akses ke cabang mana pun (data akan kosong).
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
                    <p v-if="form.errors.branches" class="text-xs text-rose-500 mt-1">
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
                            Pilih divisi kerja yang dapat diakses oleh jabatan ini. Jika dikosongkan, pemegang jabatan ini tidak memiliki akses ke divisi mana pun (data akan kosong).
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
                    <p v-if="form.errors.divisions" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.divisions }}
                    </p>
                </div>

                <!-- Hak Akses Menu (2-Tab Platform Architecture) -->
                <div class="pt-5 border-t border-slate-100">
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold text-slate-700">
                            Hak Akses Menu & Fitur
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tentukan hak akses untuk aplikasi Mobile karyawan dan Web Dashboard manajerial secara terpisah dan terorganisir.
                        </p>
                    </div>

                    <!-- Platform Switcher Tabs -->
                    <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-lg w-fit border border-slate-200/80 mb-5">
                        <button
                            type="button"
                            @click="activePlatformTab = 'mobile'"
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors duration-150 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none select-none border"
                            :class="activePlatformTab === 'mobile' ? 'bg-white text-emerald-700 shadow-xs border-slate-200/80 font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent'"
                        >
                            <span class="text-base leading-none">📱</span>
                            <span>Mobile App Karyawan</span>
                        </button>
                        <button
                            type="button"
                            @click="activePlatformTab = 'web'"
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors duration-150 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none select-none border"
                            :class="activePlatformTab === 'web' ? 'bg-white text-emerald-700 shadow-xs border-slate-200/80 font-bold' : 'text-slate-600 hover:text-slate-900 border-transparent'"
                        >
                            <span class="text-base leading-none">💻</span>
                            <span>Web Dashboard Karyawan</span>
                        </button>
                    </div>

                    <!-- ============================================================= -->
                    <!-- TAB 1: 📱 MOBILE APP KARYAWAN -->
                    <!-- ============================================================= -->
                    <div v-show="activePlatformTab === 'mobile'" class="space-y-6">
                        <!-- Sub-grup 1: Menu Home - Diri Sendiri (ESS) -->
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                            <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                            1. Menu Home — Fitur Karyawan (12 Menu)
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Grid Home Mobile
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Mengatur menu di halaman utama aplikasi karyawan untuk mengurus data pribadi karyawan itu sendiri.
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                            <th class="py-3 px-4 min-w-[300px]">Menu Diri Sendiri & Fungsi</th>
                                            <th class="py-3 px-3 text-center w-24">View</th>
                                            <th class="py-3 px-3 text-center w-24">Create</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <tr v-for="item in MOBILE_HOME_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-800 leading-snug">
                                                    {{ item.title }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    {{ item.desc }}
                                                </div>
                                            </td>
                                            <!-- View Checkbox -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-home-${item.key}-view`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.view)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'view', val)"
                                                    />
                                                </div>
                                            </td>
                                            <!-- Create Checkbox -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="item.hasCreate" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-home-${item.key}-create`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.create)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'create', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">
                                                    —
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-grup 2: Menu Home - Persetujuan Pengajuan (Approval) -->
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                            <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                            2. Menu Home — Persetujuan & Kelola Karyawan
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Approval & Kelola Karyawan
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Persetujuan pengajuan staf serta pengelolaan presensi, laporan harian, dan kuota sisa cuti karyawan langsung dari Home Mobile.
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                            <th class="py-3 px-4 min-w-[300px]">Modul Persetujuan & Kelola Karyawan</th>
                                            <th class="py-3 px-3 text-center w-24">Edit</th>
                                            <th class="py-3 px-3 text-center w-24">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <tr v-for="item in MOBILE_APPROVAL_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-800 leading-snug">
                                                    {{ item.title }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    {{ item.desc }}
                                                </div>
                                            </td>
                                            <!-- Approval Edit Checkbox -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-approval-${item.key}-edit`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.edit)"
                                                        @update:modelValue="(val) => {
                                                            updateMobileAction(item.key, 'edit', val);
                                                            if (val) updateMobileAction(item.key, 'view', true);
                                                        }"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Approval Delete Checkbox -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-approval-${item.key}-delete`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.delete)"
                                                        @update:modelValue="(val) => {
                                                            updateMobileAction(item.key, 'delete', val);
                                                            if (val) updateMobileAction(item.key, 'view', true);
                                                        }"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-grup 3: Tab Perusahaan Mobile -->
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                            <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                            3. Tab Perusahaan — 12 Menu Kantor di Mobile
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            Bottom Navbar Perusahaan
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Menu operasional kantor yang tampil pada tab 'Perusahaan' di bagian bawah aplikasi karyawan (akan hilang otomatis jika tidak ada yang dicentang).
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                            <th class="py-3 px-4 min-w-[280px]">Menu Tab Perusahaan</th>
                                            <th class="py-3 px-3 text-center w-24">View</th>
                                            <th class="py-3 px-3 text-center w-24">Create</th>
                                            <th class="py-3 px-3 text-center w-24">Edit</th>
                                            <th class="py-3 px-3 text-center w-24">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <tr v-for="item in MOBILE_COMPANY_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-800 leading-snug">
                                                    {{ item.title }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    {{ item.desc }}
                                                </div>
                                            </td>
                                            <!-- View Action -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.view" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-company-${item.key}-view`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.view)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'view', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Create Action -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.create" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-company-${item.key}-create`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.create)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'create', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Edit Action -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-company-${item.key}-edit`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.edit)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'edit', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Delete Action -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`mobile-company-${item.key}-delete`"
                                                        :modelValue="Boolean(form.permissions.mobile?.[item.key]?.delete)"
                                                        @update:modelValue="(val) => updateMobileAction(item.key, 'delete', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================= -->
                    <!-- TAB 2: 💻 WEB DASHBOARD KARYAWAN -->
                    <!-- ============================================================= -->
                    <div v-show="activePlatformTab === 'web'" class="space-y-6">
                        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
                            <div class="bg-gradient-to-r from-slate-50 to-white px-5 py-3.5 border-b border-slate-200/80">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-slate-800 tracking-wide uppercase">
                                        Hak Akses Web Dashboard (Khusus Manajerial Laptop/PC)
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Sidebar Web
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Hak akses manajerial saat karyawan dengan jabatan ini login ke Web Dashboard di komputer. Murni untuk operasional perusahaan dan pengawasan tim (tidak ada menu diri sendiri).
                                </p>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/70 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                            <th class="py-3 px-4 min-w-[320px]">Menu Manajerial Web</th>
                                            <th class="py-3 px-3 text-center w-24">View</th>
                                            <th class="py-3 px-3 text-center w-24">Create</th>
                                            <th class="py-3 px-3 text-center w-24">Edit</th>
                                            <th class="py-3 px-3 text-center w-24">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <tr v-for="item in WEB_MANAGERIAL_MENUS" :key="item.key" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-slate-800 leading-snug">
                                                    {{ item.title }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    {{ item.desc }}
                                                </div>
                                            </td>
                                            <!-- View -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.view" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`web-${item.key}-view`"
                                                        :modelValue="Boolean(form.permissions.web?.[item.key]?.view)"
                                                        @update:modelValue="(val) => updateWebAction(item.key, 'view', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Create -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.create" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`web-${item.key}-create`"
                                                        :modelValue="Boolean(form.permissions.web?.[item.key]?.create)"
                                                        @update:modelValue="(val) => updateWebAction(item.key, 'create', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Edit -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.edit" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`web-${item.key}-edit`"
                                                        :modelValue="Boolean(form.permissions.web?.[item.key]?.edit)"
                                                        @update:modelValue="(val) => updateWebAction(item.key, 'edit', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                            <!-- Delete -->
                                            <td class="py-3 px-3 text-center align-middle">
                                                <div v-if="!item.disabled?.delete" class="flex items-center justify-center">
                                                    <Checkbox
                                                        :id="`web-${item.key}-delete`"
                                                        :modelValue="Boolean(form.permissions.web?.[item.key]?.delete)"
                                                        @update:modelValue="(val) => updateWebAction(item.key, 'delete', val)"
                                                    />
                                                </div>
                                                <div v-else class="text-slate-300 text-center text-xs">—</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.permissions" class="text-xs text-rose-500 mt-2">
                        {{ form.errors.permissions }}
                    </p>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.company.positions.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</template>
