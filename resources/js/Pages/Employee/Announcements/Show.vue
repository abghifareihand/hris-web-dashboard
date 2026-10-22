<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    announcement: Object,
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head :title="announcement.title + ' - Pengumuman Perusahaan'" />

    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Back Button -->
            <div>
                <Link
                    :href="route('employee.announcements.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Pengumuman
                </Link>
            </div>

            <!-- Main Article Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div v-if="announcement.image_url" class="w-full max-h-96 overflow-hidden bg-slate-100">
                    <img
                        :src="announcement.image_url"
                        :alt="announcement.title"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center gap-2 flex-wrap">
                        <Badge variant="neutral">
                            {{ announcement.branch?.name || 'Semua Cabang' }}
                        </Badge>
                        <Badge v-if="announcement.division?.name" variant="neutral">
                            {{ announcement.division.name }}
                        </Badge>
                        <span class="text-xs text-slate-400 ml-auto">
                            Dipublikasikan: {{ formatDate(announcement.created_at) }}
                        </span>
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            {{ announcement.title }}
                        </h1>
                        <p class="text-xs text-slate-500 mt-2">
                            Masa Berlaku Pengumuman: <strong>{{ formatDate(announcement.start_date) }}</strong> s/d <strong>{{ formatDate(announcement.end_date) }}</strong>
                        </p>
                    </div>

                    <div class="prose prose-slate max-w-none text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                        {{ announcement.content }}
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <Link :href="route('employee.announcements.index')">
                            <Button variant="secondary">
                                Kembali ke Pengumuman
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
