<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    balances: {
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
    leaveCategories: {
        type: Array,
        default: () => [],
    },
    currentYear: {
        type: Number,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    year: props.filters.year || props.currentYear,
    search: props.filters.search || '',
    branch_id: props.filters.branch_id || '',
    division_id: props.filters.division_id || '',
    leave_category_id: props.filters.leave_category_id || '',
});

const applyFilters = () => {
    router.get(
        route('owner.management.leaves.balance.index'),
        {
            year: filters.year,
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            leave_category_id: filters.leave_category_id || undefined,
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

const years = [2024, 2025, 2026, 2027, 2028];
</script>

<template>
    <Head title="Sisa Kuota Cuti Karyawan" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sisa Kuota Cuti Karyawan</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Pantau alokasi jatah cuti tahunan, jumlah yang telah dipakai, dan sisa hari untuk setiap karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.leaves.index')">
                    <Button variant="secondary">
                        Riwayat Cuti
                    </Button>
                </Link>
                <Link :href="route('owner.management.leaves.categories.index')">
                    <Button variant="primary">
                        Kelola Kategori Cuti
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Tahun -->
                <div>
                    <select
                        v-model="filters.year"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs text-slate-700 py-2 px-3"
                    >
                        <option v-for="y in years" :key="y" :value="y">Tahun {{ y }}</option>
                    </select>
                </div>

                <!-- Cabang -->
                <div>
                    <select
                        v-model="filters.branch_id"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs text-slate-700 py-2 px-3"
                    >
                        <option value="">Semua Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>

                <!-- Divisi -->
                <div>
                    <select
                        v-model="filters.division_id"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs text-slate-700 py-2 px-3"
                    >
                        <option value="">Semua Divisi</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- Kategori Cuti -->
                <div>
                    <select
                        v-model="filters.leave_category_id"
                        @change="applyFilters"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs text-slate-700 py-2 px-3"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="cat in leaveCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <Input
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Nama atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Karyawan', 'Kategori Cuti', 'Tahun', 'Kuota Diberikan', 'Terpakai', 'Sisa Cuti', 'Aksi']">
            <template v-if="balances.data && balances.data.length > 0">
                <tr v-for="item in balances.data" :key="item.id" class="hover:bg-slate-50/70 transition">
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
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-600 whitespace-nowrap">
                        {{ item.year }}
                    </td>
                    <td class="px-5 py-3.5 font-semibold text-sm text-slate-800 whitespace-nowrap">
                        {{ item.quota }} Hari
                    </td>
                    <td class="px-5 py-3.5 text-sm text-rose-600 font-semibold whitespace-nowrap">
                        {{ item.used }} Hari
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold font-mono',
                                (item.quota - item.used) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                            ]"
                        >
                            {{ item.quota - item.used }} Hari Sisa
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <Link :href="route('owner.management.leaves.balance.edit', item.id)">
                            <Button variant="ghost" size="sm">Edit Kuota</Button>
                        </Link>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty :colspan="7" message="Belum ada data saldo cuti karyawan untuk periode ini." />
            </template>
        </DataTable>

        <TablePagination :pagination="balances" />
    </div>
</template>
