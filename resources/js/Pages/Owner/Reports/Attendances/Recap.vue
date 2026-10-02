<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getAttendanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const props = defineProps({
    employees: Object,
    recapData: [Array, Object],
    branches: Array,
    divisions: Array,
    positions: Array,
    startDate: String,
    endDate: String,
    statusLabels: Object,
    filters: Object,
});

const filterForm = ref({
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    position_id: props.filters?.position_id || '',
    search: props.filters?.search || '',
    start_date: props.filters?.start_date || props.startDate || '',
    end_date: props.filters?.end_date || props.endDate || '',
});

const applyFilter = () => {
    router.get(route('owner.reports.attendances.recap.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        branch_id: '',
        division_id: '',
        position_id: '',
        search: '',
        start_date: props.startDate || '',
        end_date: props.endDate || '',
    };
    applyFilter();
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.attendances.recap.export')}?${params}`;
};

const getRecapItem = (empId) => {
    if (Array.isArray(props.recapData)) {
        return props.recapData.find(item => item.employee?.id === empId) || {};
    }
    return props.recapData?.[empId] || {};
};

const getEvaluationBadgeClass = (status) => {
    switch (status) {
        case 'Sangat Baik':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Baik':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Cukup':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Kurang':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Rekap Kehadiran Karyawan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Rekap Kehadiran Karyawan</h1>
                    <p class="text-slate-500 text-sm mt-1">Laporan rekapitulasi kehadiran, keterlambatan, cuti, lembur, dan evaluasi status absensi.</p>
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
                        <span>Export CSV/Excel</span>
                    </a>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs :items="getAttendanceReportTabs()" />

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.start_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                            <input type="date" v-model="filterForm.end_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cabang</label>
                            <Dropdown
                                v-model="filterForm.branch_id"
                                :options="branches.map(b => ({ value: b.id, label: b.name }))"
                                all-label="Semua Cabang"
                                size="sm"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Divisi</label>
                            <Dropdown
                                v-model="filterForm.division_id"
                                :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                                all-label="Semua Divisi"
                                size="sm"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Jabatan</label>
                            <Dropdown
                                v-model="filterForm.position_id"
                                :options="positions.map(p => ({ value: p.id, label: p.name }))"
                                all-label="Semua Jabatan"
                                size="sm"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cari Karyawan</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Nama / NIP..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="resetFilter"
                            class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Karyawan</th>
                                <th class="px-5 py-3.5 text-center">Jadwal</th>
                                <th class="px-5 py-3.5 text-center">Hadir</th>
                                <th class="px-5 py-3.5 text-center">Terlambat</th>
                                <th class="px-5 py-3.5 text-center">Pulang Cepat</th>
                                <th class="px-5 py-3.5 text-center">Alpha</th>
                                <th class="px-5 py-3.5 text-center">Cuti</th>
                                <th class="px-5 py-3.5 text-center">Lembur</th>
                                <th class="px-5 py-3.5 text-center">Evaluasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-800">{{ emp.name }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ emp.nip || '-' }} • {{ emp.division?.name || 'Tanpa Divisi' }} • {{ emp.branch?.name || '-' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                    {{ getRecapItem(emp.id).total_schedule || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-bold text-emerald-600">
                                    {{ getRecapItem(emp.id).total_present || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-amber-600">
                                    {{ getRecapItem(emp.id).total_late || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-orange-600">
                                    {{ getRecapItem(emp.id).total_early_leaving || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-bold text-rose-600">
                                    {{ getRecapItem(emp.id).total_alpha || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-blue-600">
                                    {{ getRecapItem(emp.id).total_leave || 0 }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-medium text-slate-700">
                                    {{ getRecapItem(emp.id).overtime_duration_formatted || '0j' }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold border', getEvaluationBadgeClass(getRecapItem(emp.id).evaluation_status)]">
                                        {{ getRecapItem(emp.id).evaluation_status || 'Cukup' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!employees.data || employees.data.length === 0">
                                <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data rekapitulasi kehadiran.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="employees.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="employees.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
