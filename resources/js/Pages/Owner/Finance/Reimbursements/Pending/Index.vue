<script setup>
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    pendingReimbursements: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    search: props.filters.search || '',
    branch_id: props.filters.branch_id || '',
    division_id: props.filters.division_id || '',
    date: props.filters.date || '',
});

const selectedItem = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);

const approveForm = useForm({
    payout_method: 'payroll',
});

const rejectForm = useForm({
    reject_reason: '',
});

const applyFilters = () => {
    router.get(
        route('owner.finance.reimbursements.pending.index'),
        {
            search: filters.search || undefined,
            branch_id: filters.branch_id || undefined,
            division_id: filters.division_id || undefined,
            date: filters.date || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const { debouncedFn: debouncedSearch } = useDebounce(() => {
    applyFilters();
}, 350);

const onSearchInput = (val) => {
    filters.search = val;
    debouncedSearch();
};

const onFilterChange = () => {
    applyFilters();
};

const resetFilters = () => {
    filters.search = '';
    filters.branch_id = '';
    filters.division_id = '';
    filters.date = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(
        filters.search ||
        filters.branch_id ||
        filters.division_id ||
        filters.date
    );
};

const openApprove = (item) => {
    selectedItem.value = item;
    approveForm.reset();
    approveForm.payout_method = 'payroll';
    showApproveModal.value = true;
};

const openReject = (item) => {
    selectedItem.value = item;
    rejectForm.reset();
    showRejectModal.value = true;
};

const submitApprove = () => {
    if (!selectedItem.value) return;
    approveForm.post(route('owner.finance.reimbursements.pending.approve', selectedItem.value.id), {
        onSuccess: () => {
            showApproveModal.value = false;
            selectedItem.value = null;
        },
    });
};

const submitReject = () => {
    if (!selectedItem.value) return;
    if (!rejectForm.reject_reason || !rejectForm.reject_reason.trim()) {
        rejectForm.setError('reject_reason', 'Alasan penolakan wajib diisi.');
        return;
    }
    rejectForm.post(route('owner.finance.reimbursements.pending.reject', selectedItem.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedItem.value = null;
            rejectForm.reset();
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

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <Head title="Persetujuan Klaim Biaya - Frans HRIS" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Persetujuan Klaim Biaya (Pending)</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Verifikasi pengajuan reimbursement karyawan dan tentukan metode pencairannya.
                </p>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                <h2 class="text-base font-bold text-slate-900">
                    Filter Klaim Biaya
                </h2>

                <!-- Reset Filter Button -->
                <transition
                    enter-active-class="transition-opacity duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <Button
                        v-if="hasActiveFilters()"
                        variant="secondary"
                        size="sm"
                        type="button"
                        @click="resetFilters"
                        class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs cursor-pointer"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        <span>Reset</span>
                    </Button>
                </transition>
            </div>

            <div class="p-6 space-y-4">
                <!-- Row 1: Dropdown Filters (Cabang, Divisi, Tanggal Klaim) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Cabang -->
                    <div>
                        <Select
                            label="Cabang"
                            v-model="filters.branch_id"
                            :options="branches.map((b) => ({ value: b.id, label: b.name }))"
                            all-label="Semua Cabang"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Divisi -->
                    <div>
                        <Select
                            label="Divisi"
                            v-model="filters.division_id"
                            :options="divisions.map((d) => ({ value: d.id, label: d.name }))"
                            all-label="Semua Divisi"
                            @change="onFilterChange"
                        />
                    </div>

                    <!-- Tanggal Klaim -->
                    <div>
                        <DatePicker
                            label="Tanggal Klaim"
                            v-model="filters.date"
                            placeholder="Pilih Tanggal"
                            @change="onFilterChange"
                        />
                    </div>
                </div>

                <!-- Row 2: Search field -->
                <div class="pt-3 border-t border-slate-100">
                    <Input
                        label="Cari"
                        :modelValue="filters.search"
                        @update:modelValue="onSearchInput"
                        placeholder="Cari nama karyawan atau NIP..."
                    />
                </div>
            </div>
        </div>

        <!-- Table -->
        <DataTable :headers="['Karyawan', 'Tanggal Klaim', 'Deskripsi / Alasan', 'Nominal', 'Bukti / Nota', '']">
            <template v-if="pendingReimbursements.data && pendingReimbursements.data.length > 0">
                <tr v-for="item in pendingReimbursements.data" :key="item.id" class="hover:bg-slate-50/70 transition">
                    <!-- Karyawan (Nama di depan) -->
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ item.employee?.name?.charAt(0).toUpperCase() || 'E' }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800 truncate">
                                    {{ item.employee?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-400 truncate flex items-center gap-1.5 mt-0.5 font-mono">
                                    <span>{{ item.employee?.nip || '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ item.employee?.division?.name || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Tanggal Klaim (Formatted: 22 Sep 2026) -->
                    <td class="px-5 py-3.5 text-xs font-semibold text-slate-700 whitespace-nowrap">
                        {{ formatDate(item.date) }}
                    </td>

                    <!-- Deskripsi / Alasan -->
                    <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs">
                        <p class="line-clamp-2">{{ item.reason || '-' }}</p>
                    </td>

                    <!-- Nominal -->
                    <td class="px-5 py-3.5 whitespace-nowrap font-bold text-emerald-600 text-sm">
                        {{ formatCurrency(item.amount) }}
                    </td>

                    <!-- Bukti / Nota -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <a
                            v-if="item.attachment"
                            :href="'/storage/' + item.attachment"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-medium hover:underline bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-100 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>Lihat File</span>
                        </a>
                        <span v-else class="text-xs text-slate-400 italic">Tidak ada lampiran</span>
                    </td>

                    <!-- Aksi (Sesuai gaya Employees/Pending.vue) -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="openReject(item)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50/90 hover:bg-rose-100/90 border border-rose-200/80 rounded-lg transition cursor-pointer"
                                title="Tolak Klaim"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                                    />
                                </svg>
                                <span>Tolak</span>
                            </button>
                            <button
                                type="button"
                                @click="openApprove(item)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50/90 hover:bg-emerald-100/90 border border-emerald-200/80 rounded-lg transition cursor-pointer"
                                title="Setujui Klaim"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span>Setujui</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <template v-else>
                <TableEmpty
                    :colspan="6"
                    message="Tidak ada pengajuan klaim biaya yang sedang menunggu persetujuan."
                />
            </template>
        </DataTable>

        <!-- Pagination -->
        <TablePagination :pagination="pendingReimbursements" />

        <!-- Modal Setujui -->
        <Modal
            :show="showApproveModal"
            maxWidth="md"
            @close="showApproveModal = false"
        >
            <div class="space-y-4" v-if="selectedItem">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Setujui Klaim Biaya
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Verifikasi persetujuan pengajuan klaim biaya dan tentukan metode pencairan dananya.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Karyawan:</span>
                        <span class="font-semibold text-slate-900">{{ selectedItem.employee?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Nominal Klaim:</span>
                        <span class="font-bold text-emerald-600 text-base">{{ formatCurrency(selectedItem.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Tanggal Pengajuan:</span>
                        <span class="font-medium text-slate-700">{{ formatDate(selectedItem.date) }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Metode Pencairan Dana</label>
                    <div class="flex flex-col gap-2.5">
                        <label
                            class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                            :class="approveForm.payout_method === 'payroll' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <input type="radio" value="payroll" v-model="approveForm.payout_method" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <div class="ml-2.5">
                                <span class="block text-xs font-bold text-slate-900">Cairkan Bersama Gaji Bulanan (Payroll)</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">Otomatis ditambahkan sebagai komponen penambah pada slip gaji periode berjalan dan dibayarkan saat penggajian.</span>
                            </div>
                        </label>
                        <label
                            class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                            :class="approveForm.payout_method === 'direct' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <input type="radio" value="direct" v-model="approveForm.payout_method" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <div class="ml-2.5">
                                <span class="block text-xs font-bold text-slate-900">Bayar via Transfer Langsung Sekarang</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">Klaim ditransfer manual terpisah hari ini (di luar penggajian bulanan) dan langsung ditandai Lunas (Paid).</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="approveForm.processing"
                    @click="showApproveModal = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    @click="submitApprove"
                    :loading="approveForm.processing"
                    :disabled="approveForm.processing"
                >
                    Ya, Setujui
                </Button>
            </template>
        </Modal>

        <!-- Modal Tolak -->
        <Modal
            :show="showRejectModal"
            maxWidth="md"
            @close="showRejectModal = false"
        >
            <div class="space-y-4" v-if="selectedItem">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        Tolak Pengajuan Klaim Biaya
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Berikan alasan penolakan untuk pengajuan klaim ini. Alasan akan tersimpan dan dapat dilihat oleh karyawan.
                    </p>
                </div>

                <!-- Info Preview Card -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Nama Karyawan:</span>
                        <span class="font-semibold text-slate-900">{{ selectedItem.employee?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm" v-if="selectedItem.employee?.nip || selectedItem.employee?.division?.name">
                        <span class="text-slate-500">NIP / Divisi:</span>
                        <span class="font-mono text-xs text-slate-600">{{ selectedItem.employee?.nip || '-' }} &bull; {{ selectedItem.employee?.division?.name || '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Nominal Klaim:</span>
                        <span class="font-bold text-rose-600">{{ formatCurrency(selectedItem.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Tanggal Pengajuan:</span>
                        <span class="font-medium text-slate-700">{{ formatDate(selectedItem.date) }}</span>
                    </div>
                    <div class="flex justify-between items-start text-sm" v-if="selectedItem.reason">
                        <span class="text-slate-500 shrink-0">Keperluan Klaim:</span>
                        <span class="text-slate-700 text-right ml-4 italic">"{{ selectedItem.reason }}"</span>
                    </div>
                </div>

                <div>
                    <Textarea
                        label="Alasan Penolakan"
                        v-model="rejectForm.reject_reason"
                        :error="rejectForm.errors.reject_reason"
                        placeholder="Contoh: Bukti nota tidak terbaca atau pengeluaran di luar ketentuan dinas kantor..."
                        :rows="3"
                        required
                    />
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="rejectForm.processing"
                    @click="showRejectModal = false"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    @click="submitReject"
                    :loading="rejectForm.processing"
                    :disabled="rejectForm.processing || !rejectForm.reject_reason?.trim()"
                >
                    Ya, Tolak
                </Button>
            </template>
        </Modal>
    </div>
</template>
