<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import DatePickerRange from '@/Components/UI/DatePickerRange.vue';
import Badge from '@/Components/UI/Badge.vue';

defineOptions({
    layout: AppLayout,
});

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

const onDateRangeChange = (range) => {
    if (range && typeof range === 'object') {
        filterForm.value.start_date = range.startDate || '';
        filterForm.value.end_date = range.endDate || '';
    }
    router.get(
        route('owner.reports.attendances.overtime-recap.detail', props.employee.id),
        {
            start_date: filterForm.value.start_date || undefined,
            end_date: filterForm.value.end_date || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Rincian Lembur - ${employee.name}`" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('owner.reports.attendances.overtime-recap.index')"
                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors border border-slate-200 bg-white shadow-2xs"
                    title="Kembali ke Rekap Lembur"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rincian Lembur Karyawan</h1>
                    <p class="text-slate-500 text-xs sm:text-sm mt-0.5">Daftar riwayat lembur disetujui untuk {{ employee.name }}.</p>
                </div>
            </div>
        </div>

        <!-- Employee Info Header Card -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <img
                    v-if="employee.user?.avatar"
                    :src="employee.user.avatar"
                    :alt="employee.name"
                    class="w-14 h-14 rounded-2xl object-cover shrink-0 border border-slate-200 shadow-2xs"
                />
                <div
                    v-else
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold flex items-center justify-center text-xl shrink-0 shadow-2xs"
                >
                    {{ employee.name?.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-lg font-bold text-slate-900">{{ employee.name }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ employee.nip || '-' }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <span>{{ employee.branch?.name || '-' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>{{ employee.division?.name || 'Tanpa Divisi' }}</span>
                        <span class="text-slate-300">•</span>
                        <span>{{ employee.position?.name || 'Tanpa Jabatan' }}</span>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="w-full md:w-72">
                <DatePickerRange
                    label="Periode Tanggal"
                    v-model:startDate="filterForm.start_date"
                    v-model:endDate="filterForm.end_date"
                    placeholder="Pilih Rentang Tanggal"
                    @change="onDateRangeChange"
                />
            </div>
        </div>

        <!-- Overtime Table -->
        <DataTable
            :headers="[
                'Tanggal',
                'Durasi',
                'Keterangan / Alasan',
                'Kompensasi',
                'Status',
            ]"
        >
            <template v-if="overtimes.data && overtimes.data.length > 0">
                <tr
                    v-for="ot in overtimes.data"
                    :key="ot.id"
                    class="hover:bg-slate-50/70 transition-colors"
                >
                    <td class="px-5 py-3.5 font-semibold text-slate-800 text-sm whitespace-nowrap">
                        {{ formatDate(ot.date) }}
                    </td>
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-bold text-xs font-mono text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            {{ Math.floor(ot.duration_minutes / 60) }}j {{ ot.duration_minutes % 60 }}m
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-slate-700 text-xs">
                        {{ ot.reason || '-' }}
                    </td>
                    <td class="px-4 py-3.5 text-center text-slate-600 font-medium text-xs whitespace-nowrap">
                        <span class="inline-flex items-center justify-center font-semibold text-xs text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                            {{ ot.compensation_type || 'Uang Lembur' }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                        <Badge variant="success" pill>
                            Disetujui
                        </Badge>
                    </td>
                </tr>
            </template>

            <template v-else>
                <TableEmpty
                    :colspan="5"
                    title="Tidak ada riwayat lembur"
                    message="Tidak ada data lembur untuk karyawan ini pada periode yang dipilih."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="overtimes" />
    </div>
</template>
