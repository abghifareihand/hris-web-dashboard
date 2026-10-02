<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    pendingLeaves: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const selectedLeave = ref(null);
const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isProcessing = ref(false);

const rejectForm = useForm({
    reject_reason: '',
});

const { debouncedFn: submitSearch } = useDebounce((val) => {
    router.get(
        route('owner.management.leaves.pending.index'),
        { search: val },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 350);

const onSearchInput = (val) => {
    search.value = val;
    submitSearch(val);
};

const openApprove = (leave) => {
    selectedLeave.value = leave;
    isApproveModalOpen.value = true;
};

const openReject = (leave) => {
    selectedLeave.value = leave;
    rejectForm.reset();
    isRejectModalOpen.value = true;
};

const executeApprove = () => {
    if (!selectedLeave.value) return;
    isProcessing.value = true;
    router.post(route('owner.management.leaves.pending.approve', selectedLeave.value.id), {}, {
        onFinish: () => {
            isProcessing.value = false;
            isApproveModalOpen.value = false;
            selectedLeave.value = null;
        },
    });
};

const executeReject = () => {
    if (!selectedLeave.value) return;
    rejectForm.post(route('owner.management.leaves.pending.reject', selectedLeave.value.id), {
        onSuccess: () => {
            isRejectModalOpen.value = false;
            selectedLeave.value = null;
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Persetujuan Cuti Karyawan" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Persetujuan Pengajuan Cuti</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        {{ pendingLeaves.total || 0 }} Menunggu
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Verifikasi dan setujui permohonan cuti tahunan, sakit, atau izin khusus dari karyawan.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('owner.management.leaves.index')">
                    <Button variant="secondary">
                        Riwayat Semua Cuti
                    </Button>
                </Link>
                <Link :href="route('owner.management.leaves.categories.index')">
                    <Button variant="secondary">
                        Kategori Cuti
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
            <div class="w-full sm:max-w-xs">
                <Input
                    :modelValue="search"
                    @update:modelValue="onSearchInput"
                    placeholder="Cari nama karyawan atau NIP..."
                />
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Karyawan', 'Jenis Cuti', 'Periode Cuti', 'Durasi', 'Alasan Cuti', 'Aksi']">
            <template v-if="pendingLeaves.data && pendingLeaves.data.length > 0">
                <tr v-for="item in pendingLeaves.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-800">{{ item.employee?.name || '-' }}</div>
                        <div class="text-xs text-slate-400">
                            {{ item.employee?.branch?.name || '-' }} &bull; {{ item.employee?.division?.name || '-' }}
                        </div>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                            {{ item.leave_category?.name || 'Cuti' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.start_date) }}
                        <span v-if="item.end_date && item.end_date !== item.start_date" class="text-slate-400">
                            &mdash; {{ formatDate(item.end_date) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                            {{ item.days_count || 1 }} Hari
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs truncate">
                        {{ item.reason || '-' }}
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2">
                        <Button variant="danger" size="sm" @click="openReject(item)">Tolak</Button>
                        <Button variant="primary" size="sm" @click="openApprove(item)">Setujui</Button>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty :colspan="6" message="Tidak ada permohonan cuti yang sedang menunggu persetujuan." />
            </template>
        </DataTable>

        <TablePagination :pagination="pendingLeaves" />

        <!-- Modal Setujui -->
        <Modal :show="isApproveModalOpen" maxWidth="md" @close="isApproveModalOpen = false">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">Setujui Permohonan Cuti?</h3>
                <p class="text-sm text-slate-600 text-center mt-2">
                    Menyetujui cuti <span class="font-semibold text-slate-900">{{ selectedLeave?.employee?.name }}</span>
                    sebanyak <span class="font-semibold text-slate-900">{{ selectedLeave?.days_count }} hari</span>.
                    Saldo kuota cuti karyawan akan dipotong dan catatan absensi otomatis disinkronisasi.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <Button variant="secondary" @click="isApproveModalOpen = false" :disabled="isProcessing">Batal</Button>
                    <Button variant="primary" @click="executeApprove" :loading="isProcessing">Ya, Setujui</Button>
                </div>
            </div>
        </Modal>

        <!-- Modal Tolak -->
        <Modal :show="isRejectModalOpen" maxWidth="md" @close="isRejectModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Tolak Permohonan Cuti</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Berikan alasan penolakan cuti untuk karyawan <span class="font-semibold text-slate-800">{{ selectedLeave?.employee?.name }}</span>:
                </p>
                <form @submit.prevent="executeReject" class="space-y-4">
                    <div>
                        <textarea
                            v-model="rejectForm.reject_reason"
                            rows="3"
                            placeholder="Alasan penolakan..."
                            class="w-full text-sm rounded-xl border-slate-300 focus:border-rose-500 focus:ring-rose-500 shadow-2xs p-3 text-slate-700"
                            required
                        ></textarea>
                        <p v-if="rejectForm.errors.reject_reason" class="text-xs text-rose-500 mt-1">
                            {{ rejectForm.errors.reject_reason }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Button variant="secondary" type="button" @click="isRejectModalOpen = false" :disabled="rejectForm.processing">
                            Batal
                        </Button>
                        <Button variant="danger" type="submit" :loading="rejectForm.processing">
                            Tolak Cuti
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
