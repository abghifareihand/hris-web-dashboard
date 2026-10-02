<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getPerformanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const tabs = computed(() => getPerformanceReportTabs());

const props = defineProps({
    recapList: Array,
    paginatedEmployees: Object,
    totalEmployees: Number,
    totalNetHours: String,
    totalOvertimeHours: String,
    avgScore: Number,
    chartBarData: Object,
    chartDonutData: Object,
    branches: Array,
    divisions: Array,
    positions: Array,
    filters: Object,
});

const filterForm = ref({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    position_id: props.filters?.position_id || '',
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

const applyFilter = () => {
    router.get(route('owner.reports.performances.employee.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        start_date: '',
        end_date: '',
        branch_id: '',
        division_id: '',
        position_id: '',
        search: '',
        per_page: 10,
    };
    applyFilter();
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.performances.employee.export')}?${params}`;
};

const getScoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    if (score >= 65) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (score >= 50) return 'bg-amber-100 text-amber-800 border-amber-200';
    return 'bg-rose-100 text-rose-800 border-rose-200';
};
</script>

<template>
    <Head title="Laporan Performa Karyawan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Laporan Performa Karyawan</h1>
                    <p class="text-slate-500 text-sm mt-1">Ringkasan produktivitas jam kerja bersih, kedisiplinan istirahat, lembur, dan evaluasi skor performa.</p>
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

            <!-- 4 Summary Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Terdata</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalEmployees }} Orang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Kerja Bersih</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ totalNetHours }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Jam Lembur</div>
                        <div class="text-xl font-bold text-slate-900 mt-0.5">{{ totalOvertimeHours }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Skor Performa</div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-2xl font-bold text-slate-900">{{ avgScore }}%</span>
                            <span :class="['px-2 py-0.5 text-[11px] font-bold rounded-full border', getScoreBadgeClass(avgScore)]">
                                {{ avgScore >= 80 ? 'Sangat Baik' : (avgScore >= 65 ? 'Baik' : 'Cukup') }}
                            </span>
                        </div>
                    </div>
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
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Jabatan</label>
                            <Dropdown
                                v-model="filterForm.position_id"
                                :options="positions.map(p => ({ value: p.id, label: p.name }))"
                                all-label="Semua Jabatan"
                                size="sm"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-end pt-3 border-t border-slate-100">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cari Karyawan</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Nama, NIP, atau Email..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="flex gap-2 w-full sm:w-auto">
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
                                <th class="px-5 py-3.5 text-center">Kehadiran</th>
                                <th class="px-5 py-3.5 text-center">Terlambat</th>
                                <th class="px-5 py-3.5 text-center">Jam Kerja Bersih</th>
                                <th class="px-5 py-3.5 text-center">Lembur</th>
                                <th class="px-5 py-3.5 text-center">Disiplin Istirahat</th>
                                <th class="px-5 py-3.5 text-center">Skor Performa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in recapList" :key="row.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-800">{{ row.name }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ row.nip }} • {{ row.division }} • {{ row.branch }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="font-bold text-emerald-600">{{ row.present_days }}</span>
                                    <span class="text-slate-400"> / {{ row.scheduled_days }} Hari</span>
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold" :class="row.late_count > 0 ? 'text-amber-600' : 'text-slate-400'">
                                    {{ row.late_count }} Kali
                                </td>
                                <td class="px-5 py-3.5 text-center font-bold text-slate-800">
                                    {{ row.net_hours_text }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                    {{ row.overtime_hours_text }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-semibold border', row.break_discipline_class]">
                                        {{ row.break_discipline_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-20 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="row.score >= 80 ? 'bg-emerald-500' : (row.score >= 65 ? 'bg-blue-500' : 'bg-amber-500')"
                                                :style="{ width: `${Math.min(100, row.score)}%` }"
                                            ></div>
                                        </div>
                                        <span :class="['px-2 py-0.5 rounded-full text-xs font-bold border', getScoreBadgeClass(row.score)]">
                                            {{ row.score }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!recapList || recapList.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data performa karyawan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="paginatedEmployees && paginatedEmployees.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="paginatedEmployees.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
