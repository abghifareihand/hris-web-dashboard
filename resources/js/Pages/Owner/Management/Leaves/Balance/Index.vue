<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
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

const onFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    filters.search = '';
    filters.year = props.currentYear;
    filters.branch_id = '';
    filters.division_id = '';
    filters.leave_category_id = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.leave_category_id ||
        String(filters.year) !== String(props.currentYear)
    );
};

const years = [2024, 2025, 2026, 2027, 2028];

const getRemainingBadge = (quota, used) => {
    const remaining = Number(quota || 0) - Number(used || 0);
    if (remaining <= 0) {
        return {
            classes: 'bg-rose-50 text-rose-700 border-rose-200/80',
            dot: 'bg-rose-500',
            text: 'Habis (0 Hari)',
        };
    }
    if (remaining <= 3) {
        return {
            classes: 'bg-rose-50 text-rose-700 border-rose-200/80',
            dot: 'bg-rose-500',
            text: `${remaining} Hari Tersisa`,
        };
    }
    return {
        classes: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        dot: 'bg-emerald-500',
        text: `${remaining} Hari Tersisa`,
    };
};
</script>

<template>
    <Head title="Sisa Kuota Cuti Karyawan" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sisa Kuota Cuti Karyawan</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Pantau alokasi jatah cuti tahunan, jumlah yang telah dipakai, dan sisa hari untuk setiap karyawan.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Sisa Kuota Cuti
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
                    <!-- Tahun -->
                    <div>
                        <Select
                            label="Tahun"
                            v-model="filters.year"
                            :options="years.map(y => ({ value: y, label: `Tahun ${y}` }))"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Cabang -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map(b => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map(d => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Kategori Cuti -->
                    <div>
                        <Select
                            label="Kategori Cuti"
                            v-model="filters.leave_category_id"
                            :options="leaveCategories.map(cat => ({ value: cat.id, label: cat.name }))"
                            all-label="Semua Kategori"
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
        <DataTable :headers="['Karyawan', 'Kategori Cuti', 'Tahun', 'Jatah Cuti', 'Cuti Terpakai', 'Sisa Cuti', '']">
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
                    <td class="px-5 py-3.5 text-sm text-slate-600 whitespace-nowrap">
                        {{ item.year }}
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                            {{ item.quota }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border',
                                item.used > 0
                                    ? 'bg-amber-50 text-amber-700 border-amber-200/80'
                                    : 'bg-slate-50 text-slate-500 border-slate-200/80'
                            ]"
                        >
                            {{ item.used }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border',
                                getRemainingBadge(item.quota, item.used).classes
                            ]"
                        >
                            <span
                                :class="[
                                    'w-1.5 h-1.5 rounded-full shrink-0',
                                    getRemainingBadge(item.quota, item.used).dot
                                ]"
                            ></span>
                            <span>
                                {{ getRemainingBadge(item.quota, item.used).text }}
                            </span>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end">
                            <Link
                                :href="route('owner.management.leaves.balance.edit', item.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Kuota Cuti"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </Link>
                        </div>
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
