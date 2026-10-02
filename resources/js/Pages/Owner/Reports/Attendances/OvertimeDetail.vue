<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';

const props = defineProps({
    employee: Object,
    overtimes: Object,
    startDate: String,
    endDate: String,
    filters: Object,
});

const filterForm = ref({
    start_date: props.filters?.start_date || props.startDate || '',
    end_date: props.filters?.end_date || props.endDate || '',
});

const applyFilter = () => {
    router.get(route('owner.reports.attendances.overtime-recap.detail', props.employee.id), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.attendances.overtime-recap.detail-export', props.employee.id)}?${params}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Rincian Lembur - ${employee.name}`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.reports.attendances.overtime-recap.index')"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Rincian Lembur Karyawan</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Daftar riwayat lembur disetujui untuk {{ employee.name }}.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        :href="exportUrl()"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>

            <!-- Employee Info Header Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-xl shrink-0">
                        {{ employee.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">{{ employee.name }}</h2>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ employee.nip || '-' }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ employee.division?.name || 'Tanpa Divisi' }} • {{ employee.position?.name || 'Tanpa Jabatan' }} • {{ employee.branch?.name || '-' }}
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <form @submit.prevent="applyFilter" class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" v-model="filterForm.start_date" class="text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                    <span class="text-xs text-slate-400">s/d</span>
                    <input type="date" v-model="filterForm.end_date" class="text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                    <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Overtime Table -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5 text-center">Durasi</th>
                                <th class="px-5 py-3.5">Keterangan / Alasan</th>
                                <th class="px-5 py-3.5 text-center">Kompensasi</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ot in overtimes.data" :key="ot.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-800">
                                    {{ formatDate(ot.date) }}
                                </td>
                                <td class="px-5 py-4 text-center font-bold text-emerald-600">
                                    {{ Math.floor(ot.duration_minutes / 60) }}j {{ ot.duration_minutes % 60 }}m
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    {{ ot.reason || '-' }}
                                </td>
                                <td class="px-5 py-4 text-center text-slate-600 font-medium">
                                    {{ ot.compensation_type || 'Uang Lembur' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200">
                                        Disetujui
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!overtimes.data || overtimes.data.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Tidak ada data lembur untuk karyawan ini pada periode yang dipilih.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="overtimes.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="overtimes.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
