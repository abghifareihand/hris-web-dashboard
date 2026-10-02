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
    branchList: Array,
    paginatedBranches: Object,
    totalBranches: Number,
    totalEmployeesAllBranches: Number,
    avgCompanyAttendance: Number,
    avgCompanyScore: Number,
    month: Number,
    year: Number,
    months: Object,
    years: Array,
    filters: Object,
});

const filterForm = ref({
    month: props.filters?.month || props.month || new Date().getMonth() + 1,
    year: props.filters?.year || props.year || new Date().getFullYear(),
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

const applyFilter = () => {
    router.get(route('owner.reports.performances.branch.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
        search: '',
        per_page: 10,
    };
    applyFilter();
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.performances.branch.export')}?${params}`;
};
</script>

<template>
    <Head title="Laporan Performa Cabang" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Laporan Performa Cabang</h1>
                    <p class="text-slate-500 text-sm mt-1">Analisis tingkat kehadiran, rata-rata jam kerja, turnover karyawan, dan skor performa per cabang.</p>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Cabang</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalBranches }} Cabang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Karyawan</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalEmployeesAllBranches }} Orang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ avgCompanyAttendance }}%</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Skor Cabang</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ avgCompanyScore }}%</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="flex flex-col sm:flex-row items-center gap-4 justify-between">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="w-36">
                            <Dropdown
                                v-model="filterForm.month"
                                :options="Object.entries(months).map(([num, name]) => ({ value: Number(num), label: name }))"
                                size="sm"
                            />
                        </div>
                        <div class="w-28">
                            <Dropdown
                                v-model="filterForm.year"
                                :options="years.map(y => ({ value: y, label: String(y) }))"
                                size="sm"
                            />
                        </div>
                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors">
                            Filter
                        </button>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <input
                            type="text"
                            v-model="filterForm.search"
                            placeholder="Cari cabang..."
                            class="text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 w-full sm:w-64"
                        />
                        <button type="button" @click="resetFilter" class="px-3 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium">
                            Reset
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
                                <th class="px-5 py-3.5">Cabang</th>
                                <th class="px-5 py-3.5 text-center">Jumlah Karyawan</th>
                                <th class="px-5 py-3.5 text-center">Tingkat Kehadiran</th>
                                <th class="px-5 py-3.5 text-center">Rata-rata Jam Kerja</th>
                                <th class="px-5 py-3.5 text-center">Turnover Rate</th>
                                <th class="px-5 py-3.5 text-center">Skor Performa</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="branch in branchList" :key="branch.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ branch.branch_name }}</div>
                                    <div class="text-[11px] text-slate-400">Kode: {{ branch.branch_code || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 text-center font-bold text-slate-800">
                                    {{ branch.employee_count }} Orang
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="branch.attendance_rate >= 80 ? 'bg-emerald-500' : 'bg-amber-500'"
                                                :style="{ width: `${Math.min(100, branch.attendance_rate)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-semibold text-slate-800">{{ branch.attendance_rate }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center font-medium text-slate-700">
                                    {{ branch.avg_work_hours }} Jam / Hari
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="['font-semibold', branch.turnover_rate > 10 ? 'text-rose-600' : 'text-slate-600']">
                                        {{ branch.turnover_rate }}%
                                    </span>
                                    <div class="text-[10px] text-slate-400">({{ branch.resigned_count }} resign)</div>
                                </td>
                                <td class="px-5 py-4 text-center font-bold text-slate-900">
                                    {{ branch.performance_score }}%
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold', branch.status_class]">
                                        {{ branch.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!branchList || branchList.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data performa cabang.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="paginatedBranches && paginatedBranches.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="paginatedBranches.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
