<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getPerformanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const tabs = computed(() => getPerformanceReportTabs());

const props = defineProps({
    reports: Object,
    totalReports: Number,
    reportingEmployeesCount: Number,
    totalAttachmentsCount: Number,
    branches: Array,
    divisions: Array,
    employees: Array,
    filters: Object,
});

const filterForm = ref({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    employee_id: props.filters?.employee_id || '',
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

const applyFilter = () => {
    router.get(route('owner.reports.performances.daily.index'), filterForm.value, {
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
        employee_id: '',
        search: '',
        per_page: 10,
    };
    applyFilter();
};

const exportUrl = () => {
    const params = new URLSearchParams(filterForm.value).toString();
    return `${route('owner.reports.performances.daily.export')}?${params}`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Laporan Kerja Harian" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Laporan Kerja Harian</h1>
                    <p class="text-slate-500 text-sm mt-1">Rekapitulasi catatan dan uraian aktivitas kerja harian yang dilaporkan oleh karyawan.</p>
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

            <!-- 4 Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Laporan</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalReports }} Laporan</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Melapor</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ reportingEmployeesCount }} Orang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Lampiran</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalAttachmentsCount }} Berkas</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Periode</div>
                        <div class="text-xs font-bold text-slate-900 mt-1">
                            {{ formatDate(filters?.start_date) }} - {{ formatDate(filters?.end_date) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Karyawan</label>
                            <Dropdown
                                v-model="filterForm.employee_id"
                                :options="employees.map(e => ({ value: e.id, label: e.name }))"
                                all-label="Semua Karyawan"
                                size="sm"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.start_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                            <input type="date" v-model="filterForm.end_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-end pt-3 border-t border-slate-100">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cari Keyword (Judul / Uraian / Nama)</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Ketik kata kunci pencarian..."
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
                                Cari Laporan
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
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Karyawan</th>
                                <th class="px-5 py-3.5">Judul Aktivitas</th>
                                <th class="px-5 py-3.5">Uraian / Ringkasan</th>
                                <th class="px-5 py-3.5 text-center">Lampiran</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="rep in reports.data" :key="rep.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-4 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ formatDate(rep.date) }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">{{ rep.user?.employee?.name || rep.user?.name || '-' }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ rep.user?.employee?.branch?.name || '-' }} • {{ rep.user?.employee?.division?.name || '-' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-900 max-w-xs truncate">
                                    {{ rep.title }}
                                </td>
                                <td class="px-5 py-4 text-slate-600 max-w-sm truncate">
                                    {{ rep.description || '-' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span v-if="rep.attachments && rep.attachments.length > 0" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                        {{ rep.attachments.length }} Berkas
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link
                                        :href="route('owner.reports.performances.daily.show', rep.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg text-xs font-semibold transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Detail</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!reports.data || reports.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada laporan kerja harian untuk kriteria ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reports.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="reports.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
