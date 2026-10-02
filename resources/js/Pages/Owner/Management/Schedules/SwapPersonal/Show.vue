<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Badge from "@/Components/UI/Badge.vue";
import Modal from "@/Components/UI/Modal.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    swap: {
        type: Object,
        required: true,
    },
    originalSchedule: {
        type: Object,
        default: null,
    },
    targetSchedule: {
        type: Object,
        default: null,
    },
});

const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isProcessing = ref(false);

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const statusVariant = (status) => {
    switch (status) {
        case "approved":
            return "success";
        case "rejected":
            return "danger";
        case "pending":
        default:
            return "warning";
    }
};

const statusLabel = (status) => {
    switch (status) {
        case "approved":
            return "Disetujui";
        case "rejected":
            return "Ditolak";
        case "pending":
        default:
            return "Menunggu Persetujuan";
    }
};

const executeApprove = () => {
    isProcessing.value = true;
    router.post(
        route("owner.management.schedules.swap-personal.approve", props.swap.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                isApproveModalOpen.value = false;
            },
        }
    );
};

const executeReject = () => {
    isProcessing.value = true;
    router.post(
        route("owner.management.schedules.swap-personal.reject", props.swap.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                isRejectModalOpen.value = false;
            },
        }
    );
};
</script>

<template>
    <Head :title="`Detail Tukar Jadwal Mandiri #${swap.id}`" />

    <div class="w-full space-y-6">
        <!-- Top Banner / Header -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                        Detail Permohonan Tukar Jadwal Mandiri
                    </h1>
                    <Badge :variant="statusVariant(swap.status)" size="sm">
                        {{ statusLabel(swap.status) }}
                    </Badge>
                </div>
                <p class="text-xs sm:text-sm text-slate-500">
                    ID Permohonan: <span class="font-mono font-medium text-slate-700">#SWAP-P-{{ swap.id }}</span>
                    &bull; Diajukan pada {{ formatDateTime(swap.created_at) }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                <Link :href="route('owner.management.schedules.swap-personal.index')">
                    <Button variant="secondary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali ke Daftar</span>
                    </Button>
                </Link>

                <template v-if="swap.status === 'pending'">
                    <button
                        type="button"
                        @click="isRejectModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition cursor-pointer"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>Tolak</span>
                    </button>

                    <button
                        type="button"
                        @click="isApproveModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition cursor-pointer shadow-xs"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <span>Setujui Permohonan</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Employee Info & Schedule Shift Swap -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Employee Profile Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs flex items-center gap-4 sm:gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xl flex items-center justify-center shrink-0 shadow-xs ring-4 ring-emerald-50">
                        {{ swap.employee?.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900 truncate">
                                {{ swap.employee?.name || "-" }}
                            </h2>
                            <span v-if="swap.employee?.nip" class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md font-medium">
                                NIP: {{ swap.employee.nip }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ swap.employee?.branch?.name || "-" }} &bull; {{ swap.employee?.division?.name || "-" }}
                            <span v-if="swap.employee?.position">&bull; {{ swap.employee.position.name }}</span>
                        </p>
                    </div>
                </div>

                <!-- Schedule Swap Comparison Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                ⇄
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">
                                    Pertukaran Jadwal Mandiri
                                </h3>
                                <p class="text-xs text-slate-400">
                                    Pertukaran tanggal kerja dan shift pada kalender karyawan yang sama
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 relative">
                        <!-- Box 1: Tanggal Semula -->
                        <div class="p-4 sm:p-5 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-rose-100 text-rose-800">
                                    Tanggal Semula
                                </span>
                            </div>

                            <div>
                                <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                    Tanggal
                                </span>
                                <span class="text-sm font-semibold text-slate-800">
                                    {{ formatDate(swap.original_work_date) }}
                                </span>
                            </div>

                            <div class="pt-2 border-t border-slate-200/70">
                                <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                    Shift Terjadwal
                                </span>
                                <span v-if="originalSchedule?.is_day_off" class="text-xs font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                    Libur
                                </span>
                                <span v-else-if="originalSchedule?.shift" class="text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                    {{ originalSchedule.shift.name }} ({{ originalSchedule.shift.start_time?.slice(0, 5) }} - {{ originalSchedule.shift.end_time?.slice(0, 5) }})
                                </span>
                                <span v-else class="text-xs text-slate-500">
                                    Jadwal Reguler
                                </span>
                            </div>
                        </div>

                        <!-- Box 2: Tanggal Pengganti -->
                        <div class="p-4 sm:p-5 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                    Tanggal Pengganti
                                </span>
                            </div>

                            <div>
                                <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                    Tanggal
                                </span>
                                <span class="text-sm font-semibold text-slate-800">
                                    {{ formatDate(swap.target_work_date) }}
                                </span>
                            </div>

                            <div class="pt-2 border-t border-slate-200/70">
                                <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                    Shift Terjadwal
                                </span>
                                <span v-if="targetSchedule?.is_day_off" class="text-xs font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                    Libur
                                </span>
                                <span v-else-if="targetSchedule?.shift" class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                    {{ targetSchedule.shift.name }} ({{ targetSchedule.shift.start_time?.slice(0, 5) }} - {{ targetSchedule.shift.end_time?.slice(0, 5) }})
                                </span>
                                <span v-else class="text-xs text-slate-500">
                                    Jadwal Reguler
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reason Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-3">
                    <div class="flex items-center gap-2 text-slate-800 font-bold text-base">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                        <span>Alasan Pengajuan Pertukaran</span>
                    </div>

                    <div class="p-4 sm:p-5 rounded-xl bg-slate-50/70 border-l-4 border-emerald-500 border-y border-r border-slate-200/80">
                        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                            {{ swap.reason || "Tidak ada alasan spesifik yang dicantumkan." }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Metadata & Approval Audit -->
            <div class="space-y-6">
                <!-- Info Summary Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                        Informasi Permohonan
                    </h3>

                    <div class="divide-y divide-slate-100 text-xs">
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-500">Status</span>
                            <Badge :variant="statusVariant(swap.status)" size="sm">
                                {{ statusLabel(swap.status) }}
                            </Badge>
                        </div>
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-500">Waktu Pengajuan</span>
                            <span class="font-medium text-slate-700">{{ formatDateTime(swap.created_at) }}</span>
                        </div>
                        <div v-if="swap.status !== 'pending'" class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-500">Diproses Oleh</span>
                            <span class="font-medium text-slate-800">{{ swap.approver?.name || "Administrator" }}</span>
                        </div>
                        <div v-if="swap.status !== 'pending'" class="py-2.5 flex items-center justify-between">
                            <span class="text-slate-500">Waktu Proses</span>
                            <span class="font-medium text-slate-700">{{ formatDateTime(swap.updated_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Info Notice -->
                <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-800 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Catatan Sinkronisasi Sistem</span>
                    </div>
                    <p class="leading-relaxed">
                        Saat permohonan disetujui, shift dan jadwal pada tanggal semula dan tanggal pengganti untuk karyawan ini akan otomatis tertukar di sistem absensi.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Setujui -->
        <Modal
            :show="isApproveModalOpen"
            maxWidth="md"
            @close="isApproveModalOpen = false"
        >
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">
                    Setujui Tukar Jadwal Mandiri?
                </h3>
                <p class="text-sm text-slate-600 text-center mt-2">
                    Menyetujui pertukaran jadwal untuk
                    <span class="font-semibold text-slate-900">{{ swap.employee?.name }}</span>
                    antara tanggal <span class="font-semibold text-slate-900">{{ formatDate(swap.original_work_date) }}</span>
                    dan tanggal <span class="font-semibold text-slate-900">{{ formatDate(swap.target_work_date) }}</span>.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <Button
                        variant="secondary"
                        @click="isApproveModalOpen = false"
                        :disabled="isProcessing"
                    >
                        Batal
                    </Button>
                    <Button
                        variant="primary"
                        @click="executeApprove"
                        :loading="isProcessing"
                    >
                        Ya, Setujui
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Modal Tolak -->
        <Modal
            :show="isRejectModalOpen"
            maxWidth="md"
            @close="isRejectModalOpen = false"
        >
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">
                    Tolak Tukar Jadwal Mandiri?
                </h3>
                <p class="text-sm text-slate-600 text-center mt-2">
                    Apakah Anda yakin ingin menolak permohonan pertukaran jadwal ini? Status akan diperbarui menjadi ditolak.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <Button
                        variant="secondary"
                        @click="isRejectModalOpen = false"
                        :disabled="isProcessing"
                    >
                        Batal
                    </Button>
                    <Button
                        variant="danger"
                        @click="executeReject"
                        :loading="isProcessing"
                    >
                        Ya, Tolak
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
