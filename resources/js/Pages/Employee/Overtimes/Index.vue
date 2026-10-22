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
import TimePicker from '@/Components/UI/TimePicker.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    overtimes: Object,
    totalHours: Number,
    pendingCount: Number,
    approvedCount: Number,
    filters: Object,
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    status: props.filters?.status || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.start_date = newFilters?.start_date || '';
        filters.end_date = newFilters?.end_date || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.overtimes.index'),
        {
            status: filters.status || undefined,
            start_date: filters.start_date || undefined,
            end_date: filters.end_date || undefined,
            search: filters.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
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
    filters.start_date = '';
    filters.end_date = '';
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.status || filters.start_date || filters.end_date || filters.search);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

// Form Pengajuan Lembur
const isCreateModalOpen = ref(false);

const form = useForm({
    title: '',
    date: '',
    start_time: '',
    end_time: '',
    description: '',
});

const estimatedDuration = computed(() => {
    if (!form.date || !form.start_time || !form.end_time) return null;
    const start = new Date(`${form.date}T${form.start_time}`);
    let end = new Date(`${form.date}T${form.end_time}`);
    if (end < start) {
        end = new Date(end.getTime() + 24 * 60 * 60 * 1000);
    }
    const diffMins = Math.round((end - start) / 60000);
    if (diffMins <= 0) return '0 menit';
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    if (hours > 0 && mins > 0) return `${hours} jam ${mins} menit`;
    if (hours > 0) return `${hours} jam`;
    return `${mins} menit`;
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().split('T')[0];
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const submitOvertime = () => {
    form.post(route('employee.overtimes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Detail Modal
const isDetailModalOpen = ref(false);
const selectedOvertime = ref(null);

const openDetailModal = (item) => {
    selectedOvertime.value = item;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedOvertime.value = null;
};

// Cancel Overtime
const cancelForm = useForm({});
const isCancelling = ref(false);

const cancelOvertime = (id) => {
    if (!confirm('Apakah Anda yakin ingin membatalkan pengajuan lembur ini?')) return;
    isCancelling.value = true;
    cancelForm.post(route('employee.overtimes.cancel', id), {
        preserveScroll: true,
        onFinish: () => {
            isCancelling.value = false;
            if (isDetailModalOpen.value) {
                closeDetailModal();
            }
        },
    });
};

// Format Helpers
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

const getBadgeVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'cancelled':
            return 'neutral';
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
        case 'cancelled':
            return 'Dibatalkan';
        case 'pending':
        default:
            return 'Menunggu';
    }
};

// Table headers
const tableHeaders = [
    { label: 'Tugas / Kegiatan Lembur', class: 'w-72' },
    { label: 'Tanggal', class: 'w-40' },
    { label: 'Jam Kerja', class: 'w-32' },
    { label: 'Durasi', class: 'w-28 text-center' },
    { label: 'Status', class: 'w-32 text-center' },
    { label: 'Aksi', class: 'w-28 text-right' },
];
</script>

<template>
    <Head title="Pengajuan Lembur Kerja" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Lembur Kerja (Overtime)</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Catat aktivitas lembur dan pantau persetujuan jam kerja ekstra Anda.
                    </p>
                </div>
                <div>
                    <Button
                        variant="primary"
                        @click="openCreateModal"
                        class="shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajukan Lembur
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Jam Disetujui</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-0.5">
                            {{ totalHours }} <span class="text-xs font-normal text-slate-400">Jam</span>
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Menunggu Review</p>
                        <p class="text-2xl font-bold text-amber-600 mt-0.5">
                            {{ pendingCount }} <span class="text-xs font-normal text-slate-400">Pengajuan</span>
                        </p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Lembur Disetujui</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">
                            {{ approvedCount }} <span class="text-xs font-normal text-slate-400">Kali</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Riwayat Lembur
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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Pengajuan"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <DatePicker
                                label="Tanggal Mulai"
                                v-model="filters.start_date"
                                placeholder="Pilih Tanggal Mulai"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <DatePicker
                                label="Tanggal Selesai"
                                v-model="filters.end_date"
                                placeholder="Pilih Tanggal Selesai"
                                @change="onFilterChange"
                            />
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="pt-3 border-t border-slate-100">
                        <Input
                            label="Cari Kegiatan"
                            :modelValue="filters.search"
                            @update:modelValue="onSearchInput"
                            placeholder="Ketik judul kegiatan atau uraian tugas lembur..."
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
                    <template v-if="overtimes?.data && overtimes.data.length > 0">
                        <tr
                            v-for="item in overtimes.data"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <p
                                    class="font-bold text-slate-900 text-sm hover:text-emerald-600 transition-colors cursor-pointer"
                                    @click="openDetailModal(item)"
                                >
                                    {{ item.title }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5 line-clamp-1 leading-relaxed">{{ item.description }}</p>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-semibold text-slate-800">
                                {{ formatDate(item.date) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                {{ item.start_time?.substring(0, 5) }} - {{ item.end_time?.substring(0, 5) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ formatDuration(item.duration_minutes) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <Badge :variant="getBadgeVariant(item.status)" size="sm">
                                    {{ getStatusLabel(item.status) }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openDetailModal(item)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        title="Lihat Detail Lembur"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <Button
                                        v-if="item.status === 'pending'"
                                        variant="danger"
                                        size="xs"
                                        :disabled="isCancelling"
                                        @click="cancelOvertime(item.id)"
                                    >
                                        Batal
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="6"
                        title="Belum Ada Riwayat Lembur"
                        message="Anda belum pernah mengajukan lembur atau tidak ada data yang cocok dengan kriteria filter yang dipilih."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openCreateModal">
                                Ajukan Lembur Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="overtimes" />
        </div>

        <!-- Modal Ajukan Lembur -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Pengajuan Lembur</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Catat aktivitas dan durasi pekerjaan lembur Anda</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitOvertime" class="space-y-4">
                <div>
                    <Input
                        label="Judul Kegiatan Lembur"
                        v-model="form.title"
                        placeholder="Contoh: Lembur Maintenance Server / Audit Akhir Bulan"
                        :error="form.errors.title"
                        required
                    />
                </div>

                <div>
                    <DatePicker
                        label="Tanggal Lembur"
                        v-model="form.date"
                        placeholder="Pilih Tanggal Lembur"
                        :error="form.errors.date"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <TimePicker
                            label="Jam Mulai"
                            v-model="form.start_time"
                            placeholder="Pilih Jam Mulai"
                            default-time="18:00"
                            :error="form.errors.start_time"
                            required
                        />
                    </div>
                    <div>
                        <TimePicker
                            label="Jam Selesai"
                            v-model="form.end_time"
                            placeholder="Pilih Jam Selesai"
                            default-time="21:00"
                            :error="form.errors.end_time"
                            required
                        />
                    </div>
                </div>

                <div v-if="estimatedDuration" class="p-3 bg-emerald-50/70 border border-emerald-100 rounded-xl flex items-center gap-2 text-xs text-emerald-800">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Estimasi Durasi Lembur: <strong class="font-semibold">{{ estimatedDuration }}</strong></span>
                </div>

                <div>
                    <Textarea
                        label="Keterangan Pekerjaan / Tugas"
                        v-model="form.description"
                        rows="3"
                        placeholder="Uraikan detail pekerjaan yang dikerjakan saat lembur..."
                        :error="form.errors.description"
                        required
                    />
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeCreateModal"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Kirim Pengajuan
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Modal Detail Lembur -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian Pengajuan Lembur</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Informasi lengkap status lembur kerja</p>
                    </div>
                </div>
            </template>

            <div v-if="selectedOvertime" class="space-y-4 text-sm">
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 space-y-2.5">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-xs text-slate-500">Judul Kegiatan:</span>
                        <span class="font-semibold text-slate-900 text-right">{{ selectedOvertime.title }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Tanggal Lembur:</span>
                        <span class="font-medium text-slate-800">{{ formatDate(selectedOvertime.date) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Waktu Kerja:</span>
                        <span class="font-mono text-xs text-slate-700">
                            {{ selectedOvertime.start_time?.substring(0, 5) }} - {{ selectedOvertime.end_time?.substring(0, 5) }}
                            ({{ formatDuration(selectedOvertime.duration_minutes) }})
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Status Permohonan:</span>
                        <Badge :variant="getBadgeVariant(selectedOvertime.status)">
                            {{ getStatusLabel(selectedOvertime.status) }}
                        </Badge>
                    </div>
                    <div v-if="selectedOvertime.approved_by" class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Ditinjau Oleh:</span>
                        <span class="text-xs font-medium text-slate-700">{{ selectedOvertime.approved_by?.name || 'Manajemen' }}</span>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Keterangan Pekerjaan:</h4>
                    <p class="text-xs text-slate-600 bg-slate-50/50 p-3 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line">
                        {{ selectedOvertime.description || '-' }}
                    </p>
                </div>

                <!-- Alasan Penolakan -->
                <div v-if="selectedOvertime.status === 'rejected'" class="p-3 bg-rose-50 border border-rose-200 rounded-xl">
                    <p class="text-xs font-bold text-rose-800 mb-0.5">Alasan Penolakan:</p>
                    <p class="text-xs text-rose-700 leading-relaxed">
                        {{ selectedOvertime.reject_reason || 'Tidak ada alasan khusus yang dicantumkan.' }}
                    </p>
                </div>

                <div class="pt-3 flex items-center justify-between border-t border-slate-100">
                    <Button
                        v-if="selectedOvertime.status === 'pending'"
                        variant="danger"
                        size="sm"
                        :disabled="isCancelling"
                        @click="cancelOvertime(selectedOvertime.id)"
                    >
                        Batalkan Pengajuan
                    </Button>
                    <div v-else></div>

                    <Button
                        variant="secondary"
                        size="sm"
                        @click="closeDetailModal"
                    >
                        Tutup
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
