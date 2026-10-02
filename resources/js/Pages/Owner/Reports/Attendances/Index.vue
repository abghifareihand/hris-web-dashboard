<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getAttendanceReportTabs } from '@/Utils/tabs';
import Dropdown from '@/Components/UI/Dropdown.vue';

const props = defineProps({
    attendances: Object,
    branches: Array,
    divisions: Array,
    filters: Object,
});

const filterForm = ref({
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
});

const applyFilter = () => {
    router.get(route('owner.reports.attendances.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        branch_id: '',
        division_id: '',
        status: '',
        start_date: '',
        end_date: '',
        search: '',
    };
    applyFilter();
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'present':
        case 'hadir':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'late':
        case 'terlambat':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'leave':
        case 'cuti':
        case 'izin':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'sick':
        case 'sakit':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'alpha':
        case 'absent':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Absensi Karyawan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Absensi Karyawan</h1>
                    <p class="text-slate-500 text-sm mt-1">Kelola data dan informasi absensi harian karyawan perusahaan.</p>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs :items="getAttendanceReportTabs()" />

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cari Karyawan</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Nama karyawan atau NIP..."
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
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition-colors flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Cari</span>
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
                                <th class="px-5 py-3.5">Shift</th>
                                <th class="px-5 py-3.5">Absen Masuk</th>
                                <th class="px-5 py-3.5">Absen Keluar</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="att in attendances.data" :key="att.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ att.employee?.name ? att.employee.name.charAt(0).toUpperCase() : '?' }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-800">{{ att.employee?.name || '-' }}</div>
                                            <div class="text-[11px] text-slate-400">
                                                {{ att.employee?.division?.name || 'Tanpa Divisi' }} • {{ att.employee?.branch?.name || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-700">
                                    {{ att.shift?.name || 'Bebas' }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">{{ att.clock_in_time ? att.clock_in_time.slice(0, 5) : '-' }}</div>
                                    <div class="text-[11px] text-slate-400">WIB</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">{{ att.clock_out_time ? att.clock_out_time.slice(0, 5) : '-' }}</div>
                                    <div class="text-[11px] text-slate-400">WIB</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-semibold border', getStatusBadgeClass(att.attendance_status)]">
                                        {{ att.status_label || att.attendance_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ formatDate(att.date) }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link
                                        :href="route('owner.reports.attendances.show', att.id)"
                                        class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                        title="Detail"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!attendances.data || attendances.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="font-medium">Belum ada data absensi karyawan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="attendances.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="attendances.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
