<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getScheduleTabs } from '@/Utils/tabs';

const tabs = computed(() => getScheduleTabs());

const props = defineProps({
    employee: Object,
    schedules: Array,
    holidays: Array,
    month: Number,
    year: Number,
    months: Object,
    years: Array,
});

const filterForm = ref({
    month: props.month,
    year: props.year,
});

const applyFilter = () => {
    router.get(route('employee.schedules.work.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
};
</script>

<template>
    <Head title="Jadwal & Shift Kerja" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Jadwal & Shift Kerja</h1>
                    <p class="text-slate-500 text-sm mt-1">Daftar jadwal tugas harian, shift kerja, dan kalender hari libur Anda.</p>
                </div>

                <form @submit.prevent="applyFilter" class="flex items-center gap-2">
                    <select v-model="filterForm.month" class="text-xs rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option v-for="(name, num) in months" :key="num" :value="Number(num)">{{ name }}</option>
                    </select>
                    <select v-model="filterForm.year" class="text-xs rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold">
                        Lihat
                    </button>
                </form>
            </div>

            <!-- Tabs -->
            <Tabs :items="tabs" />

            <!-- Schedules Table Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Jadwal Bulan {{ months[month] }} {{ year }}</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Shift Kerja</th>
                                <th class="px-5 py-3.5 text-center">Jam Kerja</th>
                                <th class="px-5 py-3.5 text-center">Status Hari</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="sch in schedules" :key="sch.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ formatDate(sch.date) }}
                                </td>
                                <td class="px-5 py-3.5 font-medium text-slate-800">
                                    {{ sch.is_day_off ? 'Libur Terjadwal' : (sch.shift?.name || 'Shift Reguler') }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono text-slate-700">
                                    <span v-if="!sch.is_day_off">
                                        {{ sch.shift?.clock_in ? sch.shift.clock_in.slice(0, 5) : '08:00' }} - {{ sch.shift?.clock_out ? sch.shift.clock_out.slice(0, 5) : '17:00' }} WIB
                                    </span>
                                    <span v-else class="text-slate-400">--:--</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span
                                        v-if="sch.is_day_off"
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold border bg-rose-50 text-rose-700 border-rose-200"
                                    >
                                        Hari Libur
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200"
                                    >
                                        Hari Kerja
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!schedules || schedules.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada jadwal kerja yang ditetapkan untuk bulan ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
