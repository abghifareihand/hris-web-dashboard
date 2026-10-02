<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
});

const markAllRead = () => {
    router.post(route('owner.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Pusat Notifikasi" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Notifikasi Sistem</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Semua pemberitahuan aktivitas absensi, cuti, lembur, dan pengajuan karyawan.
                </p>
            </div>
            <Button
                v-if="notifications.data && notifications.data.length > 0"
                variant="secondary"
                size="sm"
                @click="markAllRead"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Tandai Semua Dibaca
            </Button>
        </div>

        <!-- Notification List -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
            <template v-if="notifications.data && notifications.data.length > 0">
                <div
                    v-for="item in notifications.data"
                    :key="item.id"
                    :class="[
                        'p-5 transition hover:bg-slate-50/70 flex items-start gap-4',
                        !item.read_at ? 'bg-emerald-50/30' : 'bg-white'
                    ]"
                >
                    <!-- Dot Indicator -->
                    <div class="mt-1.5">
                        <span
                            :class="[
                                'inline-block w-2.5 h-2.5 rounded-full',
                                !item.read_at ? 'bg-emerald-500 shadow-xs ring-4 ring-emerald-100' : 'bg-slate-200'
                            ]"
                        ></span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h3 :class="['text-sm font-semibold truncate', !item.read_at ? 'text-slate-900' : 'text-slate-700']">
                                {{ item.data?.title || 'Pemberitahuan Sistem' }}
                            </h3>
                            <span class="text-[11px] text-slate-400 shrink-0">
                                {{ formatDate(item.created_at) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 line-clamp-2">
                            {{ item.data?.message || item.data?.content || 'Tidak ada detail pesan.' }}
                        </p>
                    </div>

                    <!-- Action -->
                    <div class="shrink-0">
                        <a
                            :href="route('owner.notifications.read', item.id)"
                            class="inline-flex items-center justify-center p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition"
                            title="Buka notifikasi"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="py-16 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-700">Belum ada notifikasi</h3>
                    <p class="text-xs text-slate-400 mt-1">Semua aktivitas terbaru akan ditampilkan di sini.</p>
                </div>
            </template>
        </div>

        <TablePagination :pagination="notifications" />
    </div>
</template>
