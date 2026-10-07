<script setup>
import { reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Select from '@/Components/UI/Select.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'
import { useDebounce } from '@/Composables/useDebounce'

const props = defineProps({
    employees: Object,
    bpjsTk: Object,
    bpjsKes: Object,
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
})

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    salary_status: props.filters?.salary_status || '',
})

const applyFilters = () => {
    router.get(
        route('owner.finance.payrolls.master.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            salary_status: filters.salary_status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const { debouncedFn: debouncedSearch } = useDebounce(() => {
    applyFilters()
}, 350)

const onSearchInput = (val) => {
    filters.search = val
    debouncedSearch()
}

const onFilterChange = () => {
    applyFilters()
}

const resetFilters = () => {
    filters.search = ''
    filters.branch_id = ''
    filters.division_id = ''
    filters.salary_status = ''
    applyFilters()
}

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.salary_status
    )
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0)
}

const getBpjsKesRate = (emp) => {
    if (!emp.bpjs_kesehatan_no) return null
    const k = props.bpjsKes?.employee_percent ? Number(props.bpjsKes.employee_percent) : 1
    const p = props.bpjsKes?.company_percent ? Number(props.bpjsKes.company_percent) : 4
    return { k: `${k}%`, p: `${p}%` }
}

const getJhtRate = (emp) => {
    if (!emp.jht_no) return null
    const k = props.bpjsTk?.jht_employee_percent ? Number(props.bpjsTk.jht_employee_percent) : 2
    const p = props.bpjsTk?.jht_company_percent ? Number(props.bpjsTk.jht_company_percent) : 3.7
    return { k: `${k}%`, p: `${p}%` }
}

const getJpRate = (emp) => {
    if (!emp.jp_no) return null
    const k = props.bpjsTk?.jp_employee_percent ? Number(props.bpjsTk.jp_employee_percent) : 1
    const p = props.bpjsTk?.jp_company_percent ? Number(props.bpjsTk.jp_company_percent) : 2
    return { k: `${k}%`, p: `${p}%` }
}

const getJkkRate = (emp) => {
    if (!emp.jkk_no && !emp.jht_no) return null
    const val = props.bpjsTk?.jkk_percent ? Number(props.bpjsTk.jkk_percent) : 0.24
    return `${val}%`
}

const getJkmRate = (emp) => {
    if (!emp.jkm_no && !emp.jht_no) return null
    const val = props.bpjsTk?.jkm_percent ? Number(props.bpjsTk.jkm_percent) : 0.3
    return `${val}%`
}
</script>

<template>
    <AppLayout>
        <Head title="Master Penggajian Karyawan - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Komponen Gaji Karyawan</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Daftar konfigurasi gaji pokok, tunjangan, upah lembur, dan data kepesertaan BPJS per karyawan.
                </p>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div
                    class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]"
                >
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Master Gaji
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
                    <!-- Row 1: Dropdown Filters (Cabang, Divisi, Status Gaji) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Cabang -->
                        <div>
                            <Select
                                label="Cabang"
                                v-model="filters.branch_id"
                                :options="branches.map((b) => ({ value: b.id, label: b.name }))"
                                all-label="Semua Cabang"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Divisi -->
                        <div>
                            <Select
                                label="Divisi"
                                v-model="filters.division_id"
                                :options="divisions.map((d) => ({ value: d.id, label: d.name }))"
                                all-label="Semua Divisi"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Status Gaji -->
                        <div>
                            <Select
                                label="Status Gaji"
                                v-model="filters.salary_status"
                                :options="[
                                    { value: '', label: 'Semua Status Gaji' },
                                    { value: 'configured', label: 'Gaji Sudah Diatur', dotClass: 'bg-emerald-500' },
                                    { value: 'unconfigured', label: 'Gaji Belum Diatur', dotClass: 'bg-rose-500' },
                                ]"
                                all-label="Semua Status Gaji"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Search field -->
                    <div class="pt-3 border-t border-slate-100">
                        <Input
                            label="Pencarian Karyawan"
                            :modelValue="filters.search"
                            @update:modelValue="onSearchInput"
                            placeholder="Ketik nama karyawan atau NIP untuk mencari..."
                            clearable
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :headers="[
                    'Karyawan',
                    'Gaji Pokok',
                    'Tunj. Tetap',
                    'Tunj. Harian',
                    'Lembur/Jam',
                    'Denda Terlambat',
                    'BPJS Kes (K/P)',
                    'JHT (K/P)',
                    'JP (K/P)',
                    'JKK',
                    'JKM',
                    'Status Pajak',
                    '',
                ]"
            >
                <template v-if="employees.data && employees.data.length > 0">
                    <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-slate-50/70 transition">
                        <!-- Karyawan -->
                        <td class="px-5 py-3.5 whitespace-nowrap text-left">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="emp.user?.avatar"
                                    :src="emp.user.avatar"
                                    :alt="emp.name"
                                    class="w-9 h-9 rounded-full object-cover shrink-0 border border-slate-200"
                                />
                                <div
                                    v-else
                                    class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs"
                                >
                                    {{ emp.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 truncate">
                                        {{ emp.name }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">
                                        {{ emp.nip || '-' }} &bull; {{ emp.division?.name || '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Gaji Pokok -->
                        <td class="px-4 py-3.5 whitespace-nowrap font-bold text-slate-900">
                            <span v-if="emp.basic_salary && emp.basic_salary > 0">{{ formatCurrency(emp.basic_salary) }}</span>
                            <span v-else class="text-xs text-rose-600 font-semibold bg-rose-50 border border-rose-200/80 px-2.5 py-0.5 rounded-full">Belum Diatur</span>
                        </td>

                        <!-- Tunj. Tetap -->
                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-800">
                            {{ formatCurrency(emp.fixed_allowance) }}
                        </td>

                        <!-- Tunj. Harian -->
                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-800">
                            {{ formatCurrency(emp.daily_allowance) }}
                        </td>

                        <!-- Lembur/Jam -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="!text-emerald-600">
                                {{ formatCurrency(emp.overtime_rate_per_hour) }}
                            </span>
                        </td>

                        <!-- Denda Terlambat -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span
                                v-if="emp.late_penalty_type === 'flat'"
                                class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-300 text-[11px] font-medium leading-normal"
                            >
                                Flat: {{ formatCurrency(emp.late_penalty_nominal) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 border border-blue-300 text-[11px] font-medium leading-normal"
                            >
                                Prorate: {{ formatCurrency(emp.late_penalty_nominal) }}
                            </span>
                        </td>

                        <!-- BPJS Kes (K/P) -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div v-if="getBpjsKesRate(emp)" class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                    <span class="font-bold text-slate-500">K</span>
                                    <span>{{ getBpjsKesRate(emp).k }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                    <span class="font-bold text-blue-500">P</span>
                                    <span>{{ getBpjsKesRate(emp).p }}</span>
                                </span>
                            </div>
                            <span v-else class="text-slate-400 font-mono text-xs pl-1">-</span>
                        </td>

                        <!-- JHT (K/P) -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div v-if="getJhtRate(emp)" class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                    <span class="font-bold text-slate-500">K</span>
                                    <span>{{ getJhtRate(emp).k }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                    <span class="font-bold text-blue-500">P</span>
                                    <span>{{ getJhtRate(emp).p }}</span>
                                </span>
                            </div>
                            <span v-else class="text-slate-400 font-mono text-xs pl-1">-</span>
                        </td>

                        <!-- JP (K/P) -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div v-if="getJpRate(emp)" class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                    <span class="font-bold text-slate-500">K</span>
                                    <span>{{ getJpRate(emp).k }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                    <span class="font-bold text-blue-500">P</span>
                                    <span>{{ getJpRate(emp).p }}</span>
                                </span>
                            </div>
                            <span v-else class="text-slate-400 font-mono text-xs pl-1">-</span>
                        </td>

                        <!-- JKK -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span v-if="getJkkRate(emp)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                {{ getJkkRate(emp) }}
                            </span>
                            <span v-else class="text-slate-400 font-mono text-xs pl-1">-</span>
                        </td>

                        <!-- JKM -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span v-if="getJkmRate(emp)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                {{ getJkmRate(emp) }}
                            </span>
                            <span v-else class="text-slate-400 font-mono text-xs pl-1">-</span>
                        </td>

                        <!-- Status Pajak -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap"
                                :class="emp.taxable ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/80' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                            >
                                {{ emp.taxable ? 'Kena Pajak' : 'Bebas Pajak' }} ({{ emp.ptkp_status || 'TK/0' }})
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="px-4 py-3.5 whitespace-nowrap text-center">
                            <Link
                                :href="route('owner.management.employees.edit', emp.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition inline-flex items-center justify-center"
                                title="Edit Komponen Gaji"
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
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <TableEmpty
                        title="Tidak ada data karyawan"
                        message="Tidak ada data karyawan yang cocok dengan filter pencarian."
                        :colspan="13"
                    />
                </template>
            </DataTable>

            <!-- Pagination -->
            <TablePagination :pagination="employees" />
        </div>
    </AppLayout>
</template>
