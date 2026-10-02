<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employee: Object,
    attendances: Array,
    todayAttendance: Object,
    todaySchedule: Object,
    month: Number,
    year: Number,
    months: Object,
    years: Array,
});

const filterForm = ref({
    month: props.month,
    year: props.year,
});

const applyFilter = () => {
    router.get(route('employee.attendances.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clock-In Form
const clockInForm = useForm({
    latitude: '',
    longitude: '',
    notes: '',
    photo: null,
});

// Clock-Out Form
const clockOutForm = useForm({
    latitude: '',
    longitude: '',
    notes: '',
    photo: null,
});

const getLocation = (formObj) => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                formObj.latitude = pos.coords.latitude;
                formObj.longitude = pos.coords.longitude;
            },
            (err) => {
                console.warn('Geolocation unavailable:', err.message);
            }
        );
    }
};

const handleClockIn = () => {
    getLocation(clockInForm);
    clockInForm.post(route('employee.attendances.clock-in'), {
        preserveScroll: true,
    });
};

const handleClockOut = () => {
    getLocation(clockOutForm);
    clockOutForm.post(route('employee.attendances.clock-out'), {
        preserveScroll: true,
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'present':
        case 'hadir':
            return { label: 'Hadir', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
        case 'late':
        case 'terlambat':
            return { label: 'Terlambat', class: 'bg-amber-100 text-amber-800 border-amber-200' };
        case 'leave':
        case 'cuti':
            return { label: 'Cuti / Izin', class: 'bg-blue-100 text-blue-800 border-blue-200' };
        case 'sick':
        case 'sakit':
            return { label: 'Sakit', class: 'bg-purple-100 text-purple-800 border-purple-200' };
        case 'alpha':
        case 'absent':
            return { label: 'Alpha', class: 'bg-rose-100 text-rose-800 border-rose-200' };
        default:
            return { label: status || '-', class: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Presensi & Kehadiran" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Presensi & Kehadiran</h1>
                    <p class="text-slate-500 text-sm mt-1">Lakukan absen masuk / pulang harian dan pantau riwayat kehadiran Anda.</p>
                </div>

                <!-- Month Year Selector -->
                <form @submit.prevent="applyFilter" class="flex items-center gap-2">
                    <select v-model="filterForm.month" class="text-xs rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option v-for="(name, num) in months" :key="num" :value="Number(num)">{{ name }}</option>
                    </select>
                    <select v-model="filterForm.year" class="text-xs rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold">
                        Lihat
                    </button>
                </form>
            </div>

            <!-- Clock In / Out Action Banner -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    <div class="md:col-span-6 space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-semibold border border-sky-100">
                            <span>Jadwal Hari Ini: {{ todaySchedule?.shift?.name || 'Shift Reguler' }}</span>
                            <span>•</span>
                            <span>{{ todaySchedule?.shift?.clock_in ? todaySchedule.shift.clock_in.slice(0, 5) : '08:00' }} - {{ todaySchedule?.shift?.clock_out ? todaySchedule.shift.clock_out.slice(0, 5) : '17:00' }} WIB</span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">
                            {{ todayAttendance?.clock_in_time ? (todayAttendance?.clock_out_time ? 'Presensi Hari Ini Selesai' : 'Anda Sedang Bekerja') : 'Silakan Melakukan Absen Masuk' }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            Lokasi kantor: {{ employee?.branch?.name || '-' }}. Pastikan izin lokasi (GPS) dan kamera browser aktif.
                        </p>
                    </div>

                    <div class="md:col-span-6 flex flex-col sm:flex-row items-center justify-end gap-4">
                        <!-- Clock In Box -->
                        <div v-if="!todayAttendance?.clock_in_time" class="w-full sm:w-auto">
                            <button
                                type="button"
                                @click="handleClockIn"
                                :disabled="clockInForm.processing"
                                class="w-full sm:w-auto px-6 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-xs transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <span>Absen Masuk (Clock In)</span>
                            </button>
                        </div>

                        <!-- Clock Out Box -->
                        <div v-else-if="!todayAttendance?.clock_out_time" class="w-full sm:w-auto flex items-center gap-3">
                            <div class="text-right hidden sm:block">
                                <span class="text-[11px] text-slate-400 block">Masuk pukul</span>
                                <span class="text-sm font-bold text-emerald-600 font-mono">{{ todayAttendance.clock_in_time.slice(0, 5) }} WIB</span>
                            </div>
                            <button
                                type="button"
                                @click="handleClockOut"
                                :disabled="clockOutForm.processing"
                                class="w-full sm:w-auto px-6 py-3.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold shadow-xs transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Absen Pulang (Clock Out)</span>
                            </button>
                        </div>

                        <!-- Finished Today -->
                        <div v-else class="flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 px-4 py-3 rounded-xl border border-emerald-200">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Absen masuk ({{ todayAttendance.clock_in_time.slice(0, 5) }}) & pulang ({{ todayAttendance.clock_out_time.slice(0, 5) }}) lengkap.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance History Table -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Riwayat Presensi Bulan {{ months[month] }} {{ year }}</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Shift</th>
                                <th class="px-5 py-3.5 text-center">Masuk</th>
                                <th class="px-5 py-3.5 text-center">Pulang</th>
                                <th class="px-5 py-3.5 text-center">Jam Kerja</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="att in attendances" :key="att.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ formatDate(att.date) }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-700">
                                    {{ att.shift?.name || 'Shift Reguler' }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold text-slate-800">
                                    {{ att.clock_in_time ? att.clock_in_time.slice(0, 5) : '-' }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold text-slate-800">
                                    {{ att.clock_out_time ? att.clock_out_time.slice(0, 5) : '-' }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-700">
                                    {{ att.total_work_minutes ? Math.floor(att.total_work_minutes / 60) + 'j ' + (att.total_work_minutes % 60) + 'm' : '-' }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold border', getStatusBadge(att.attendance_status).class]">
                                        {{ getStatusBadge(att.attendance_status).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 max-w-xs truncate">
                                    {{ att.clock_in_notes || att.clock_out_notes || '-' }}
                                </td>
                            </tr>
                            <tr v-if="!attendances || attendances.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Tidak ada catatan presensi pada bulan ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
