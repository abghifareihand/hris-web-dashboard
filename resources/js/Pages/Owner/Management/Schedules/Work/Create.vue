<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import MultiSelect from '@/Components/UI/MultiSelect.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    shifts: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    holidays: {
        type: Array,
        default: () => [],
    },
});

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

// Employee Filter & Selection
const employeeFilters = reactive({
    search: '',
    branch_id: '',
    division_id: '',
});

const employeeOptions = ref([]);
const isLoadingEmployees = ref(false);

const fetchEmployees = async () => {
    isLoadingEmployees.value = true;
    try {
        const params = new URLSearchParams({
            search: employeeFilters.search,
            branch_id: employeeFilters.branch_id,
            division_id: employeeFilters.division_id,
        });
        const res = await fetch(route('owner.management.schedules.work.api.employees') + '?' + params.toString());
        const data = await res.json();
        employeeOptions.value = data;
    } catch (err) {
        console.error('Error fetching employees:', err);
    } finally {
        isLoadingEmployees.value = false;
    }
};

const { debouncedFn: debouncedEmployeeSearch } = useDebounce(() => {
    fetchEmployees();
}, 350);

watch(() => employeeFilters.search, () => debouncedEmployeeSearch());
watch(() => employeeFilters.branch_id, () => fetchEmployees());
watch(() => employeeFilters.division_id, () => fetchEmployees());

onMounted(() => {
    fetchEmployees();
});

const branchFilterOptions = computed(() => [
    { value: '', label: 'Semua Cabang' },
    ...props.branches.map((b) => ({ value: b.id, label: b.name })),
]);

const divisionFilterOptions = computed(() => [
    { value: '', label: 'Semua Divisi' },
    ...props.divisions.map((d) => ({ value: d.id, label: d.name })),
]);

const modeOptions = [
    { value: 'hari', label: 'Berdasarkan Hari' },
    { value: 'tanggal', label: 'Berdasarkan Tanggal' },
];

const shiftOptionsWithOff = computed(() => [
    { value: '', label: 'Libur (kosongkan jadwal)' },
    ...props.shifts.map((s) => ({
        value: s.id,
        label: `${s.name} (${s.clock_in?.substring(0, 5)} - ${s.clock_out?.substring(0, 5)})`,
    })),
]);

const areaOptions = [
    { value: 'all', label: 'Semua Cabang' },
    { value: 'one', label: 'Satu Cabang' },
    { value: 'some', label: 'Beberapa Cabang' },
];

const branchSelectOptions = computed(() => [
    { value: '', label: 'Pilih Cabang...' },
    ...props.branches.map((b) => ({ value: b.id, label: b.name })),
]);

const daysList = [
    { key: 'senin', label: 'Senin' },
    { key: 'selasa', label: 'Selasa' },
    { key: 'rabu', label: 'Rabu' },
    { key: 'kamis', label: 'Kamis' },
    { key: 'jumat', label: 'Jumat' },
    { key: 'sabtu', label: 'Sabtu' },
    { key: 'minggu', label: 'Minggu' },
];

const form = useForm({
    employees: [],
    mode: 'hari', // 'hari' or 'tanggal'
    
    // Mode Hari
    months: [currentMonth],
    year: currentYear,
    shifts: {
        senin: '',
        selasa: '',
        rabu: '',
        kamis: '',
        jumat: '',
        sabtu: '',
        minggu: '',
    },
    area: {
        senin: 'all',
        selasa: 'all',
        rabu: 'all',
        kamis: 'all',
        jumat: 'all',
        sabtu: 'all',
        minggu: 'all',
    },
    branch_one: {
        senin: '',
        selasa: '',
        rabu: '',
        kamis: '',
        jumat: '',
        sabtu: '',
        minggu: '',
    },
    branch_some: {
        senin: [],
        selasa: [],
        rabu: [],
        kamis: [],
        jumat: [],
        sabtu: [],
        minggu: [],
    },

    // Mode Tanggal
    date: '',
    shift_date: '',
    area_date: 'all',
    branch_one_date: '',
    branch_some_date: [],
});

const isAllSelected = computed(() => {
    return employeeOptions.value.length > 0 && employeeOptions.value.every(e => form.employees.includes(e.id));
});

const toggleSelectAllEmployees = () => {
    if (isAllSelected.value) {
        const visibleIds = employeeOptions.value.map(e => e.id);
        form.employees = form.employees.filter(id => !visibleIds.includes(id));
    } else {
        const visibleIds = employeeOptions.value.map(e => e.id);
        const set = new Set([...form.employees, ...visibleIds]);
        form.employees = Array.from(set);
    }
};

const toggleEmployee = (empId) => {
    const idx = form.employees.indexOf(empId);
    if (idx > -1) {
        form.employees.splice(idx, 1);
    } else {
        form.employees.push(empId);
    }
};

const monthsList = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const yearsList = computed(() => {
    const base = currentYear || new Date().getFullYear();
    return [base - 2, base - 1, base, base + 1, base + 2].map((y) => ({
        value: y,
        label: String(y),
    }));
});

const submit = () => {
    form.post(route('owner.management.schedules.work.store'));
};
</script>

<template>
    <Head title="Buat Jadwal Kerja Baru" />

    <div class="w-full space-y-6">
        <!-- Header with Back Icon Button -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.schedules.work.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Jadwal Kerja"
            >
                <svg
                    class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Buat Jadwal Kerja
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Atur jadwal shift kerja karyawan secara massal berdasarkan pola hari mingguan atau tanggal spesifik.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- STEP 1: Pilih Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-slate-900">Pilih Karyawan</h2>
                    <!-- Selection Count Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                        {{ form.employees.length }} Karyawan Dipilih
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Filter Bar Karyawan with Custom Select Components -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <Input
                            v-model="employeeFilters.search"
                            placeholder="Cari nama karyawan..."
                            clearable
                        />
                        <Select
                            v-model="employeeFilters.branch_id"
                            :options="branchFilterOptions"
                            placeholder="Semua Cabang"
                        />
                        <Select
                            v-model="employeeFilters.division_id"
                            :options="divisionFilterOptions"
                            placeholder="Semua Divisi"
                        />
                    </div>

                    <!-- Karyawan List Table View (Baris ke bawah dengan Checkbox) -->
                    <div class="border border-slate-200/80 rounded-xl overflow-hidden bg-white shadow-2xs">
                        <div class="max-h-72 overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-slate-50 border-b border-slate-200/80 z-10">
                                    <tr>
                                        <th class="w-12 px-4 py-3 text-center">
                                            <Checkbox
                                                :model-value="isAllSelected"
                                                @update:model-value="toggleSelectAllEmployees"
                                                :disabled="employeeOptions.length === 0"
                                            />
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-700">
                                            Nama Karyawan
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-700">
                                            Cabang
                                        </th>
                                        <th class="px-4 py-3 text-xs font-semibold text-slate-700">
                                            Divisi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <tr v-if="isLoadingEmployees">
                                        <td colspan="4" class="text-center py-8 text-xs text-slate-400">
                                            <div class="flex items-center justify-center gap-2">
                                                <svg class="animate-spin h-4 w-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                <span>Memuat daftar karyawan...</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="employeeOptions.length === 0">
                                        <td colspan="4" class="text-center py-8 text-xs text-slate-400 italic">
                                            Tidak ada karyawan yang cocok dengan kriteria filter.
                                        </td>
                                    </tr>
                                    <tr
                                        v-else
                                        v-for="emp in employeeOptions"
                                        :key="emp.id"
                                        @click="toggleEmployee(emp.id)"
                                        :class="[
                                            'transition-colors cursor-pointer select-none',
                                            form.employees.includes(emp.id)
                                                ? 'bg-emerald-50/50 hover:bg-emerald-50/80'
                                                : 'hover:bg-slate-50/70'
                                        ]"
                                    >
                                        <td class="w-12 px-4 py-3 text-center" @click.stop>
                                            <Checkbox
                                                v-model="form.employees"
                                                :value="emp.id"
                                            />
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ emp.name }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs sm:text-sm">
                                            {{ emp.branch_name || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs sm:text-sm">
                                            {{ emp.division_name || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p v-if="form.errors.employees" class="text-xs text-rose-500 mt-1">{{ form.errors.employees }}</p>
                </div>
            </div>

            <!-- STEP 2: Pilih Metode & Konfigurasi Shift -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">Informasi Jadwal Kerja</h2>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Mode Pengisian Jadwal Dropdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <Select
                            label="Mode Pengisian Jadwal"
                            v-model="form.mode"
                            :options="modeOptions"
                        />
                    </div>

                    <!-- Dynamic Sub-Heading with Divider (Inner Divider sesuai padding card) -->
                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                            {{ form.mode === 'hari' ? 'Pengaturan Berdasarkan Hari' : 'Pengaturan Berdasarkan Tanggal' }}
                        </h3>
                    </div>

                    <!-- SUB-FORM A: Mode Hari Mingguan -->
                    <div v-if="form.mode === 'hari'" class="space-y-6">
                        <!-- Pilih Bulan & Tahun -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <MultiSelect
                                    label="Pilih Bulan"
                                    v-model="form.months"
                                    :options="monthsList"
                                    placeholder="Pilih Bulan..."
                                    required
                                />
                                <p v-if="form.errors.months" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.months }}
                                </p>
                            </div>
                            <div>
                                <Select
                                    label="Pilih Tahun"
                                    v-model="form.year"
                                    :options="yearsList"
                                    placeholder="Pilih Tahun..."
                                    required
                                />
                                <p v-if="form.errors.year" class="text-xs text-rose-500 mt-1">
                                    {{ form.errors.year }}
                                </p>
                            </div>
                        </div>

                        <!-- Pola Hari: Senin s/d Minggu (Card 2 Kolom) -->
                        <div class="space-y-4">
                            <label class="form-label form-label-required">
                                Atur Shift Tiap Hari
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4.5">
                            <div
                                v-for="d in daysList"
                                :key="d.key"
                                class="p-4 sm:p-5 bg-white rounded-xl border space-y-4 transition-colors"
                                :class="form.shifts[d.key] ? 'border-emerald-500' : 'border-slate-200'"
                            >
                                <!-- Header Card Hari -->
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full"
                                            :class="form.shifts[d.key] ? 'bg-emerald-500' : 'bg-slate-300'"
                                        />
                                        <h4 class="font-bold text-sm text-slate-800 capitalize">
                                            {{ d.label }}
                                        </h4>
                                    </div>
                                    <span
                                        class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                        :class="form.shifts[d.key] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ form.shifts[d.key] ? 'Masuk Kerja' : 'Libur' }}
                                    </span>
                                </div>

                                <!-- Shift Picker -->
                                <div>
                                    <Select
                                        label="Shift Kerja"
                                        v-model="form.shifts[d.key]"
                                        :options="shiftOptionsWithOff"
                                        placeholder="Pilih Shift"
                                    />
                                </div>

                                <!-- Area Absensi (Hanya muncul jika memilih Shift Kerja bukan libur) -->
                                <div v-if="form.shifts[d.key]" class="space-y-3 pt-3 border-t border-slate-100 animate-in fade-in duration-150">
                                    <label class="block text-xs font-semibold text-slate-700">
                                        Area Absensi
                                    </label>

                                    <div class="space-y-3">
                                        <!-- Opsi 1: Semua Cabang -->
                                        <div>
                                            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                                <input
                                                    type="radio"
                                                    :name="'area_' + d.key"
                                                    v-model="form.area[d.key]"
                                                    value="all"
                                                    class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                                />
                                                <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                                    Semua Cabang bisa digunakan untuk lokasi absensi
                                                </span>
                                            </label>
                                        </div>

                                        <!-- Opsi 2: Hanya Satu Cabang -->
                                        <div class="space-y-2.5">
                                            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                                <input
                                                    type="radio"
                                                    :name="'area_' + d.key"
                                                    v-model="form.area[d.key]"
                                                    value="one"
                                                    class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                                />
                                                <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                                    Hanya Satu Cabang yang digunakan untuk lokasi absensi
                                                </span>
                                            </label>

                                            <!-- Dropdown Satu Cabang -->
                                            <div v-if="form.area[d.key] === 'one'" class="pl-6.5 max-w-md">
                                                <Select
                                                    v-model="form.branch_one[d.key]"
                                                    :options="branchSelectOptions"
                                                    placeholder="Pilih Cabang..."
                                                />
                                            </div>
                                        </div>

                                        <!-- Opsi 3: Beberapa Cabang -->
                                        <div class="space-y-2.5">
                                            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                                <input
                                                    type="radio"
                                                    :name="'area_' + d.key"
                                                    v-model="form.area[d.key]"
                                                    value="some"
                                                    class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                                />
                                                <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                                    Beberapa Cabang bisa digunakan untuk lokasi absensi
                                                </span>
                                            </label>

                                            <!-- List Checkbox Beberapa Cabang -->
                                            <div v-if="form.area[d.key] === 'some'" class="pl-6.5 space-y-2.5 max-h-48 overflow-y-auto pr-1">
                                                <div
                                                    v-for="branch in props.branches"
                                                    :key="branch.id"
                                                >
                                                    <Checkbox
                                                        v-model="form.branch_some[d.key]"
                                                        :value="branch.id"
                                                        :label="branch.name"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-FORM B: Mode Tanggal Spesifik -->
                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <DatePicker
                                label="Tanggal"
                                v-model="form.date"
                                placeholder="Pilih Tanggal"
                                required
                            />
                        </div>

                        <div>
                            <Select
                                label="Shift Kerja"
                                v-model="form.shift_date"
                                :options="shiftOptionsWithOff"
                                placeholder="Pilih Shift"
                                required
                            />
                        </div>
                    </div>

                    <!-- Area Absensi (Hanya muncul jika memilih Shift Kerja) -->
                    <div v-if="form.shift_date" class="space-y-3 pt-1">
                        <label class="block text-xs font-semibold text-slate-700">
                            Area Absensi
                        </label>

                        <div class="space-y-3">
                            <!-- Opsi 1: Semua Cabang -->
                            <div>
                                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                    <input
                                        type="radio"
                                        v-model="form.area_date"
                                        value="all"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                    <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                        Semua Cabang bisa digunakan untuk lokasi absensi
                                    </span>
                                </label>
                            </div>

                            <!-- Opsi 2: Hanya Satu Cabang -->
                            <div class="space-y-2.5">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                    <input
                                        type="radio"
                                        v-model="form.area_date"
                                        value="one"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                    <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                        Hanya Satu Cabang yang digunakan untuk lokasi absensi
                                    </span>
                                </label>

                                <!-- Dropdown Satu Cabang (Gambar 2) -->
                                <div v-if="form.area_date === 'one'" class="pl-6.5 max-w-md">
                                    <Select
                                        v-model="form.branch_one_date"
                                        :options="branchSelectOptions"
                                        placeholder="Pilih Cabang..."
                                    />
                                    <p v-if="form.errors.branch_one_date" class="text-xs text-rose-500 mt-1">
                                        {{ form.errors.branch_one_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Opsi 3: Beberapa Cabang -->
                            <div class="space-y-2.5">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none group">
                                    <input
                                        type="radio"
                                        v-model="form.area_date"
                                        value="some"
                                        class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                    <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 transition-colors">
                                        Beberapa Cabang bisa digunakan untuk lokasi absensi
                                    </span>
                                </label>

                                <!-- List Checkbox Beberapa Cabang (Gambar 3) -->
                                <div v-if="form.area_date === 'some'" class="pl-6.5 space-y-2.5">
                                    <div
                                        v-for="branch in props.branches"
                                        :key="branch.id"
                                    >
                                        <Checkbox
                                            v-model="form.branch_some_date"
                                            :value="branch.id"
                                            :label="branch.name"
                                        />
                                    </div>
                                    <p v-if="form.errors.branch_some_date" class="text-xs text-rose-500 mt-1">
                                        {{ form.errors.branch_some_date }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.schedules.work.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
