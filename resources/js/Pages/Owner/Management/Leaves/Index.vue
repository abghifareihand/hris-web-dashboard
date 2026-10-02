<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
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
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Riwayat Pengajuan Cuti</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Daftar histori seluruh pengajuan cuti karyawan yang telah diproses.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.leaves.pending.index')">
                    <Button variant="secondary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Persetujuan Tertunda
                    </Button>
                </Link>
                <Link :href="route('owner.management.leaves.balance.index')">
                    <Button variant="secondary">
                        Sisa Kuota Cuti
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <Input
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama atau NIP..."
                    />
                </div>

                <!-- Status -->
                <div>
                    <Select
                        v-model="filters.status"
                        :options="[
                            { value: '', label: 'Semua Status' },
                            { value: 'approved', label: 'Disetujui', dotClass: 'bg-emerald-500' },
                            { value: 'rejected', label: 'Ditolak', dotClass: 'bg-rose-500' },
                        ]"
                        size="sm"
                        @change="applyFilters"
                    />
                </div>

                <!-- Cabang -->
                <div>
                    <Select
                        v-model="filters.branch_id"
                        :options="branches.map(b => ({ value: b.id, label: b.name }))"
                        all-label="Semua Cabang"
                        size="sm"
                        @change="applyFilters"
                    />
                </div>

                <!-- Divisi -->
                <div>
                    <Select
                        v-model="filters.division_id"
                        :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                        all-label="Semua Divisi"
                        size="sm"
                        @change="applyFilters"
                    />
                </div>

                <!-- Start Date -->
                <div>
                    <Input
                        v-model="filters.start_date"
                        type="date"
                        @change="applyFilters"
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
                        <span v-if="item.end_date && item.end_date !== item.start_date" class="text-slate-400">
                            &mdash; {{ formatDate(item.end_date) }}
                        </span>
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
