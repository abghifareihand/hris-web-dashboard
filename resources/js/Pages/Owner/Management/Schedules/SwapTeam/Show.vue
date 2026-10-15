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
    requestorSchedule: {
        type: Object,
        default: null,
    },
    targetSchedule: {
        type: Object,
        default: null,
    },
    currentSchedules: {
        type: Object,
        default: () => ({}),
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

const formatShiftTime = (shift) => {
    if (!shift) return "";
    const inTime = (shift.clock_in || shift.start_time)?.slice(0, 5);
    const outTime = (shift.clock_out || shift.end_time)?.slice(0, 5);
    if (inTime && outTime) {
        return `(${inTime} - ${outTime})`;
    }
    return "";
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
        route("owner.management.schedules.swap-team.approve", props.swap.id),
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
        route("owner.management.schedules.swap-team.reject", props.swap.id),
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
    <Head :title="`Detail Tukar Jadwal Tim #${swap.id}`" />

    <div class="w-full space-y-6">
        <!-- Top Banner / Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5 sm:gap-4">
                <Link
                    :href="route('owner.management.schedules.swap-team.index')"
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5 cursor-pointer"
                    title="Kembali ke Daftar Tukar Jadwal Tim"
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
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Detail Permohonan Tukar Jadwal Tim
                        </h1>
                        <Badge :variant="statusVariant(swap.status)" size="sm">
                            {{ statusLabel(swap.status) }}
                        </Badge>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500">
                        ID Permohonan: <span class="font-mono font-medium text-slate-700">#SWAP-T-{{ swap.id }}</span>
                        &bull; Diajukan pada {{ formatDateTime(swap.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div v-if="swap.status === 'pending'" class="flex items-center gap-2.5 shrink-0 flex-wrap">
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
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Schedule Comparison & Reason -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Comparison Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                ⇄
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-800">
                                    Pertukaran Jadwal Kerja
                                </h2>
                                <p class="text-xs text-slate-400">
                                    Perbandingan jadwal dan shift antara kedua karyawan
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 relative">
                        <!-- Left: Pemohon -->
                        <div class="p-4 sm:p-5 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                    Pemohon
                                </span>
                                <span v-if="swap.requestor?.nip" class="text-xs font-mono text-slate-400">
                                    NIP: {{ swap.requestor.nip }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-lg flex items-center justify-center shrink-0 shadow-xs">
                                    {{ swap.requestor?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">
                                        {{ swap.requestor?.name || "-" }}
                                    </h3>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ swap.requestor?.branch?.name || "-" }} &bull; {{ swap.requestor?.division?.name || "-" }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-200/70 space-y-2">
                                <div>
                                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                        Tanggal Kerja
                                    </span>
                                    <span class="text-sm font-semibold text-slate-800">
                                        {{ formatDate(swap.requestor_work_date) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                        {{ swap.status === 'approved' ? 'Shift Yang Ditukarkan' : 'Shift Terjadwal' }}
                                    </span>
                                    <span v-if="requestorSchedule?.is_day_off" class="text-xs font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                        Libur
                                    </span>
                                    <span v-else-if="requestorSchedule?.shift" class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                        {{ requestorSchedule.shift.name }} {{ formatShiftTime(requestorSchedule.shift) }}
                                    </span>
                                    <span v-else class="text-xs text-slate-500">
                                        Jadwal Reguler
                                    </span>
                                </div>

                                <div v-if="swap.status === 'approved'" class="mt-2.5 p-2.5 bg-emerald-50/70 border border-emerald-200/60 rounded-lg text-xs space-y-1">
                                    <div class="flex items-center gap-1.5 font-semibold text-emerald-800">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Status Pasca Tukar:</span>
                                    </div>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">
                                        &bull; Pada <strong>{{ formatDate(swap.requestor_work_date) }}</strong>: <span class="font-semibold text-amber-700">Libur</span> (digantikan {{ swap.target_employee?.name }}).
                                    </p>
                                    <p v-if="swap.requestor_work_date !== swap.target_work_date" class="text-slate-600 text-[11px] leading-relaxed">
                                        &bull; Pada <strong>{{ formatDate(swap.target_work_date) }}</strong>: <span class="font-semibold text-emerald-700">Bekerja {{ targetSchedule?.shift?.name ? '(' + targetSchedule.shift.name + ' ' + formatShiftTime(targetSchedule.shift) + ')' : '' }}</span> (menggantikan {{ swap.target_employee?.name }}).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Rekan Target -->
                        <div class="p-4 sm:p-5 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-teal-100 text-teal-800">
                                    Rekan Pengganti
                                </span>
                                <span v-if="swap.target_employee?.nip" class="text-xs font-mono text-slate-400">
                                    NIP: {{ swap.target_employee.nip }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white font-bold text-lg flex items-center justify-center shrink-0 shadow-xs">
                                    {{ swap.target_employee?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">
                                        {{ swap.target_employee?.name || "-" }}
                                    </h3>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ swap.target_employee?.branch?.name || "-" }} &bull; {{ swap.target_employee?.division?.name || "-" }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-200/70 space-y-2">
                                <div>
                                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                        Tanggal Kerja
                                    </span>
                                    <span class="text-sm font-semibold text-slate-800">
                                        {{ formatDate(swap.target_work_date) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-medium text-slate-400 uppercase tracking-wider block">
                                        {{ swap.status === 'approved' ? 'Shift Yang Ditukarkan' : 'Shift Terjadwal' }}
                                    </span>
                                    <span v-if="targetSchedule?.is_day_off" class="text-xs font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                        Libur
                                    </span>
                                    <span v-else-if="targetSchedule?.shift" class="text-xs font-semibold text-teal-700 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-md inline-block mt-0.5">
                                        {{ targetSchedule.shift.name }} {{ formatShiftTime(targetSchedule.shift) }}
                                    </span>
                                    <span v-else class="text-xs text-slate-500">
                                        Jadwal Reguler
                                    </span>
                                </div>

                                <div v-if="swap.status === 'approved'" class="mt-2.5 p-2.5 bg-teal-50/70 border border-teal-200/60 rounded-lg text-xs space-y-1">
                                    <div class="flex items-center gap-1.5 font-semibold text-teal-800">
                                        <svg class="w-3.5 h-3.5 text-teal-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Status Pasca Tukar:</span>
                                    </div>
                                    <p v-if="swap.requestor_work_date !== swap.target_work_date" class="text-slate-600 text-[11px] leading-relaxed">
                                        &bull; Pada <strong>{{ formatDate(swap.requestor_work_date) }}</strong>: <span class="font-semibold text-emerald-700">Bekerja {{ requestorSchedule?.shift?.name ? '(' + requestorSchedule.shift.name + ' ' + formatShiftTime(requestorSchedule.shift) + ')' : '' }}</span> (menggantikan {{ swap.requestor?.name }}).
                                    </p>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">
                                        &bull; Pada <strong>{{ formatDate(swap.target_work_date) }}</strong>: <span class="font-semibold text-amber-700">Libur</span> (digantikan {{ swap.requestor?.name }}).
                                    </p>
                                </div>
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
                <div v-if="swap.status === 'approved'" class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200/80 text-xs text-emerald-800 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Sinkronisasi Selesai</span>
                    </div>
                    <p class="leading-relaxed text-emerald-700">
                        Jadwal kerja dan shift kedua karyawan pada tanggal terkait telah otomatis diperbarui dan disinkronisasikan ke sistem absensi.
                    </p>
                </div>
                <div v-else-if="swap.status === 'rejected'" class="p-4 rounded-xl bg-rose-50/80 border border-rose-200/80 text-xs text-rose-800 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold text-rose-900">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Permohonan Ditolak</span>
                    </div>
                    <p class="leading-relaxed text-rose-700">
                        Permohonan ini telah ditolak. Jadwal kerja kedua karyawan tetap sesuai jadwal awal.
                    </p>
                </div>
                <div v-else class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-800 space-y-1.5">
                    <div class="flex items-center gap-1.5 font-bold">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Catatan Sinkronisasi Sistem</span>
                    </div>
                    <p class="leading-relaxed">
                        Saat permohonan disetujui, jadwal kerja dan shift kedua karyawan pada tanggal yang bersangkutan akan otomatis tertukar di sistem absensi.
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
                    Setujui Tukar Jadwal Tim?
                </h3>
                <p class="text-sm text-slate-600 text-center mt-2">
                    Menyetujui pertukaran jadwal antara
                    <span class="font-semibold text-slate-900">{{ swap.requestor?.name }}</span> dan
                    <span class="font-semibold text-slate-900">{{ swap.target_employee?.name }}</span>.
                    Jadwal kerja kedua karyawan akan otomatis disesuaikan di sistem.
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
                    Tolak Tukar Jadwal Tim?
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
