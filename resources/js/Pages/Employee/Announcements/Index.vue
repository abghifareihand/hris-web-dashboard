<script setup>
import { ref, reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    announcements: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.announcements.index'),
        {
            status: filters.status || undefined,
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
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.search || filters.status);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'active', label: 'Sedang Aktif' },
    { value: 'upcoming', label: 'Jadwal Mendatang' },
    { value: 'past', label: 'Sudah Berakhir' },
];

const tableHeaders = [
    { label: 'Pengumuman', class: 'min-w-[280px]' },
    { label: 'Sasaran', class: 'w-48' },
    { label: 'Periode Tayang', class: 'w-56 text-center' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Aksi', class: 'w-24 text-right' },
];

// Detail Modal State
const isDetailModalOpen = ref(false);
const selectedAnnouncement = ref(null);

const openDetail = (item) => {
    selectedAnnouncement.value = item;
    isDetailModalOpen.value = true;
};

const closeDetail = () => {
    isDetailModalOpen.value = false;
    selectedAnnouncement.value = null;
};

// Status Helpers
const getStatusBadge = (startDate, endDate) => {
    const today = new Date().toISOString().slice(0, 10);
    const start = startDate ? startDate.slice(0, 10) : '';
    const end = endDate ? endDate.slice(0, 10) : '';

    if (start && start > today) {
        return { label: 'Jadwal Mendatang', variant: 'primary' };
    }
    if (end && end < today) {
        return { label: 'Sudah Berakhir', variant: 'secondary' };
    }
    return { label: 'Sedang Aktif', variant: 'success' };
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Pengumuman Perusahaan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengumuman Perusahaan</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Informasi penting, surat edaran, dan kabar resmi dari pimpinan & manajemen.
                </p>
            </div>

            <!-- Filter Controls (Sesuai Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Pengumuman
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
                                label="Status Pengumuman"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Search Input -->
                        <div>
                            <Input
                                label="Cari Pengumuman"
                                :modelValue="filters.search"
                                @update:modelValue="onSearchInput"
                                placeholder="Ketik judul pengumuman atau kata kunci keterangan..."
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

            <!-- Announcements Table (DataTable Component) -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="announcements.data && announcements.data.length > 0">
                        <tr
                            v-for="ann in announcements.data"
                            :key="ann.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Pengumuman (Judul & Keterangan singkat) -->
                            <td class="px-5 py-3.5 max-w-sm">
                                <div
                                    @click="openDetail(ann)"
                                    class="font-bold text-slate-900 text-sm hover:text-emerald-600 transition-colors cursor-pointer"
                                >
                                    {{ ann.title }}
                                </div>
                                <div class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                                    {{ ann.content }}
                                </div>
                            </td>

                            <!-- Sasaran (Cabang & Divisi) -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 text-xs">
                                    {{ ann.branch?.name || 'Semua Cabang' }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ ann.division?.name || 'Semua Divisi' }}
                                </div>
                            </td>

                            <!-- Periode Tayang -->
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="font-medium text-slate-700 text-xs">
                                    {{ formatDate(ann.start_date) }}
                                </span>
                                <span class="text-slate-400 text-xs"> s/d </span>
                                <span class="font-medium text-slate-700 text-xs">
                                    {{ formatDate(ann.end_date) }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <Badge :variant="getStatusBadge(ann.start_date, ann.end_date).variant">
                                    {{ getStatusBadge(ann.start_date, ann.end_date).label }}
                                </Badge>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="openDetail(ann)"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                    title="Lihat Detail Pengumuman"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="5"
                        title="Belum Ada Pengumuman"
                        message="Tidak ada pengumuman yang sesuai dengan kriteria filter yang dipilih."
                    />
                </template>
            </DataTable>

            <!-- Table Pagination -->
            <TablePagination :pagination="announcements" />
        </div>

        <!-- Modal Detail Pengumuman -->
        <Modal :show="isDetailModalOpen" @close="closeDetail" maxWidth="2xl">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Detail Pengumuman</h3>
                        <p class="text-xs text-slate-500 mt-0.5" v-if="selectedAnnouncement">
                            Dipublikasikan: {{ formatDate(selectedAnnouncement.created_at) }}
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="selectedAnnouncement" class="space-y-4">
                <!-- Cover Image (If Available) -->
                <div
                    v-if="selectedAnnouncement.image"
                    class="rounded-xl overflow-hidden max-h-72 w-full bg-slate-100 border border-slate-200"
                >
                    <img
                        :src="selectedAnnouncement.image.startsWith('http') ? selectedAnnouncement.image : `/storage/${selectedAnnouncement.image}`"
                        :alt="selectedAnnouncement.title"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Meta Badges -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <Badge :variant="getStatusBadge(selectedAnnouncement.start_date, selectedAnnouncement.end_date).variant">
                        {{ getStatusBadge(selectedAnnouncement.start_date, selectedAnnouncement.end_date).label }}
                    </Badge>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 text-slate-700">
                        Cabang: {{ selectedAnnouncement.branch?.name || 'Semua Cabang' }}
                    </span>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 text-slate-700">
                        Divisi: {{ selectedAnnouncement.division?.name || 'Semua Divisi' }}
                    </span>
                    <span class="text-xs text-slate-400 ml-auto">
                        Masa Tayang: {{ formatDate(selectedAnnouncement.start_date) }} - {{ formatDate(selectedAnnouncement.end_date) }}
                    </span>
                </div>

                <!-- Title & Content -->
                <div class="pt-2 border-t border-slate-100">
                    <h2 class="text-lg font-bold text-slate-900 leading-snug">
                        {{ selectedAnnouncement.title }}
                    </h2>
                    <div class="mt-3 text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/70 p-4 rounded-xl border border-slate-100">
                        {{ selectedAnnouncement.content }}
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <Button variant="secondary" @click="closeDetail">
                        Tutup
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
