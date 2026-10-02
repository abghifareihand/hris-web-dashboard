<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getAttendanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const tabs = computed(() => getAttendanceReportTabs('overtime-recap'));

const props = defineProps({
    employees: Object,
    overtimeData: Object,
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
    router.get(route('owner.reports.attendances.overtime-recap.index'), filterForm.value, {
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
    return `${route('owner.reports.attendances.overtime-recap.export')}?${params}`;
};

const getEmpOt = (empId) => {
    return props.overtimeData?.[empId] || { total_count: 0, duration_formatted: '0j', estimated_pay: 0 };
};

// Summary metrics computed
const summaryStats = computed(() => {
    const items = Object.values(props.overtimeData || {});
    const employeesWithOt = items.filter(i => i.total_count > 0).length;
    const totalRequests = items.reduce((acc, curr) => acc + (curr.total_count || 0), 0);
    const totalMinutes = items.reduce((acc, curr) => acc + (curr.total_minutes || 0), 0);
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    const durationFormatted = `${hours} Jam ${mins} Menit`;

    return {
        employeesWithOt,
        totalRequests,
        durationFormatted,
    };
});
</script>

<template>
    <Head title="Rekap Lembur Karyawan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Rekap Lembur Karyawan</h1>
                    <p class="text-slate-500 text-sm mt-1">Laporan rekapitulasi data dan total durasi lembur yang disetujui.</p>
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
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Lembur</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.employeesWithOt }} Orang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengajuan Lembur</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.totalRequests }} Kali</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Durasi Lembur</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ summaryStats.durationFormatted }}</div>
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
                                <th class="px-5 py-3.5 text-center">Jumlah Pengajuan</th>
                                <th class="px-5 py-3.5 text-center">Total Durasi</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
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
                                <td class="px-5 py-3.5 text-center font-bold text-slate-800">
                                    {{ getEmpOt(emp.id).total_count }} Kali
                                </td>
                                <td class="px-5 py-3.5 text-center font-bold text-emerald-600">
                                    {{ getEmpOt(emp.id).duration_formatted }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <Link
                                        :href="route('owner.reports.attendances.overtime-recap.detail', emp.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg text-xs font-semibold transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Rincian</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!employees.data || employees.data.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data rekap lembur.</p>
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
