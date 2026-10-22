<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Modal from "@/Components/UI/Modal.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    feedback: {
        type: Object,
        required: true,
    },
});

const isDeleteModalOpen = ref(false);
const deleteForm = useForm({});

const executeDelete = () => {
    deleteForm.delete(route("owner.feedbacks.destroy", props.feedback.id));
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head :title="`Detail Kritik & Saran - ${feedback.target}`" />

    <div class="w-full space-y-6">
        <!-- Header Section with Standard Back Button -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3.5 sm:gap-4">
                <Link
                    :href="route('owner.feedbacks.index')"
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5 cursor-pointer"
                    title="Kembali ke Daftar Kritik & Saran"
                >
                    <svg
                        class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                </Link>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Detail Kritik & Saran
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200/90 font-mono font-semibold text-xs tracking-wider">
                            #FDB-{{ feedback.id }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 flex flex-wrap items-center gap-2">
                        <span class="font-medium text-slate-700">
                            {{ feedback.is_anonymous ? 'Anonim' : (feedback.employee?.name || 'Karyawan') }}
                        </span>
                        <span class="text-slate-300">•</span>
                        <span>Tanggal: {{ formatDate(feedback.created_at) }}</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons (Delete button on right) -->
            <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-center">
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

        <!-- Detail Information Cards (2 cols left, 1 col right) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Main Content (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Card 1: Kritik & Keluhan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <h2 class="text-base font-bold text-slate-900">
                                Kritik
                            </h2>
                        </div>
                        <span class="text-xs font-semibold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-100">
                            Keluhan
                        </span>
                    </div>

                    <div class="p-6">
                        <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-line bg-rose-50/50 p-5 rounded-2xl border border-rose-100/80">
                            {{ feedback.criticism }}
                        </div>
                    </div>
                </div>

                <!-- Card 2: Saran & Solusi -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h2 class="text-base font-bold text-slate-900">
                                Saran
                            </h2>
                        </div>
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-100">
                            Solusi
                        </span>
                    </div>

                    <div class="p-6">
                        <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-line bg-emerald-50/50 p-5 rounded-2xl border border-emerald-100/80">
                            {{ feedback.suggestion }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Submitter Info & Meta (1 col) -->
            <div class="space-y-6">
                <!-- Card: Informasi Pengirim & Sasaran -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Informasi Pengirim
                    </h2>

                    <!-- Status Pengirim -->
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            Status Privasi
                        </div>
                        <div v-if="feedback.is_anonymous" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Anonim (Rahasia)</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Karyawan memilih untuk merahasiakan identitas dirinya agar masukan dapat disampaikan secara jujur dan objektif.
                            </p>
                        </div>
                        <div v-else-if="feedback.employee" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center shrink-0">
                                    {{ feedback.employee.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 text-sm truncate">
                                        {{ feedback.employee.name }}
                                    </div>
                                    <div class="text-xs text-slate-500 truncate">
                                        {{ feedback.employee.email }}
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-slate-200/60 space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Jabatan:</span>
                                    <span class="font-semibold text-slate-700">{{ feedback.employee.position }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Divisi:</span>
                                    <span class="font-semibold text-slate-700">{{ feedback.employee.division }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Cabang:</span>
                                    <span class="font-semibold text-slate-700">{{ feedback.employee.branch }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pihak Sasaran -->
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                            Pihak yang Dituju
                        </div>
                        <div class="text-sm font-bold text-slate-900">
                            {{ feedback.target }}
                        </div>
                    </div>

                    <!-- Waktu Masuk -->
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                            Waktu Masuk
                        </div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ feedback.date_formatted }}
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ feedback.time_ago }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal Confirmation (Style matching Loans/Pending) -->
        <Modal
            :show="isDeleteModalOpen"
            maxWidth="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-2">
                <h3 class="text-base sm:text-lg font-bold text-slate-900">
                    Hapus Kritik & Saran
                </h3>
                <p class="text-sm text-slate-500">
                    Apakah Anda yakin ingin menghapus data kritik dan saran ini? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    @click="isDeleteModalOpen = false"
                    :disabled="deleteForm.processing"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="executeDelete"
                    :loading="deleteForm.processing"
                    :disabled="deleteForm.processing"
                >
                    Ya, Hapus Masukan
                </Button>
            </template>
        </Modal>
    </div>
</template>
