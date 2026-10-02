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

const { debouncedFn: submitSearch } = useDebounce((val) => {
    router.get(
        route('owner.management.leaves.categories.index'),
        { search: val },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 350);

const onSearchInput = (val) => {
    search.value = val;
    submitSearch(val);
};

const confirmDelete = (cat) => {
    categoryToDelete.value = cat;
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!categoryToDelete.value) return;
    router.delete(route('owner.management.leaves.categories.destroy', categoryToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            categoryToDelete.value = null;
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
                <Link :href="route('owner.management.leaves.balance.index')">
                    <Button variant="secondary">
                        Sisa Kuota Karyawan
                    </Button>
                </Link>
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

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div class="w-full sm:max-w-xs">
                <Input
                    :modelValue="search"
                    @update:modelValue="onSearchInput"
                    placeholder="Cari nama kategori cuti..."
                />
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Nama Kategori', 'Kuota Standar (Hari / Tahun)', 'Aksi']">
            <template v-if="categories.data && categories.data.length > 0">
                <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-50/70 transition">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">{{ cat.name }}</div>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100 font-mono">
                            {{ cat.default_quota }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2">
                        <Link :href="route('owner.management.leaves.categories.edit', cat.id)">
                            <Button variant="ghost" size="sm">Edit</Button>
                        </Link>
                        <Button variant="danger" size="sm" @click="confirmDelete(cat)">Hapus</Button>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty :colspan="3" message="Belum ada kategori cuti yang terdaftar." />
            </template>
        </DataTable>

        <TablePagination :pagination="categories" />

        <!-- Delete Modal -->
        <Modal :show="isDeleteModalOpen" maxWidth="md" @close="isDeleteModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Hapus Kategori Cuti</h3>
                <p class="text-sm text-slate-600 mb-6">
                    Apakah Anda yakin ingin menghapus kategori cuti
                    <span class="font-semibold text-slate-900">{{ categoryToDelete?.name }}</span>?
                </p>
                <div class="flex items-center justify-end gap-3">
                    <Button variant="secondary" @click="isDeleteModalOpen = false">Batal</Button>
                    <Button variant="danger" @click="executeDelete">Hapus</Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
