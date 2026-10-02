<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    announcement: Object,
});

const statusBadge = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    const start = props.announcement?.start_date ? props.announcement.start_date.slice(0, 10) : '';
    const end = props.announcement?.end_date ? props.announcement.end_date.slice(0, 10) : '';

    if (start > today) {
        return { label: 'Jadwal Mendatang', class: 'bg-blue-100 text-blue-800 border-blue-200' };
    }
    if (end < today) {
        return { label: 'Sudah Berakhir', class: 'bg-slate-100 text-slate-600 border-slate-200' };
    }
    return { label: 'Sedang Aktif', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const deleteAnnouncement = () => {
    if (confirm(`Apakah Anda yakin ingin menghapus pengumuman "${props.announcement?.title}"?`)) {
        router.delete(route('owner.announcements.destroy', props.announcement.id));
    }
};
</script>

<template>
    <Head :title="`Detail Pengumuman - ${announcement?.title}`" />

    <AppLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.announcements.index')"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Detail Pengumuman</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Informasi lengkap pengumuman perusahaan.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('owner.announcements.index')"
                        class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                    >
                        Kembali
                    </Link>
                    <Link
                        :href="route('owner.announcements.edit', announcement.id)"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Edit</span>
                    </Link>
                    <button
                        type="button"
                        @click="deleteAnnouncement"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-xs flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>

            <!-- Banner Header -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-xl font-bold text-slate-900">{{ announcement.title }}</h2>
                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border shadow-xs', statusBadge.class]">
                                    {{ statusBadge.label }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                Diterbitkan pada: {{ announcement.created_at ? new Date(announcement.created_at).toLocaleString('id-ID') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Cabang Sasaran</span>
                        <span class="font-bold text-slate-800">{{ announcement.branch?.name || 'Semua Cabang (Global)' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Divisi Sasaran</span>
                        <span class="font-bold text-slate-800">{{ announcement.division?.name || 'Semua Divisi (Global)' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Periode Tayang</span>
                        <span class="font-bold text-slate-800">
                            {{ formatDate(announcement.start_date) }} s/d {{ formatDate(announcement.end_date) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 space-y-6">
                <!-- Image if exists -->
                <div v-if="announcement.image" class="rounded-xl overflow-hidden border border-slate-200 max-h-96 flex items-center justify-center bg-slate-50">
                    <img :src="`/storage/${announcement.image}`" :alt="announcement.title" class="w-full h-auto object-cover" />
                </div>

                <!-- Content Text -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Isi Pengumuman</h3>
                    <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-line bg-slate-50/70 p-5 rounded-xl border border-slate-100">
                        {{ announcement.content }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
