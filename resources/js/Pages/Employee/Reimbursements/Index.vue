<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    reimbursements: Object,
    totalApproved: Number,
    totalPending: Number,
    filters: Object,
});

const isModalOpen = ref(false);
const statusFilter = ref(props.filters.status || '');

const form = useForm({
    date: '',
    amount: '',
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

const submitReimbursement = () => {
    form.post(route('employee.reimbursements.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const applyFilter = () => {
    router.get(
        route('employee.reimbursements.index'),
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

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return { label: 'Disetujui', class: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
        case 'rejected':
            return { label: 'Ditolak', class: 'bg-rose-50 text-rose-700 ring-rose-600/20' };
        default:
            return { label: 'Menunggu Verifikasi', class: 'bg-amber-50 text-amber-700 ring-amber-600/20' };
    }
};
</script>

<template>
    <Head title="Klaim Reimbursement" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Klaim Reimbursement</h1>
                    <p class="text-sm text-slate-500 mt-1">Ajukan penggantian biaya operasional, medis, atau perjalanan dinas kantor.</p>
                </div>
                <button
                    type="button"
                    @click="openModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Klaim Baru
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Klaim Disetujui</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-0.5">{{ formatRupiah(totalApproved) }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Klaim Sedang Diproses</p>
                        <p class="text-2xl font-bold text-amber-600 mt-0.5">{{ formatRupiah(totalPending) }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Pengajuan Reimbursement</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar biaya yang telah Anda klaim beserta status pencairan</p>
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilter"
                            class="text-xs rounded-xl border-slate-200 text-slate-700 py-1.5 focus:border-sky-500 focus:ring-sky-500/20"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Verifikasi</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal Nota/Kuitansi</th>
                                <th class="px-5 py-3.5">Keterangan Biaya</th>
                                <th class="px-5 py-3.5">Nominal Klaim</th>
                                <th class="px-5 py-3.5">Bukti Struk</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5">Catatan Finance</th>
                                <th class="px-5 py-3.5 text-right">Diajukan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!reimbursements.data || reimbursements.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada klaim reimbursement</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Gunakan tombol "Ajukan Klaim Baru" untuk mengajukan penggantian nota pengeluaran operasional.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in reimbursements.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-medium">
                                    {{ formatDate(item.date) }}
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="line-clamp-2 text-xs leading-relaxed text-slate-900 font-medium">{{ item.reason }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900">
                                    {{ formatRupiah(item.amount) }}
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
                                        Lihat Kuitansi
                                    </a>
                                    <span v-else class="text-slate-400 italic">Tanpa Bukti</span>
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
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="text-xs text-slate-500 italic">{{ item.note || '-' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(item.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reimbursements.links && reimbursements.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="reimbursements.links" />
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Klaim Reimbursement -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Klaim Reimbursement</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Unggah bukti nota dan rincian klaim pengeluaran</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitReimbursement" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Pengeluaran <span class="text-rose-500">*</span>
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

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nominal Biaya (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="number"
                        min="1000"
                        step="100"
                        v-model="form.amount"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        placeholder="Contoh: 150000"
                        required
                    />
                    <p v-if="form.errors.amount" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.amount }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Keterangan Pengeluaran <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.reason"
                        rows="3"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Contoh: Pembelian tinta printer kantor, biaya bensin tugas dinas..."
                        required
                    ></textarea>
                    <p v-if="form.errors.reason" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.reason }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Foto Nota / File Kuitansi
                    </label>
                    <input
                        type="file"
                        @change="handleFileChange"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 transition-all cursor-pointer"
                    />
                    <p class="text-[11px] text-slate-400 mt-1">Format: PDF, JPG, PNG (Maksimal 5MB).</p>
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
                        <span v-else>Kirim Klaim</span>
                    </button>
                </div>
            </form>
        </Modal>
    </EmployeeLayout>
</template>
