<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
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
        senin: props.shifts[0]?.id || '',
        selasa: props.shifts[0]?.id || '',
        rabu: props.shifts[0]?.id || '',
        kamis: props.shifts[0]?.id || '',
        jumat: props.shifts[0]?.id || '',
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
    date: new Date().toISOString().substring(0, 10),
    shift_date: props.shifts[0]?.id || '',
    area_date: 'all',
    branch_one_date: '',
    branch_some_date: [],
});

const toggleSelectAllEmployees = () => {
    if (form.employees.length === employeeOptions.value.length) {
        form.employees = [];
    } else {
        form.employees = employeeOptions.value.map(e => e.id);
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

const submit = () => {
    form.post(route('owner.management.schedules.work.store'));
};
</script>

<template>
    <Head title="Buat Jadwal Kerja Baru" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Buat Jadwal Kerja</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Atur jadwal shift kerja karyawan secara massal berdasarkan pola hari mingguan atau tanggal spesifik.
                </p>
            </div>
            <Link :href="route('owner.management.schedules.work.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- STEP 1: Pilih Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">1. Pilih Karyawan</h2>
                        <p class="text-xs text-slate-500">
                            Pilih karyawan yang akan diberikan penugasan jadwal ini ({{ form.employees.length }} terpilih).
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="toggleSelectAllEmployees"
                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 underline text-left"
                    >
                        {{ form.employees.length === employeeOptions.length ? 'Batal Pilih Semua' : 'Pilih Semua Karyawan' }}
                    </button>
                </div>

                <!-- Filter Bar Karyawan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <Input
                        v-model="employeeFilters.search"
                        placeholder="Cari nama karyawan..."
                    />
                    <select
                        v-model="employeeFilters.branch_id"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="">Semua Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <select
                        v-model="employeeFilters.division_id"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="">Semua Divisi</option>
                        <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- Karyawan Checkbox Grid -->
                <div class="border border-slate-200 rounded-xl p-4 max-h-56 overflow-y-auto bg-slate-50/50">
                    <div v-if="isLoadingEmployees" class="text-center py-6 text-xs text-slate-400">
                        Memuat daftar karyawan...
                    </div>
                    <div v-else-if="employeeOptions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <label
                            v-for="emp in employeeOptions"
                            :key="emp.id"
                            class="flex items-center gap-2.5 p-2 bg-white rounded-lg border border-slate-200/80 hover:border-emerald-400 transition cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                :value="emp.id"
                                v-model="form.employees"
                                class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-slate-800 truncate">{{ emp.name }}</div>
                                <div class="text-[10px] text-slate-400 truncate">{{ emp.branch_name }} &bull; {{ emp.division_name }}</div>
                            </div>
                        </label>
                    </div>
                    <div v-else class="text-center py-6 text-xs text-slate-400 italic">
                        Tidak ada karyawan yang cocok dengan kriteria filter.
                    </div>
                </div>
                <p v-if="form.errors.employees" class="text-xs text-rose-500 mt-1">{{ form.errors.employees }}</p>
            </div>

            <!-- STEP 2: Pilih Metode & Konfigurasi Shift -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">2. Mode Pembuatan Jadwal</h2>
                        <p class="text-xs text-slate-500">Tentukan apakah ingin membuat pola mingguan atau satu tanggal spesifik.</p>
                    </div>
                    <!-- Mode Buttons -->
                    <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 text-xs font-semibold">
                        <button
                            type="button"
                            @click="form.mode = 'hari'"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition-all',
                                form.mode === 'hari' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600'
                            ]"
                        >
                            Pola Hari Mingguan
                        </button>
                        <button
                            type="button"
                            @click="form.mode = 'tanggal'"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition-all',
                                form.mode === 'tanggal' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-slate-600'
                            ]"
                        >
                            Satu Tanggal Spesifik
                        </button>
                    </div>
                </div>

                <!-- SUB-FORM A: Mode Hari Mingguan -->
                <div v-if="form.mode === 'hari'" class="space-y-6">
                    <!-- Bulan & Tahun -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-slate-700">Terapkan Pada Bulan:</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2">
                            <label
                                v-for="m in monthsList"
                                :key="m.value"
                                class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer text-xs font-medium text-slate-700 hover:bg-emerald-50 transition"
                            >
                                <input
                                    type="checkbox"
                                    :value="m.value"
                                    v-model="form.months"
                                    class="w-3.5 h-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>{{ m.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Pola Hari: Senin s/d Minggu -->
                    <div class="space-y-3 pt-4 border-t border-slate-100">
                        <label class="block text-sm font-semibold text-slate-700">Atur Shift Tiap Hari:</label>
                        <div class="space-y-3">
                            <div
                                v-for="d in daysList"
                                :key="d.key"
                                class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-3"
                            >
                                <div class="w-24 font-bold text-sm text-slate-800 capitalize">
                                    {{ d.label }}
                                </div>

                                <!-- Shift Picker -->
                                <div class="flex-1 max-w-xs">
                                    <select
                                        v-model="form.shifts[d.key]"
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2.5"
                                    >
                                        <option value="">Libur (Day Off)</option>
                                        <option v-for="s in shifts" :key="s.id" :value="s.id">
                                            {{ s.name }} ({{ s.clock_in?.substring(0,5) }} - {{ s.clock_out?.substring(0,5) }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Area Presensi -->
                                <div v-if="form.shifts[d.key]" class="flex-1 flex items-center gap-2">
                                    <select
                                        v-model="form.area[d.key]"
                                        class="text-xs rounded-lg border-slate-300 py-1.5 px-2 text-slate-700"
                                    >
                                        <option value="all">Semua Cabang</option>
                                        <option value="one">Satu Cabang</option>
                                        <option value="some">Beberapa Cabang</option>
                                    </select>

                                    <!-- Satu Cabang -->
                                    <select
                                        v-if="form.area[d.key] === 'one'"
                                        v-model="form.branch_one[d.key]"
                                        class="text-xs rounded-lg border-slate-300 py-1.5 px-2 text-slate-700 flex-1"
                                    >
                                        <option value="">Pilih Cabang</option>
                                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic">
                                    Hari Libur Bebas Presensi
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-FORM B: Mode Tanggal Spesifik -->
                <div v-else class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Pilih Tanggal</label>
                            <Input v-model="form.date" type="date" required />
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Pilih Shift</label>
                            <select
                                v-model="form.shift_date"
                                class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                            >
                                <option value="">Libur (Day Off)</option>
                                <option v-for="s in shifts" :key="s.id" :value="s.id">
                                    {{ s.name }} ({{ s.clock_in?.substring(0,5) }} - {{ s.clock_out?.substring(0,5) }})
                                </option>
                            </select>
                        </div>

                        <div v-if="form.shift_date" class="sm:col-span-2 space-y-3 pt-3 border-t border-slate-100">
                            <label class="block text-sm font-semibold text-slate-700">Area Cabang Presensi:</label>
                            <div class="flex items-center gap-3">
                                <select
                                    v-model="form.area_date"
                                    class="text-sm rounded-xl border-slate-300 py-2 px-3 text-slate-700"
                                >
                                    <option value="all">Bebas di Semua Cabang</option>
                                    <option value="one">Hanya di Satu Cabang</option>
                                </select>

                                <select
                                    v-if="form.area_date === 'one'"
                                    v-model="form.branch_one_date"
                                    class="text-sm rounded-xl border-slate-300 py-2 px-3 text-slate-700 flex-1"
                                >
                                    <option value="">Pilih Cabang</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3">
                <Link :href="route('owner.management.schedules.work.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Terapkan Jadwal Kerja
                </Button>
            </div>
        </form>
    </div>
</template>
