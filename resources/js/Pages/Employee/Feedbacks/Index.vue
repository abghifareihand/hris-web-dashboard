<script setup>
import { ref, reactive, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Select from '@/Components/UI/Select.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    feedbacks: Object,
    totalCount: Number,
    anonymousCount: Number,
    publicCount: Number,
    filters: Object,
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    search: props.filters?.search || '',
    is_anonymous: props.filters?.is_anonymous ?? '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.search = newFilters?.search || '';
        filters.is_anonymous = newFilters?.is_anonymous ?? '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.feedbacks.index'),
        {
            search: filters.search || undefined,
            is_anonymous: filters.is_anonymous !== '' ? filters.is_anonymous : undefined,
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
    filters.search = '';
    filters.is_anonymous = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.search || filters.is_anonymous !== '');
};

const anonymousOptions = [
    { value: '', label: 'Semua Status Identitas' },
    { value: '1', label: 'Terkirim Anonim (Rahasia)' },
    { value: '0', label: 'Terkirim Terbuka' },
];

// Target Pre-defined Options
const targetOptions = [
    { value: 'Manajemen & Pimpinan', label: 'Manajemen & Pimpinan Perusahaan' },
    { value: 'Divisi HRD & Operasional', label: 'Divisi HRD & Operasional' },
    { value: 'Fasilitas & Perlengkapan Kerja', label: 'Fasilitas & Perlengkapan Kantor' },
    { value: 'Atasan Langsung / Supervisor', label: 'Atasan Langsung / Supervisor' },
    { value: 'Budaya & Kebijakan Perusahaan', label: 'Budaya & Kebijakan Perusahaan' },
    { value: 'Lainnya', label: 'Lainnya (Tuliskan sendiri)' },
];

// Create Modal & Form
const isCreateModalOpen = ref(false);
const selectedTargetPreset = ref('Manajemen & Pimpinan');
const customTarget = ref('');

const form = useForm({
    target: 'Manajemen & Pimpinan',
    criticism: '',
    suggestion: '',
    is_anonymous: true,
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    selectedTargetPreset.value = 'Manajemen & Pimpinan';
    form.target = 'Manajemen & Pimpinan';
    form.is_anonymous = true;
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
};

const onTargetPresetChange = (val) => {
    selectedTargetPreset.value = val;
    if (val !== 'Lainnya') {
        form.target = val;
    } else {
        form.target = customTarget.value || '';
    }
};

const onCustomTargetInput = (val) => {
    customTarget.value = val;
    form.target = val;
};

const submitFeedback = () => {
    if (selectedTargetPreset.value === 'Lainnya' && customTarget.value) {
        form.target = customTarget.value;
    }
    form.post(route('employee.feedbacks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Detail Modal
const isDetailModalOpen = ref(false);
const selectedFeedback = ref(null);

const openDetailModal = (item) => {
    selectedFeedback.value = item;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedFeedback.value = null;
};

// Delete Confirmation Modal
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleteForm = useForm({});
const isDeleting = ref(false);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    itemToDelete.value = null;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    isDeleting.value = true;
    deleteForm.delete(route('employee.feedbacks.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            closeDeleteModal();
            if (isDetailModalOpen.value) {
                closeDetailModal();
            }
        },
    });
};

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const tableHeaders = [
    { label: 'Ditujukan Kepada', class: 'w-48 font-semibold' },
    { label: 'Poin Kritik / Kendala', class: 'w-72' },
    { label: 'Usulan Solusi / Saran', class: 'w-72' },
    { label: 'Identitas', class: 'w-36 text-center' },
    { label: 'Tanggal', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-24 text-right' },
];
</script>

<template>
    <Head title="Kritik & Saran Karyawan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kritik & Saran Karyawan</h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Saluran penyampaian aspirasi terbuka, evaluasi kerja, dan saran perbaikan fasilitas bagi kemajuan bersama.
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
                        Sampaikan Masukan Baru
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Masukan Terkirim</p>
                        <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ totalCount }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kirim Anonim (Rahasia)</p>
                        <p class="text-2xl font-bold text-purple-600 mt-0.5">{{ anonymousCount }}</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kirim Terbuka</p>
                        <p class="text-2xl font-bold text-blue-600 mt-0.5">{{ publicCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Kritik & Saran
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
                        <!-- Status Identitas -->
                        <div>
                            <Select
                                label="Status Identitas"
                                v-model="filters.is_anonymous"
                                :options="anonymousOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Search Input -->
                        <div>
                            <Input
                                label="Cari Aspirasi / Kata Kunci"
                                :modelValue="filters.search"
                                @update:modelValue="onSearchInput"
                                placeholder="Ketik topik, poin masalah, atau saran..."
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
            </div>

            <!-- Standar DataTable Component -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="feedbacks?.data && feedbacks.data.length > 0">
                        <tr
                            v-for="item in feedbacks.data"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-slate-900">
                                {{ item.target }}
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <p class="text-xs text-slate-800 line-clamp-2 leading-relaxed">&ldquo;{{ item.criticism }}&rdquo;</p>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">{{ item.suggestion }}</p>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <span
                                    v-if="item.is_anonymous"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700"
                                >
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Anonim
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700"
                                >
                                    Terbuka
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center text-xs text-slate-500">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openDetailModal(item)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        title="Lihat Detail Aspirasi"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <Button
                                        variant="ghost"
                                        size="xs"
                                        :disabled="isDeleting"
                                        @click="confirmDelete(item)"
                                        class="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="6"
                        title="Belum Ada Riwayat Masukan"
                        message="Anda belum pernah menyampaikan masukan atau tidak ada data yang cocok dengan kriteria filter yang dipilih."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openCreateModal">
                                Sampaikan Masukan Baru Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="feedbacks" />
        </div>

        <!-- Modal Sampaikan Masukan Baru -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="lg">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Form Kritik & Saran Karyawan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Sampaikan keluhan dan ide perbaikan dengan privasi terjamin</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitFeedback" class="space-y-4">
                <div>
                    <Select
                        label="Ditujukan Kepada"
                        :modelValue="selectedTargetPreset"
                        :options="targetOptions"
                        @update:modelValue="onTargetPresetChange"
                        required
                    />
                </div>

                <div v-if="selectedTargetPreset === 'Lainnya'">
                    <Input
                        label="Tuliskan Pihak yang Dituju"
                        v-model="customTarget"
                        placeholder="Contoh: Tim IT / Security / Vendor Kantin"
                        @update:modelValue="onCustomTargetInput"
                        :error="form.errors.target"
                        required
                    />
                </div>

                <div>
                    <Textarea
                        label="Poin Kritik / Masalah / Kendala"
                        v-model="form.criticism"
                        :rows="3"
                        placeholder="Uraikan kendala atau aspek yang menurut Anda perlu dibenahi..."
                        :error="form.errors.criticism"
                        required
                    />
                </div>

                <div>
                    <Textarea
                        label="Usulan Saran & Solusi Perbaikan"
                        v-model="form.suggestion"
                        :rows="3"
                        placeholder="Berikan gagasan atau ide solusi konkret yang dapat diterapkan perusahaan..."
                        :error="form.errors.suggestion"
                        required
                    />
                </div>

                <!-- Anonim Checkbox Box -->
                <div class="p-3.5 bg-purple-50/70 border border-purple-100 rounded-xl flex items-start gap-3">
                    <input
                        id="is_anonymous"
                        type="checkbox"
                        v-model="form.is_anonymous"
                        class="mt-0.5 w-4 h-4 text-purple-600 rounded-md border-purple-300 focus:ring-purple-500/20"
                    />
                    <label for="is_anonymous" class="cursor-pointer">
                        <span class="block text-xs font-bold text-purple-900">
                            Kirim secara Anonim (Kerahasiaan Terjamin)
                        </span>
                        <span class="block text-[11px] text-purple-700/90 mt-0.5 leading-relaxed">
                            Nama dan profil Anda tidak akan ditampilkan pada dashboard pimpinan. Identitas Anda dijaga secara penuh.
                        </span>
                    </label>
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
                        Kirim Masukan
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Modal Detail Masukan -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian Masukan Anda</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedFeedback">
                            Dikirim pada {{ formatDate(selectedFeedback.created_at) }}
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedFeedback" class="space-y-4 text-sm">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Ditujukan Kepada:</span>
                        <span class="font-bold text-slate-900">{{ selectedFeedback.target }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-500">Mode Privasi:</span>
                        <span
                            v-if="selectedFeedback.is_anonymous"
                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700"
                        >
                            Anonim (Identitas Dirahasiakan)
                        </span>
                        <span
                            v-else
                            class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-800"
                        >
                            Terbuka
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Poin Kritik / Kendala:</h4>
                    <p class="text-xs text-slate-700 bg-slate-50/50 p-3 rounded-xl border border-slate-100 leading-relaxed whitespace-pre-line">
                        {{ selectedFeedback.criticism }}
                    </p>
                </div>

                <div>
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Usulan Solusi & Rekomendasi:</h4>
                    <p class="text-xs text-slate-700 bg-emerald-50/40 p-3 rounded-xl border border-emerald-100/60 leading-relaxed whitespace-pre-line">
                        {{ selectedFeedback.suggestion }}
                    </p>
                </div>

                <div class="pt-3 flex items-center justify-between border-t border-slate-100">
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="isDeleting"
                        @click="confirmDelete(selectedFeedback)"
                        class="text-rose-600 hover:bg-rose-50"
                    >
                        Hapus Masukan
                    </Button>

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

        <!-- Modal Konfirmasi Hapus -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="sm">
            <template #title>
                <div class="flex items-center gap-2.5 text-rose-600">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Hapus Masukan?</h3>
                    </div>
                </div>
            </template>

            <div class="space-y-4">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus masukan untuk <strong>{{ itemToDelete?.target }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="closeDeleteModal"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant="danger"
                        size="sm"
                        :loading="isDeleting"
                        @click="executeDelete"
                    >
                        Ya, Hapus
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
