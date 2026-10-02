<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    companies: Object,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');

const { debouncedFn: submitSearch } = useDebounce((val) => {
    router.get(
        route('admin.companies.index'),
        { search: val },
        { preserveState: true, replace: true }
    );
}, 350);

const onSearchInput = (val) => {
    searchQuery.value = val;
    submitSearch(val);
};

const deleteCompany = (company) => {
    if (confirm(`Apakah Anda yakin ingin menghapus perusahaan "${company.name}" beserta akun pemiliknya? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('admin.companies.destroy', company.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Manajemen Perusahaan Klien" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Perusahaan Klien (Tenants)</h1>
                    <p class="text-sm text-slate-500 mt-1">Daftar seluruh perusahaan yang terdaftar menggunakan sistem SaaS Frans HRIS.</p>
                </div>
                <div>
                    <Link :href="route('admin.companies.create')">
                        <Button variant="accent">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Perusahaan
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Daftar Klien Terdaftar</h2>
                    <p class="text-xs text-slate-500">Kelola akun pemilik dan identitas entitas perusahaan</p>
                </div>
                <div class="w-full sm:w-72">
                    <Input
                        :modelValue="searchQuery"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari perusahaan, email, kota..."
                        size="sm"
                    />
                </div>
            </div>

            <!-- Data Table -->
            <DataTable :headers="['Perusahaan', 'Akun Pemilik (Owner)', 'Tipe Bisnis', 'Lokasi', 'Karyawan', 'Aksi']">
                <template v-if="companies.data && companies.data.length > 0">
                    <tr v-for="item in companies.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs">
                                    {{ item.name?.charAt(0) || 'C' }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ item.name }}</p>
                                    <p class="text-xs text-slate-400">{{ item.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="font-medium text-slate-800">{{ item.owner?.name || 'Owner' }}</p>
                            <p class="text-xs text-slate-400">{{ item.owner?.email }}</p>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ item.business_type || 'Umum' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-slate-700 text-xs">
                            <p class="font-medium text-slate-900">{{ item.city || '-' }}</p>
                            <p class="text-slate-400">{{ item.province || '-' }}</p>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700">
                                {{ item.employees_count || 0 }} Staf
                            </span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('admin.companies.edit', item.id)"
                                    class="p-1.5 text-slate-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition"
                                    title="Edit Perusahaan"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </Link>
                                <button
                                    type="button"
                                    @click="deleteCompany(item)"
                                    class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                    title="Hapus Perusahaan"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <TableEmpty
                        title="Tidak ada data perusahaan"
                        message="Belum ada perusahaan klien yang terdaftar atau sesuai kata kunci pencarian Anda."
                        :colspan="6"
                    />
                </template>
            </DataTable>

            <!-- Pagination -->
            <TablePagination :pagination="companies" />
        </div>
    </AppLayout>
</template>
