<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getAttendanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const tabs = computed(() => getAttendanceReportTabs('rate'));

const props = defineProps({
    employees: Object,
    rateData: [Array, Object],
    branches: Array,
    divisions: Array,
    startDate: String,
    endDate: String,
    filters: Object,
});

const filterForm = ref({
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    search: props.filters?.search || '',
    start_date: props.filters?.start_date || props.startDate || '',
    end_date: props.filters?.end_date || props.endDate || '',
});

const applyFilter = () => {
    router.get(route('owner.reports.attendances.rate.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        branch_id: '',
        division_id: '',
        search: '',
        start_date: props.startDate || '',
        end_date: props.endDate || '',
    };
    applyFilter();
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.attendances.rate.export')}?${params}`;
};

const getItem = (empId) => {
    if (Array.isArray(props.rateData)) {
        return props.rateData.find(item => item.employee?.id === empId) || {};
    }
    return props.rateData?.[empId] || {};
};

// Summary metrics computed
const summaryMetrics = computed(() => {
    const list = Array.isArray(props.rateData) ? props.rateData : Object.values(props.rateData || {});
    if (list.length === 0) return { avgRate: 0, totalWorkingDays: 0, totalCount: 0 };

    const totalRate = list.reduce((acc, curr) => acc + (parseFloat(curr.attendance_rate) || 0), 0);
    const avgRate = Math.round((totalRate / list.length) * 10) / 10;
    const maxDays = Math.max(...list.map(i => i.total_schedule || 0), 0);

    return {
        avgRate,
        totalWorkingDays: maxDays,
        totalCount: props.employees?.total || list.length,
    };
});
</script>

<template>
    <Head title="Laporan Tingkat Kehadiran" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Laporan Tingkat Kehadiran</h1>
                    <p class="text-slate-500 text-sm mt-1">Analisis persentase dan tingkat kehadiran karyawan per area/cabang serta perorangan.</p>
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
            <Tabs :items="tabs" />

            <!-- 3 Summary Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="rounded-xl p-5 text-white shadow-xs bg-gradient-to-br from-emerald-500 to-emerald-600 text-center">
                    <div class="text-3xl font-extrabold tracking-tight mb-1">{{ summaryMetrics.avgRate }}%</div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-emerald-100">Rata-rata Kehadiran</div>
                </div>

                <div class="rounded-xl p-5 text-white shadow-xs bg-gradient-to-br from-blue-500 to-indigo-600 text-center">
                    <div class="text-3xl font-extrabold tracking-tight mb-1">{{ summaryMetrics.totalWorkingDays }}</div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-blue-100">Hari Kerja Terjadwal</div>
                </div>

                <div class="rounded-xl p-5 text-white shadow-xs bg-gradient-to-br from-amber-500 to-orange-500 text-center">
                    <div class="text-3xl font-extrabold tracking-tight mb-1">{{ summaryMetrics.totalCount }}</div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-100">Total Karyawan Terdata</div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
                                <th class="px-5 py-3.5 text-center">Hari Kerja</th>
                                <th class="px-5 py-3.5 text-center">Hadir</th>
                                <th class="px-5 py-3.5 text-center">Tingkat Kehadiran</th>
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
                                    {{ getItem(emp.id).total_schedule || 0 }} Hari
                                </td>
                                <td class="px-5 py-3.5 text-center font-bold text-emerald-600">
                                    {{ getItem(emp.id).total_present || 0 }} Hari
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="(getItem(emp.id).attendance_rate || 0) >= 80 ? 'bg-emerald-500' : ((getItem(emp.id).attendance_rate || 0) >= 65 ? 'bg-blue-500' : 'bg-rose-500')"
                                                :style="{ width: `${Math.min(100, getItem(emp.id).attendance_rate || 0)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-bold text-slate-800">{{ getItem(emp.id).attendance_rate || 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[11px] font-bold border',
                                            (getItem(emp.id).attendance_rate || 0) >= 80 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : ((getItem(emp.id).attendance_rate || 0) >= 65 ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-rose-100 text-rose-800 border-rose-200')
                                        ]"
                                    >
                                        {{ getItem(emp.id).evaluation_status || 'Cukup' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!employees.data || employees.data.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data tingkat kehadiran.</p>
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
