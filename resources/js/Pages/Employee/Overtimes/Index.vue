<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    overtimes: Object,
    totalHours: Number,
    pendingCount: Number,
    filters: Object,
});

const isModalOpen = ref(false);
const statusFilter = ref(props.filters.status || '');

const form = useForm({
    title: '',
    date: '',
    start_time: '',
    end_time: '',
    description: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitOvertime = () => {
    form.post(route('employee.overtimes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const applyFilter = () => {
    router.get(
        route('employee.overtimes.index'),
        { status: statusFilter.value },
        { preserveState: true, replace: true }
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatDuration = (minutes) => {
    if (!minutes) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0 && mins > 0) return `${hours} jam ${mins} mnt`;
    if (hours > 0) return `${hours} jam`;
    return `${mins} menit`;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return { label: 'Disetujui', class: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
        case 'rejected':
            return { label: 'Ditolak', class: 'bg-rose-50 text-rose-700 ring-rose-600/20' };
        default:
            return { label: 'Menunggu Review', class: 'bg-amber-50 text-amber-700 ring-amber-600/20' };
    }
};
</script>

<template>
    <Head title="Pengajuan Lembur" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Lembur Kerja (Overtime)</h1>
                    <p class="text-sm text-slate-500 mt-1">Catat aktivitas lembur dan pantau persetujuan jam kerja ekstra Anda.</p>
                </div>
                <button
                    type="button"
                    @click="openModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Lembur
                </button>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Jam Lembur Disetujui</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalHours }} <span class="text-xs font-normal text-slate-400">Jam</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pengajuan Menunggu Konfirmasi</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ pendingCount }} <span class="text-xs font-normal text-slate-400">Pengajuan</span></p>
                    </div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Pengajuan Lembur</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar permohonan lembur yang telah Anda kirim</p>
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilter"
                            class="text-xs rounded-xl border-slate-200 text-slate-700 py-1.5 focus:border-sky-500 focus:ring-sky-500/20"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Review</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Judul Lembur</th>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Jam</th>
                                <th class="px-5 py-3.5">Durasi</th>
                                <th class="px-5 py-3.5">Keterangan</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Diajukan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!overtimes.data || overtimes.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada riwayat lembur</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Klik tombol "Ajukan Lembur" di atas jika Anda memiliki tugas di luar jam kerja reguler.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in overtimes.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-900">
                                    {{ item.title }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ formatDate(item.date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-xs text-slate-600">
                                    {{ item.start_time?.substring(0, 5) }} - {{ item.end_time?.substring(0, 5) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-sky-50 text-sky-700">
                                        {{ formatDuration(item.duration_minutes) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="line-clamp-2 text-xs leading-relaxed text-slate-600">{{ item.description }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            getStatusBadge(item.status).class,
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset'
                                        ]"
                                    >
                                        {{ getStatusBadge(item.status).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(item.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="overtimes.links && overtimes.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="overtimes.links" />
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Lembur -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Pengajuan Lembur</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Catat aktivitas dan waktu pekerjaan lembur</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitOvertime" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Judul Kegiatan Lembur <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.title"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Contoh: Lembur Maintenance Server / Audit Akhir Bulan"
                        required
                    />
                    <p v-if="form.errors.title" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.title }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Lembur <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        v-model="form.date"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        required
                    />
                    <p v-if="form.errors.date" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.date }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Jam Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="time"
                            v-model="form.start_time"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="form.errors.start_time" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.start_time }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Jam Selesai <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="time"
                            v-model="form.end_time"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="form.errors.end_time" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.end_time }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Keterangan Tugas / Pekerjaan <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Uraikan detail pekerjaan yang dikerjakan saat lembur..."
                        required
                    ></textarea>
                    <p v-if="form.errors.description" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50"
                    >
                        <span v-if="form.processing">Mengirim...</span>
                        <span v-else>Kirim Pengajuan</span>
                    </button>
                </div>
            </form>
        </Modal>
    </EmployeeLayout>
</template>
