<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';
import Modal from '@/Components/UI/Modal.vue';
import { useDebounce } from '@/Composables/useDebounce';

const props = defineProps({
    swaps: Object,
    colleagues: Array,
    currentEmployeeId: Number,
    filters: Object,
});

// Filter State (1:1 Standar Owner)
const filters = reactive({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

watch(
    () => props.filters,
    (newFilters) => {
        filters.status = newFilters?.status || '';
        filters.search = newFilters?.search || '';
    },
    { deep: true }
);

const applyFilters = () => {
    router.get(
        route('employee.schedules.swap-team.index'),
        {
            status: filters.status || undefined,
            search: filters.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
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
    filters.status = '';
    filters.search = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return Boolean(filters.status || filters.search);
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
];

const isModalOpen = ref(false);

const colleagueOptions = computed(() => {
    return (props.colleagues || []).map((c) => ({
        value: c.id,
        label: `${c.name} (NIP: ${c.nip || '-'})`,
    }));
});

const form = useForm({
    target_employee_id: '',
    requestor_work_date: '',
    target_work_date: '',
    reason: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    if (colleagueOptions.value.length > 0) {
        form.target_employee_id = colleagueOptions.value[0].value;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitSwap = () => {
    form.post(route('employee.schedules.swap-team.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getBadgeVariant = (status) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'pending':
        default:
            return 'warning';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'approved':
            return 'Disetujui';
        case 'rejected':
            return 'Ditolak';
        case 'pending':
        default:
            return 'Menunggu Review';
    }
};

const tableHeaders = [
    { label: 'Pemohon Pertukaran', class: 'w-48' },
    { label: 'Rekan Kerja Dituju', class: 'w-48' },
    { label: 'Jadwal Pemohon', class: 'w-36' },
    { label: 'Jadwal Rekan', class: 'w-36' },
    { label: 'Alasan', class: 'w-64' },
    { label: 'Status', class: 'w-36 text-center' },
    { label: 'Diajukan', class: 'w-32 text-right' },
];
</script>

<template>
    <Head title="Tukar Jadwal Antar Rekan" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tukar Shift Tim</h1>
                    <p class="text-slate-500 text-sm mt-1">Ajukan permohonan pertukaran jadwal kerja atau shift dengan rekan kerja satu divisi.</p>
                </div>
                <div>
                    <Button
                        variant="primary"
                        @click="openModal"
                        class="shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajukan Tukar dengan Rekan
                    </Button>
                </div>
            </div>

            <!-- Filter Controls (1:1 Standar Owner Dashboard) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-2.5 border-b border-slate-100 rounded-t-2xl flex items-center justify-between min-h-[56px]">
                    <h2 class="text-base font-bold text-slate-900">
                        Filter Tukar Shift Tim
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
                            class="shrink-0 text-xs font-semibold gap-1.5 !h-8 !min-h-[32px] !px-3 !rounded-lg !bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 hover:!text-slate-900 shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </Button>
                    </transition>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <Select
                                label="Status Permohonan"
                                v-model="filters.status"
                                :options="statusOptions"
                                @change="onFilterChange"
                            />
                        </div>

                        <!-- Search Input -->
                        <div>
                            <Input
                                label="Cari Rekan / Alasan"
                                :modelValue="filters.search"
                                @update:modelValue="onSearchInput"
                                placeholder="Ketik nama rekan kerja atau alasan tukar..."
                            >
                                <template #prefix>
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </template>
                            </Input>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Section -->
            <DataTable :headers="tableHeaders">
                <template #body>
                    <template v-if="swaps.data && swaps.data.length > 0">
                        <tr
                            v-for="item in swaps.data"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="item.requestor_id === currentEmployeeId"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase"
                                    >
                                        Saya
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ item.requestor?.name || '-' }}</p>
                                        <p class="text-xs text-slate-400">NIP: {{ item.requestor?.nip || '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="item.target_employee_id === currentEmployeeId"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase"
                                    >
                                        Saya
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ item.target_employee?.name || '-' }}</p>
                                        <p class="text-xs text-slate-400">NIP: {{ item.target_employee?.nip || '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900 whitespace-nowrap text-xs">
                                {{ formatDate(item.requestor_work_date) }}
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900 whitespace-nowrap text-xs">
                                {{ formatDate(item.target_work_date) }}
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <p class="line-clamp-2 text-xs leading-relaxed text-slate-700">{{ item.reason }}</p>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                <Badge :variant="getBadgeVariant(item.status)" size="sm">
                                    {{ getStatusLabel(item.status) }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs text-slate-500 whitespace-nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                        </tr>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="7"
                        title="Belum Ada Pertukaran Jadwal Tim"
                        message="Anda dapat mengajukan tukar jadwal jaga / shift dengan rekan kerja yang bertugas di cabang dan divisi yang sama."
                    >
                        <template #action>
                            <Button variant="primary" size="sm" @click="openModal">
                                Ajukan Tukar dengan Rekan Sekarang
                            </Button>
                        </template>
                    </TableEmpty>
                </template>
            </DataTable>

            <!-- Pagination -->
            <TablePagination :pagination="swaps" />
        </div>

        <!-- Modal Ajukan Tukar Jadwal Tim -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Ajukan Tukar Jadwal Antar Rekan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih rekan kerja dan tanggal jadwal yang akan ditukar</p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submitSwap" class="space-y-4">
                <div>
                    <Select
                        label="Pilih Rekan Kerja"
                        v-model="form.target_employee_id"
                        :options="colleagueOptions"
                        :error="form.errors.target_employee_id"
                        required
                    />
                </div>

                <div>
                    <DatePicker
                        label="Tanggal Jadwal Anda (Yang Ingin Ditukar)"
                        v-model="form.requestor_work_date"
                        placeholder="Pilih Tanggal Jadwal Anda"
                        :error="form.errors.requestor_work_date"
                        required
                    />
                </div>

                <div>
                    <DatePicker
                        label="Tanggal Jadwal Rekan (Yang Ingin Anda Ambil)"
                        v-model="form.target_work_date"
                        placeholder="Pilih Tanggal Jadwal Rekan"
                        :error="form.errors.target_work_date"
                        required
                    />
                </div>

                <div>
                    <Textarea
                        label="Alasan Pertukaran Shift"
                        v-model="form.reason"
                        :rows="3"
                        placeholder="Jelaskan alasan permohonan pertukaran shift..."
                        :error="form.errors.reason"
                        required
                    />
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="closeModal"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Kirim Permohonan
                    </Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
