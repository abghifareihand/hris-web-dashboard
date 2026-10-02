<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';

const props = defineProps({
    announcements: Object,
    branches: Array,
    divisions: Array,
    stats: Object,
    filters: Object,
});

const filterForm = ref({
    branch_id: props.filters?.branch_id || '',
    division_id: props.filters?.division_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

const applyFilter = () => {
    router.get(route('owner.announcements.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    filterForm.value = {
        branch_id: '',
        division_id: '',
        start_date: '',
        end_date: '',
        search: '',
        per_page: 10,
    };
    applyFilter();
};

const deleteAnnouncement = (id, title) => {
    if (confirm(`Apakah Anda yakin ingin menghapus pengumuman "${title}"?`)) {
        router.delete(route('owner.announcements.destroy', id), {
            preserveScroll: true,
        });
    }
};

const getStatusBadge = (startDate, endDate) => {
    const today = new Date().toISOString().slice(0, 10);
    if (startDate > today) {
        return { label: 'Jadwal Mendatang', class: 'bg-blue-100 text-blue-800 border-blue-200' };
    }
    if (endDate < today) {
        return { label: 'Sudah Berakhir', class: 'bg-slate-100 text-slate-600 border-slate-200' };
    }
    return { label: 'Sedang Aktif', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Data Pengumuman" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Data Pengumuman</h1>
                    <p class="text-slate-500 text-sm mt-1">Kelola dan siarkan informasi penting bagi seluruh karyawan perusahaan.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.announcements.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Pengumuman</span>
                    </Link>
                </div>
            </div>

            <!-- 4 Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengumuman</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ stats?.total || 0 }} Item</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sedang Aktif</div>
                        <div class="text-2xl font-bold text-emerald-600 mt-0.5">{{ stats?.active || 0 }} Tayang</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Semua Cabang</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ stats?.all_branches || 0 }} Global</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jadwal Mendatang</div>
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ stats?.upcoming || 0 }} Item</div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5">
                <form @submit.prevent="applyFilter" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cabang Sasaran</label>
                            <select v-model="filterForm.branch_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Filter Cabang</option>
                                <option value="all">Khusus Semua Cabang (Global)</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Divisi Sasaran</label>
                            <select v-model="filterForm.division_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Filter Divisi</option>
                                <option value="all">Khusus Semua Divisi</option>
                                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.start_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
                            <input type="date" v-model="filterForm.end_date" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 items-end pt-3 border-t border-slate-100">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Cari Judul / Keterangan</label>
                            <input
                                type="text"
                                v-model="filterForm.search"
                                placeholder="Ketik kata kunci pengumuman..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="flex gap-2 w-full sm:w-auto">
                            <button
                                type="button"
                                @click="resetFilter"
                                class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                            >
                                Reset
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors"
                            >
                                Cari
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">Pengumuman</th>
                                <th class="px-5 py-3.5">Sasaran</th>
                                <th class="px-5 py-3.5 text-center">Periode Tayang</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ann in announcements.data" :key="ann.id" class="hover:bg-slate-50/75 transition-colors">
                                <td class="px-5 py-4 max-w-sm">
                                    <div class="font-bold text-slate-900">{{ ann.title }}</div>
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ ann.content }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ ann.branch?.name || 'Semua Cabang' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ ann.division?.name || 'Semua Divisi' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="font-medium text-slate-700">{{ formatDate(ann.start_date) }}</span>
                                    <span class="text-slate-400"> s/d </span>
                                    <span class="font-medium text-slate-700">{{ formatDate(ann.end_date) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold border', getStatusBadge(ann.start_date, ann.end_date).class]">
                                        {{ getStatusBadge(ann.start_date, ann.end_date).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Link
                                            :href="route('owner.announcements.show', ann.id)"
                                            class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Lihat"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <Link
                                            :href="route('owner.announcements.edit', ann.id)"
                                            class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            type="button"
                                            @click="deleteAnnouncement(ann.id, ann.title)"
                                            class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!announcements.data || announcements.data.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                    <p class="font-medium">Belum ada data pengumuman.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="announcements.links" class="p-4 border-t border-slate-100">
                    <TablePagination :links="announcements.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
