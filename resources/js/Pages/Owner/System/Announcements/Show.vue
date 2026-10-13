<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Badge from "@/Components/UI/Badge.vue";
import Modal from "@/Components/UI/Modal.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    announcement: {
        type: Object,
        required: true,
    },
});

const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const executeDelete = () => {
    deleteForm.delete(route("owner.announcements.destroy", props.announcement.id));
};

const statusBadge = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    const start = props.announcement?.start_date ? props.announcement.start_date.slice(0, 10) : "";
    const end = props.announcement?.end_date ? props.announcement.end_date.slice(0, 10) : "";

    if (start && start > today) {
        return { label: "Jadwal Mendatang", variant: "primary" };
    }
    if (end && end < today) {
        return { label: "Sudah Berakhir", variant: "secondary" };
    }
    return { label: "Sedang Aktif", variant: "success" };
});

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    } catch {
        return dateStr;
    }
};

const formatPublishedDate = (dateStr) => {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head :title="`Detail Pengumuman - ${announcement.title}`" />

    <div class="w-full space-y-6">
        <!-- Top Banner Header (Style matching Employees/Show.vue) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                <!-- Megaphone Icon with soft ring -->
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-2xl flex items-center justify-center shrink-0 shadow-xs ring-4 ring-emerald-50">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>

                <div class="space-y-1.5 min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight truncate">
                            {{ announcement.title }}
                        </h1>
                        <Badge :variant="statusBadge.variant" size="sm">
                            {{ statusBadge.label }}
                        </Badge>
                    </div>

                    <!-- Metadata Pills -->
                    <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                        <span>
                            Diterbitkan: {{ formatPublishedDate(announcement.created_at) }}
                        </span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                            Cabang: {{ announcement.branch?.name || 'Semua Cabang' }}
                        </span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                            Divisi: {{ announcement.division?.name || 'Semua Divisi' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <Link :href="route('owner.announcements.index')">
                    <Button variant="secondary">
                        Kembali
                    </Button>
                </Link>
                <Link :href="route('owner.announcements.edit', announcement.id)">
                    <Button variant="primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Edit</span>
                    </Button>
                </Link>
                <Button
                    variant="danger"
                    type="button"
                    @click="isDeleteModalOpen = true"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus</span>
                </Button>
            </div>
        </div>

        <!-- Detail Information Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Main Content (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Content Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900">
                            Isi Pengumuman
                        </h2>
                    </div>

                    <div class="p-6 space-y-5">
                        <!-- Banner Image if exists -->
                        <div
                            v-if="announcement.image"
                            class="rounded-2xl overflow-hidden border border-slate-200/80 shadow-xs bg-slate-50"
                        >
                            <img
                                :src="`/storage/${announcement.image}`"
                                :alt="announcement.title"
                                class="w-full h-auto max-h-96 object-cover"
                            />
                        </div>

                        <!-- Content Text -->
                        <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-line bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                            {{ announcement.content }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Info & Periode (1 col) -->
            <div class="space-y-6">
                <!-- Card: Pengaturan Tayang -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Informasi Tayang
                    </h2>

                    <!-- Periode -->
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                            Periode Penayangan
                        </div>
                        <div class="text-sm font-bold text-slate-900">
                            {{ formatDate(announcement.start_date) }}
                        </div>
                        <div class="text-xs text-slate-400 my-0.5">sampai dengan</div>
                        <div class="text-sm font-bold text-slate-900">
                            {{ formatDate(announcement.end_date) }}
                        </div>
                    </div>

                    <!-- Sasaran Cabang -->
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                            Cabang Sasaran
                        </div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ announcement.branch?.name || 'Semua Cabang (Global)' }}
                        </div>
                    </div>

                    <!-- Sasaran Divisi -->
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                            Divisi Sasaran
                        </div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ announcement.division?.name || 'Semua Divisi (Global)' }}
                        </div>
                    </div>

                    <!-- Status Tayang -->
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                            Status Saat Ini
                        </div>
                        <Badge :variant="statusBadge.variant">
                            {{ statusBadge.label }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal Confirmation -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="sm"
            @close="isDeleteModalOpen = false"
        >
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Pengumuman
                </h3>
                <p class="text-sm text-slate-500 mt-2">
                    Apakah Anda yakin ingin menghapus pengumuman
                    <span class="font-semibold text-slate-700">"{{ announcement.title }}"</span>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="deleteForm.processing"
                    @click="isDeleteModalOpen = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    :loading="deleteForm.processing"
                    :disabled="deleteForm.processing"
                    @click="executeDelete"
                >
                    Ya, Hapus
                </Button>
            </template>
        </Modal>
    </div>
</template>
