<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    requests: Object,
    categories: Array,
    balances: Array,
    totalBalance: Number,
    filters: Object,
});

const totalUsed = computed(() => {
    return (props.balances || []).reduce((acc, b) => acc + (Number(b.used) || 0), 0);
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    status: props.filters?.status || '',
    leave_category_id: props.filters?.leave_category_id || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.leave_category_id = newFilters?.leave_category_id || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.leaves.index'),
        {
            status: filters.status || undefined,
            leave_category_id: filters.leave_category_id || undefined,
            search: filters.search || undefined,
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
    filters.status = '';
    filters.leave_category_id = '';
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.status || filters.leave_category_id || filters.search);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
];

const categoryFilterOptions = computed(() => [
    { value: '', label: 'Semua Kategori Cuti' },
    ...(props.categories || []).map((c) => ({
        value: String(c.id),
        label: c.name,
    })),
]);

// Category Options for Form
const categoryOptions = computed(() => {
    return (props.categories || []).map((c) => ({
        value: c.id,
        label: `${c.name} (Tersisa: ${c.remaining} hari)`,
    }));
});

// Create Modal State & Form
const isCreateModalOpen = ref(false);

const form = useForm({
    leave_category_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    attachment: null,
});

const calculatedDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0;
    const start = new Date(form.start_date);
    const end = new Date(form.end_date);
    if (end < start) return 0;
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    if (props.categories && props.categories.length > 0) {
        form.leave_category_id = props.categories[0].id;
    }
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
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
            closeCreateModal();
        },
    });
};

// Detail Modal State
const isDetailModalOpen = ref(false);
const selectedRequest = ref(null);

const openDetailModal = (req) => {
    selectedRequest.value = req;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedRequest.value = null;
};

// Table Headers
const tableHeaders = [
    { label: 'Kategori Cuti', class: 'w-48' },
    { label: 'Rentang Tanggal', class: 'w-48' },
    { label: 'Durasi', class: 'w-28 text-center' },
    { label: 'Alasan Permohonan', class: 'min-w-[200px]' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-24 text-right' },
];

const getBadgeVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'pending':
        default:
            return 'warning';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'approved':
            return 'Disetujui';
        case 'rejected':
            return 'Ditolak';
        case 'pending':
        default:
            return 'Menunggu';
    }
};
</script>

<template>
    <Head title="Cuti & Izin Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Cuti & Izin Saya</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Kelola permohonan cuti, izin, dan pantau sisa kuota saldo cuti tahunan Anda.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="primary" @click="openCreateModal" class="flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Ajukan Cuti Baru</span>
                    </Button>
                </div>
            </div>

            <!-- Balances Cards Grid: Symmetrical Split Layout (Featured Card on Left + 2x2 Grid on Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Left: Featured Total Kuota Card (Span 4) -->
                <div class="lg:col-span-4 rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 text-white shadow-xs p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-100/90">
                                Total Kuota Tersedia
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-white/15 text-[11px] font-semibold text-white backdrop-blur-xs">
                                Periode {{ new Date().getFullYear() }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <div class="text-4xl sm:text-5xl font-black tracking-tight text-white">
                                {{ totalBalance }}
                                <span class="text-lg font-medium text-emerald-200 ml-1">Hari</span>
                            </div>
                            <p class="text-xs text-emerald-100/80 mt-1.5 leading-relaxed">
                                Gabungan kuota cuti dan izin yang masih aktif dan dapat diajukan saat ini.
                            </p>
                        </div>
                    </div>

                    <!-- Mini Stat Breakdown at Bottom -->
                    <div class="mt-6 pt-4 border-t border-white/15 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-white/10 rounded-xl p-2.5 backdrop-blur-xs">
                            <span class="text-emerald-200 text-[11px] block">Total Terpakai</span>
                            <span class="font-bold text-white text-sm">{{ totalUsed }} Hari</span>
                        </div>
                        <div class="bg-white/10 rounded-xl p-2.5 backdrop-blur-xs">
                            <span class="text-emerald-200 text-[11px] block">Jumlah Kategori</span>
                            <span class="font-bold text-white text-sm">{{ balances?.length || 0 }} Jenis</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Category Balances in Symmetrical 2x2 Grid (Span 8) -->
                <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                        v-for="b in balances"
                        :key="b.id"
                        class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between hover:border-emerald-200 transition-colors"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-xs font-bold text-slate-700 truncate block">{{ b.category_name }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 shrink-0">
                                    Kuota: {{ b.quota }} Hari
                                </span>
                            </div>
                            <div class="text-2xl font-bold text-slate-900 mt-1">
                                {{ b.remaining }} <span class="text-xs font-normal text-slate-400">Hari Tersisa</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex justify-between text-[11px] text-slate-500 mb-1.5 font-medium">
                                <span>Terpakai: {{ b.used }} hari</span>
                                <span class="text-emerald-600 font-bold">{{ Math.round((b.remaining / (b.quota || 1)) * 100) }}% tersisa</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: Math.min(100, Math.round((b.remaining / (b.quota || 1)) * 100)) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Riwayat Cuti
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
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </Button>
                    </transition>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Pengajuan"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <Select
                                label="Kategori Cuti"
                                v-model="filters.leave_category_id"
                                :options="categoryFilterOptions"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="pt-3 border-t border-slate-100">
                        <Input
                            label="Cari Permohonan"
                            :modelValue="filters.search"
                            @update:modelValue="onSearchInput"
                            placeholder="Ketik alasan atau keperluan cuti yang diajukan..."
                        >
                            <template #prefix>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </template>
                        </Input>
                    </div>
                </div>
            </div>

            <!-- Standar DataTable Component -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="requests?.data && requests.data.length > 0">
                        <tr
                            v-for="req in requests.data"
                            :key="req.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Kategori Cuti -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-semibold text-slate-900 text-sm">{{ req.category_name }}</span>
                            </td>

                            <!-- Rentang Tanggal -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-xs text-slate-800">
                                    {{ req.date_range_formatted }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Diajukan: {{ req.created_at_formatted }}
                                </div>
                            </td>

                            <!-- Durasi -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ req.days_count }} Hari
                                </span>
                            </td>

                            <!-- Alasan Permohonan -->
                            <td class="px-5 py-3.5 max-w-md">
                                <p class="text-xs text-slate-700 line-clamp-1 italic leading-relaxed">&ldquo;{{ req.reason }}&rdquo;</p>
                                <a
                                    v-if="req.attachment_url"
                                    :href="req.attachment_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 hover:underline mt-0.5"
                                    title="Unduh / Lihat Berkas Lampiran"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <span>Lampiran Berkas</span>
                                </a>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <Badge :variant="getBadgeVariant(req.status)" size="sm">
                                    {{ getStatusLabel(req.status) }}
                                </Badge>
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <button
                                    type="button"
                                    @click="openDetailModal(req)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                    title="Lihat Detail Permohonan Cuti"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <TableEmpty
                        v-else
                        :colspan="6"
                        title="Belum Ada Pengajuan Cuti"
                        message="Anda belum pernah mengajukan cuti atau tidak ada data yang cocok dengan filter yang dipilih."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openCreateModal">
                                Ajukan Cuti Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="requests" />
        </div>

        <!-- MODAL 1: Form Pengajuan Cuti -->
        <Modal
            :show="isCreateModalOpen"
            title="Formulir Pengajuan Cuti / Izin"
            maxWidth="md"
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitLeave" class="space-y-4">
                <!-- Kategori Cuti -->
                <div>
                    <label class="form-label form-label-required">Kategori Cuti</label>
                    <Select
                        v-model="form.leave_category_id"
                        :options="categoryOptions"
                        :error="form.errors.leave_category_id"
                        placeholder="Pilih Kategori Cuti"
                    />
                </div>

                <!-- Rentang Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <DatePicker
                            v-model="form.start_date"
                            label="Tanggal Mulai"
                            placeholder="Pilih Tanggal Mulai"
                            required
                            :error="form.errors.start_date"
                        />
                    </div>
                    <div>
                        <DatePicker
                            v-model="form.end_date"
                            label="Tanggal Selesai"
                            placeholder="Pilih Tanggal Selesai"
                            required
                            :error="form.errors.end_date"
                        />
                    </div>
                </div>

                <!-- Kalkulasi Hari -->
                <div v-if="calculatedDays > 0" class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center justify-between text-xs">
                    <span class="text-emerald-800 font-medium">Total Durasi Pengajuan:</span>
                    <span class="font-bold text-emerald-900 text-sm">{{ calculatedDays }} Hari Kalender</span>
                </div>

                <!-- Alasan Cuti -->
                <div>
                    <Textarea
                        v-model="form.reason"
                        label="Alasan / Keperluan Cuti"
                        placeholder="Jelaskan alasan permohonan cuti secara rinci..."
                        rows="3"
                        required
                        :error="form.errors.reason"
                    />
                </div>

                <!-- Attachment Upload -->
                <div>
                    <label class="form-label">
                        Lampiran Pendukung <span class="text-slate-400 font-normal text-xs">(Opsional, misal surat dokter jika sakit)</span>
                    </label>
                    <input
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="handleFileChange"
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                    />
                    <p v-if="form.errors.attachment" class="text-xs text-rose-600 mt-1">
                        {{ form.errors.attachment }}
                    </p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <Button variant="secondary" type="button" @click="closeCreateModal">
                        Batal
                    </Button>
                    <Button variant="primary" type="submit" :loading="form.processing">
                        Kirim Pengajuan
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- MODAL 2: Detail Pengajuan Cuti -->
        <Modal
            :show="isDetailModalOpen"
            maxWidth="md"
            @close="closeDetailModal"
        >
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Detail Permohonan Cuti</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedRequest">
                            Diajukan pada {{ selectedRequest.created_at_formatted }}
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedRequest" class="space-y-4">
                <!-- Header Card -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kategori</span>
                        <h4 class="text-base font-bold text-slate-900">{{ selectedRequest.category_name }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ selectedRequest.date_range_formatted }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-700 block mb-1">{{ selectedRequest.days_count }} Hari</span>
                        <Badge :variant="getBadgeVariant(selectedRequest.status)" size="sm">
                            {{ getStatusLabel(selectedRequest.status) }}
                        </Badge>
                    </div>
                </div>

                <!-- Catatan Penolakan (Jika ditolak) -->
                <div v-if="selectedRequest.status === 'rejected'" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl">
                    <span class="text-xs font-bold text-rose-900 block mb-0.5">Alasan Penolakan dari HRD:</span>
                    <p class="text-xs text-rose-800">{{ selectedRequest.reject_reason || 'Tidak ada catatan spesifik.' }}</p>
                </div>

                <!-- Alasan Karyawan -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <span class="font-bold text-slate-600 block mb-1">Alasan Pengajuan:</span>
                    <p class="text-slate-800 leading-relaxed">{{ selectedRequest.reason }}</p>
                </div>

                <!-- Lampiran jika ada -->
                <div v-if="selectedRequest.attachment_url" class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span class="font-medium text-slate-700">Lampiran Dokumen</span>
                    </div>
                    <a
                        :href="selectedRequest.attachment_url"
                        target="_blank"
                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                    >
                        <span>Lihat Dokumen</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <Button variant="secondary" @click="closeDetailModal">
                        Tutup
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
