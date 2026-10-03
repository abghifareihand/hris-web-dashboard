<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Badge from '@/Components/UI/Badge.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    leaves: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    branch_id: props.filters.branch_id || '',
    division_id: props.filters.division_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const applyFilters = () => {
    router.get(
        route('owner.management.leaves.index'),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const { debouncedFn: debouncedSearch } = useDebounce(() => {
    applyFilters();
}, 350);

const onSearchInput = (val) => {
    filters.search = val;
    debouncedSearch();
};

const onFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.branch_id = '';
    filters.division_id = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.status ||
        filters.branch_id ||
        filters.division_id ||
        filters.start_date ||
        filters.end_date
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const statusVariant = (status) => {
    switch (status) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        default: return 'warning';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'approved': return 'Disetujui';
        case 'rejected': return 'Ditolak';
        default: return 'Menunggu';
    }
};
</script>

<template>
    <Head title="Riwayat Seluruh Cuti Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Riwayat Pengajuan Cuti</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar histori seluruh pengajuan cuti karyawan yang telah diproses.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Riwayat Cuti
                </h2>

                <!-- Reset Filter Button -->
                <transition
                    enter-active-class="transition-opacity duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <Button
                        v-if="hasActiveFilters()"
                        variant="secondary"
                        size="sm"
                        type="button"
                        @click="resetFilters"
                        class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>Reset</span>
                    </Button>
                </transition>
            </div>

            <div class="p-6 space-y-4">
                <!-- Row 1: Dropdown Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <Select
                            label="Status"
                            v-model="filters.status"
                            :options="[
                                { value: '', label: 'Semua Status' },
                                { value: 'approved', label: 'Disetujui', dotClass: 'bg-emerald-500' },
                                { value: 'rejected', label: 'Ditolak', dotClass: 'bg-rose-500' },
                            ]"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Branch Filter -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map(b => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Division Filter -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <DatePicker
                            label="Tanggal"
                            v-model="filters.start_date"
                            placeholder="Pilih Tanggal"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Search field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama karyawan atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Karyawan', 'Jenis Cuti', 'Periode Cuti', 'Durasi', 'Status', 'Alasan / Catatan']">
            <template v-if="leaves.data && leaves.data.length > 0">
                <tr v-for="item in leaves.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">{{ item.employee?.name || '-' }}</div>
                        <div class="text-xs text-slate-400">
                            {{ item.employee?.branch?.name || '-' }} &bull; {{ item.employee?.division?.name || '-' }}
                        </div>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                            {{ item.leave_category?.name || 'Cuti' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.start_date) }}
                        <template v-if="item.end_date && item.end_date !== item.start_date">
                            &mdash; {{ formatDate(item.end_date) }}
                        </template>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                            {{ item.days_count || 1 }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge :variant="statusVariant(item.status)" size="sm">
                            {{ statusLabel(item.status) }}
                        </Badge>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs">
                        <div>{{ item.reason || '-' }}</div>
                        <div v-if="item.reject_reason" class="text-rose-600 mt-0.5 italic">
                            Catatan: {{ item.reject_reason }}
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty :colspan="6" message="Tidak ada riwayat pengajuan cuti yang cocok dengan filter." />
            </template>
        </DataTable>

        <TablePagination :pagination="leaves" />
    </div>
</template>
