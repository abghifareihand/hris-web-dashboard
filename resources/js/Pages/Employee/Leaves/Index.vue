<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    requests: Object,
    categories: Array,
    balances: Array,
    totalBalance: Number,
    filters: Object,
});

const isModalOpen = ref(false);
const statusFilter = ref(props.filters.status || '');

const form = useForm({
    leave_category_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    attachment: null,
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.attachment = file;
    }
};

const submitLeave = () => {
    form.post(route('employee.leaves.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const applyFilter = () => {
    router.get(
        route('employee.leaves.index'),
        { status: statusFilter.value },
        { preserveState: true, replace: true }
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return { label: 'Disetujui', class: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
        case 'rejected':
            return { label: 'Ditolak', class: 'bg-rose-50 text-rose-700 ring-rose-600/20' };
        default:
            return { label: 'Menunggu HRD', class: 'bg-amber-50 text-amber-700 ring-amber-600/20' };
    }
};
</script>

<template>
    <Head title="Manajemen Cuti & Izin" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Cuti & Izin Kerja</h1>
                    <p class="text-sm text-slate-500 mt-1">Pantau kuota cuti tahunan dan ajukan permohonan istirahat/izin kerja.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="openModal"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajukan Cuti Baru
                    </button>
                </div>
            </div>

            <!-- Leave Sub-Navigation Tabs -->
            <div class="flex items-center border-b border-slate-200">
                <nav class="flex gap-6 -mb-px">
                    <Link
                        :href="route('employee.leaves.index')"
                        class="pb-3 text-sm font-semibold border-b-2 border-sky-600 text-sky-600 transition-colors"
                    >
                        Riwayat & Pengajuan Cuti
                    </Link>
                    <Link
                        :href="route('employee.leaves.balances')"
                        class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors"
                    >
                        Sisa Kuota / Saldo Cuti
                    </Link>
                </nav>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Sisa Cuti</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalBalance }} <span class="text-xs font-normal text-slate-400">Hari</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pengajuan Menunggu</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ requests.data ? requests.data.filter(r => r.status === 'pending').length : 0 }}
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Cuti Disetujui</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ requests.data ? requests.data.filter(r => r.status === 'approved').length : 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Daftar Pengajuan Cuti Anda</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Riwayat lengkap pengajuan cuti, sakit, dan izin kerja</p>
                    </div>
                    <div class="flex items-center gap-2">
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
                                <th class="px-5 py-3.5">Kategori Cuti</th>
                                <th class="px-5 py-3.5">Rentang Tanggal</th>
                                <th class="px-5 py-3.5">Durasi</th>
                                <th class="px-5 py-3.5">Alasan</th>
                                <th class="px-5 py-3.5">Lampiran</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Diajukan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!requests.data || requests.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada permohonan cuti</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Gunakan tombol "Ajukan Cuti Baru" untuk mengajukan izin atau cuti tahunan.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in requests.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ item.leave_category?.name || 'Cuti Umum' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ formatDate(item.start_date) }} - {{ formatDate(item.end_date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ item.days_count }} Hari
                                    </span>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="line-clamp-2 text-xs leading-relaxed text-slate-600">{{ item.reason }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs">
                                    <a
                                        v-if="item.attachment"
                                        :href="'/storage/' + item.attachment"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-sky-600 hover:text-sky-700 font-medium"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        Lihat Dokumen
                                    </a>
                                    <span v-else class="text-slate-400 italic">Tanpa Lampiran</span>
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
                <div v-if="requests.links && requests.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="requests.links" />
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Cuti Baru -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Pengajuan Cuti / Izin</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Isi rincian permohonan tanggal libur atau istirahat</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitLeave" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kategori Cuti <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.leave_category_id"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        required
                    >
                        <option value="" disabled>-- Pilih Kategori Cuti --</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }} (Maks {{ cat.days_count }} hari)
                        </option>
                    </select>
                    <p v-if="form.errors.leave_category_id" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.leave_category_id }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tanggal Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="form.errors.start_date" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.start_date }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tanggal Berakhir <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="form.end_date"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="form.errors.end_date" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.end_date }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alasan / Keterangan Cuti <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.reason"
                        rows="3"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Contoh: Mengurus keperluan keluarga, kondisi kesehatan..."
                        required
                    ></textarea>
                    <p v-if="form.errors.reason" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.reason }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Dokumen / Surat Keterangan (Opsional)
                    </label>
                    <input
                        type="file"
                        @change="handleFileChange"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 transition-all cursor-pointer"
                    />
                    <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG (Maksimal 5MB). Dianjurkan jika cuti sakit.</p>
                    <p v-if="form.errors.attachment" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.attachment }}
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
