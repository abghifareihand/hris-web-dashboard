<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const categoryToDelete = ref(null);
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const { debouncedFn: submitSearch } = useDebounce((val) => {
    router.get(
        route('owner.management.leaves.categories.index'),
        { search: val || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 350);

const onSearchInput = (val) => {
    search.value = val;
    submitSearch(val);
};

const resetFilter = () => {
    search.value = '';
    submitSearch('');
};

const confirmDelete = (cat) => {
    categoryToDelete.value = cat;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!categoryToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('owner.management.leaves.categories.destroy', categoryToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            categoryToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Kategori Cuti Karyawan" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kategori Cuti</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Kelola jenis-jenis cuti kerja (Tahunan, Sakit, Melahirkan, Khusus) dan kuota default tahunannya.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.leaves.categories.create')">
                    <Button variant="primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Kategori
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Kategori Cuti
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
                        v-if="Boolean(search)"
                        variant="secondary"
                        size="sm"
                        type="button"
                        @click="resetFilter"
                        class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>Reset</span>
                    </Button>
                </transition>
            </div>

            <div class="p-6">
                <Input
                    label="Cari"
                    :modelValue="search"
                    @update:modelValue="onSearchInput"
                    placeholder="Cari nama kategori cuti..."
                />
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Nama Kategori', 'Jatah Cuti Pertahun', '']">
            <template v-if="categories.data && categories.data.length > 0">
                <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-50/70 transition">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">{{ cat.name }}</div>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                            {{ cat.default_quota }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-1">
                            <Link
                                :href="route('owner.management.leaves.categories.edit', cat.id)"
                                class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                title="Edit Kategori Cuti"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </Link>
                            <button
                                type="button"
                                @click="confirmDelete(cat)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                title="Hapus Kategori Cuti"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty :colspan="3" message="Belum ada kategori cuti yang terdaftar." />
            </template>
        </DataTable>

        <TablePagination :pagination="categories" />

        <!-- Delete Modal -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Kategori Cuti
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus kategori cuti
                    <span class="font-semibold text-slate-900">{{ categoryToDelete?.name }}</span>?
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="isDeleting"
                    @click="isDeleteModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    :loading="isDeleting"
                    :disabled="isDeleting"
                    @click="executeDelete"
                >
                    Ya, Hapus
                </Button>
            </template>
        </Modal>
    </div>
</template>
