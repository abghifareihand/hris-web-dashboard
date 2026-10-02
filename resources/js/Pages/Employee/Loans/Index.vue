<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    loans: Object,
    totalActiveLoan: Number,
    filters: Object,
});

const isModalOpen = ref(false);
const isDetailModalOpen = ref(false);
const selectedLoan = ref(null);
const statusFilter = ref(props.filters.status || '');

const form = useForm({
    date: '',
    amount: '',
    tenor: 1,
    description: '',
});

const openModal = () => {
    form.reset();
    form.tenor = 1;
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const openDetailModal = (loan) => {
    selectedLoan.value = loan;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedLoan.value = null;
};

const submitLoan = () => {
    form.post(route('employee.loans.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const applyFilter = () => {
    router.get(
        route('employee.loans.index'),
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
        case 'completed':
            return { label: 'Lunas', class: 'bg-sky-50 text-sky-700 ring-sky-600/20' };
        default:
            return { label: 'Menunggu Persetujuan', class: 'bg-amber-50 text-amber-700 ring-amber-600/20' };
    }
};
</script>

<template>
    <Head title="Pinjaman & Kasbon Karyawan" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pinjaman Karyawan (Kasbon)</h1>
                    <p class="text-sm text-slate-500 mt-1">Fasilitas pembiayaan internal perusahaan dan pemotongan angsuran gaji bulanan.</p>
                </div>
                <button
                    type="button"
                    @click="openModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Kasbon Baru
                </button>
            </div>

            <!-- Stats Card -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 max-w-sm">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Kasbon Disetujui</p>
                    <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ formatRupiah(totalActiveLoan) }}</p>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Pengajuan Pinjaman</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar permohonan pinjaman kasbon dan skema tenor angsuran</p>
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilter"
                            class="text-xs rounded-xl border-slate-200 text-slate-700 py-1.5 focus:border-sky-500 focus:ring-sky-500/20"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Persetujuan</option>
                            <option value="approved">Disetujui</option>
                            <option value="completed">Lunas</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Nominal Pinjaman</th>
                                <th class="px-5 py-3.5">Tenor</th>
                                <th class="px-5 py-3.5">Angsuran / Bulan</th>
                                <th class="px-5 py-3.5">Tujuan / Keperluan</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-center">Rincian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!loans.data || loans.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada pengajuan pinjaman</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Anda dapat mengajukan pinjaman darurat / kasbon melalui tombol di atas.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in loans.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700 font-medium">
                                    {{ formatDate(item.date) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900">
                                    {{ formatRupiah(item.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-700">
                                    {{ item.tenor }} Bulan
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-700">
                                    {{ formatRupiah(item.amount / item.tenor) }}
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
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <button
                                        v-if="item.installments && item.installments.length > 0"
                                        type="button"
                                        @click="openDetailModal(item)"
                                        class="inline-flex items-center gap-1 text-xs text-sky-600 hover:text-sky-700 font-semibold"
                                    >
                                        Lihat Angsuran ({{ item.installments.length }})
                                    </button>
                                    <span v-else class="text-xs text-slate-400 italic">Belum dibuat</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="loans.links && loans.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="loans.links" />
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Pinjaman / Kasbon -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Pinjaman / Kasbon</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ajukan bantuan dana kasbon dengan skema angsuran</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitLoan" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Pengajuan <span class="text-rose-500">*</span>
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
                            Nominal (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            min="50000"
                            step="10000"
                            v-model="form.amount"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            placeholder="Contoh: 1000000"
                            required
                        />
                        <p v-if="form.errors.amount" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.amount }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tenor Cicilan <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.tenor"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        >
                            <option :value="1">1 Bulan</option>
                            <option :value="2">2 Bulan</option>
                            <option :value="3">3 Bulan</option>
                            <option :value="6">6 Bulan</option>
                            <option :value="12">12 Bulan</option>
                        </select>
                        <p v-if="form.errors.tenor" class="text-xs text-rose-500 mt-1">
                            {{ form.errors.tenor }}
                        </p>
                    </div>
                </div>

                <div v-if="form.amount && form.tenor" class="p-3 bg-sky-50/70 border border-sky-100 rounded-xl">
                    <p class="text-xs text-sky-800">
                        Perkiraan Potongan Gaji: <span class="font-bold">{{ formatRupiah(form.amount / form.tenor) }}</span> / bulan selama {{ form.tenor }} bulan.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alasan / Keperluan Pinjaman <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Contoh: Biaya berobat keluarga, keperluan mendesak..."
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
                        <span v-else>Ajukan Pinjaman</span>
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Modal Rincian Angsuran / Installments -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="lg">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Jadwal Angsuran Pinjaman</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedLoan">
                            Total Pinjaman: {{ formatRupiah(selectedLoan.amount) }} ({{ selectedLoan.tenor }} Bulan)
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedLoan && selectedLoan.installments" class="space-y-4">
                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 uppercase font-semibold text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Cicilan Ke</th>
                                <th class="px-4 py-2.5">Jatuh Tempo</th>
                                <th class="px-4 py-2.5">Nominal</th>
                                <th class="px-4 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ins in selectedLoan.installments" :key="ins.id">
                                <td class="px-4 py-3 font-medium text-slate-900">#{{ ins.installment_number }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ formatDate(ins.due_date) }}</td>
                                <td class="px-4 py-3 font-bold text-slate-900">{{ formatRupiah(ins.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            ins.is_paid ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700',
                                            'px-2 py-0.5 rounded text-[11px] font-semibold'
                                        ]"
                                    >
                                        {{ ins.is_paid ? 'Sudah Terpotong' : 'Belum Terpotong' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </EmployeeLayout>
</template>
