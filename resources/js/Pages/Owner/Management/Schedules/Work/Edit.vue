<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    schedule: {
        type: Object,
        required: true,
    },
    shifts: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
});

const initialBranchOne = () => {
    if (props.schedule.attendance_type === 'one' && props.schedule.allowed_branches) {
        return props.schedule.allowed_branches[0] || '';
    }
    return '';
};

const initialBranchSome = () => {
    if (props.schedule.attendance_type === 'some' && props.schedule.allowed_branches) {
        return props.schedule.allowed_branches || [];
    }
    return [];
};

const form = useForm({
    is_day_off: Boolean(props.schedule.is_day_off),
    shift_id: props.schedule.shift_id || '',
    attendance_type: props.schedule.attendance_type || 'all',
    branch_one: initialBranchOne(),
    branch_some: initialBranchSome(),
});

const submit = () => {
    form.put(route('owner.management.schedules.work.update', props.schedule.id));
};
</script>

<template>
    <Head :title="`Edit Jadwal - ${schedule.employee?.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Jadwal Kerja</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Sesuaikan penugasan shift kerja perorangan untuk tanggal tertentu.
                </p>
            </div>
            <Link :href="route('owner.management.schedules.work.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <!-- Info Card Karyawan -->
        <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-4 flex items-center justify-between gap-4">
            <div>
                <div class="font-bold text-slate-900">{{ schedule.employee?.name }}</div>
                <div class="text-xs text-slate-500 mt-0.5">
                    {{ schedule.employee?.division?.name || '-' }} &bull; {{ schedule.employee?.position?.name || '-' }}
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold text-slate-500">Tanggal Tugas</div>
                <div class="text-sm font-bold font-mono text-emerald-800">{{ schedule.date }}</div>
            </div>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
            <!-- Pilihan Hari Libur -->
            <div>
                <label class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="form.is_day_off"
                        class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Hari Libur (Day Off)</div>
                        <div class="text-xs text-slate-400">Karyawan tidak memiliki kewajiban absensi pada hari ini.</div>
                    </div>
                </label>
            </div>

            <!-- Konfigurasi Shift (Jika Bukan Libur) -->
            <div v-if="!form.is_day_off" class="space-y-4 pt-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Pilih Shift Kerja <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.shift_id"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                        required
                    >
                        <option value="">Pilih Shift</option>
                        <option v-for="s in shifts" :key="s.id" :value="s.id">
                            {{ s.name }} ({{ s.clock_in?.substring(0, 5) }} - {{ s.clock_out?.substring(0, 5) }})
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Area Presensi Kehadiran
                    </label>
                    <select
                        v-model="form.attendance_type"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="all">Bebas di Semua Cabang</option>
                        <option value="one">Hanya di Satu Cabang Tertentu</option>
                        <option value="some">Beberapa Cabang Terpilih</option>
                    </select>
                </div>

                <!-- Cabang Tunggal -->
                <div v-if="form.attendance_type === 'one'">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Pilih Lokasi Cabang
                    </label>
                    <select
                        v-model="form.branch_one"
                        class="w-full text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs py-2 px-3 text-slate-700"
                    >
                        <option value="">Pilih Cabang</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>

                <!-- Beberapa Cabang -->
                <div v-if="form.attendance_type === 'some'" class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700">Pilih Cabang yang Diizinkan:</label>
                    <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <label
                            v-for="b in branches"
                            :key="b.id"
                            class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                :value="b.id"
                                v-model="form.branch_some"
                                class="w-3.5 h-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span>{{ b.name }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <Link :href="route('owner.management.schedules.work.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Perbarui Jadwal
                </Button>
            </div>
        </form>
    </div>
</template>
