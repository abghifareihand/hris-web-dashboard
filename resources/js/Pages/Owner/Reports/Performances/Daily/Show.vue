<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    report: Object,
});

const emp = computed(() => props.report?.user?.employee || {});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Detail Kerja Harian - ${emp.name || report.user?.name}`" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.reports.performances.daily.index')"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Detail Laporan Kerja Harian</h1>
                        <p class="text-slate-500 text-sm mt-0.5">
                            Rincian aktivitas yang dilaporkan oleh <span class="font-semibold text-slate-800">{{ emp.name || report.user?.name }}</span>.
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('owner.reports.performances.daily.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                >
                    Kembali
                </Link>
            </div>

            <!-- Employee Info Banner -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-bold text-2xl shadow-xs shrink-0">
                            {{ (emp.name || report.user?.name || 'K').charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-xl font-bold text-slate-900">{{ emp.name || report.user?.name || 'Karyawan' }}</h2>
                                <span v-if="emp.nip" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ emp.nip }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-2 mt-3 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Email</span>
                                    <span class="font-semibold text-slate-800">{{ report.user?.email || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Cabang</span>
                                    <span class="font-semibold text-slate-800">{{ emp.branch?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Divisi</span>
                                    <span class="font-semibold text-slate-800">{{ emp.division?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Jabatan</span>
                                    <span class="font-semibold text-slate-800">{{ emp.position?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full lg:w-auto border-t lg:border-t-0 pt-4 lg:pt-0 border-slate-100">
                        <div class="flex-1 lg:flex-initial px-5 py-3 rounded-xl bg-slate-50 border border-slate-200 text-left lg:text-right">
                            <div class="text-xs text-slate-400 font-medium">Tanggal Pelaporan</div>
                            <div class="text-sm font-bold text-slate-900 mt-0.5">{{ formatDate(report.date) }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Dibuat: {{ report.created_at ? new Date(report.created_at).toLocaleString('id-ID') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 space-y-6">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Judul Aktivitas</span>
                    <h3 class="text-xl font-bold text-slate-900">{{ report.title }}</h3>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Uraian / Ringkasan Pekerjaan</span>
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-100">
                        {{ report.description || 'Tidak ada uraian rinci yang disertakan.' }}
                    </div>
                </div>

                <!-- Attachments -->
                <div class="pt-4 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-3">Lampiran Berkas ({{ report.attachments ? report.attachments.length : 0 }})</span>
                    <div v-if="report.attachments && report.attachments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <a
                            v-for="att in report.attachments"
                            :key="att.id"
                            :href="att.file_url || att.path"
                            target="_blank"
                            class="p-4 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/30 transition-colors flex items-center gap-3 group"
                        >
                            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-xs font-semibold text-slate-800 group-hover:text-emerald-700 truncate">
                                    {{ att.file_name || 'Berkas Lampiran' }}
                                </div>
                                <div class="text-[11px] text-slate-400">Klik untuk melihat berkas</div>
                            </div>
                        </a>
                    </div>
                    <div v-else class="text-xs text-slate-400 italic">
                        Tidak ada berkas lampiran pada laporan kerja ini.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
