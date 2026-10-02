<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { getScheduleTabs } from '@/Utils/tabs';
import TablePagination from '@/Components/Table/TablePagination.vue';
import Modal from '@/Components/UI/Modal.vue';

const tabs = computed(() => getScheduleTabs());

const props = defineProps({
    swaps: Object,
    colleagues: Array,
    currentEmployeeId: Number,
});

const isModalOpen = ref(false);

const form = useForm({
    target_employee_id: '',
    requestor_work_date: '',
    target_work_date: '',
    reason: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved':
            return { label: 'Disetujui', class: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
        case 'rejected':
            return { label: 'Ditolak', class: 'bg-rose-50 text-rose-700 ring-rose-600/20' };
        default:
            return { label: 'Menunggu Review', class: 'bg-amber-50 text-amber-700 ring-amber-600/20' };
    }
};
</script>

<template>
    <Head title="Tukar Jadwal Antar Rekan" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Jadwal Kerja & Tukar Shift</h1>
                    <p class="text-sm text-slate-500 mt-1">Ajukan permohonan pertukaran jadwal kerja / shift dengan rekan kerja satu tim.</p>
                </div>
                <button
                    type="button"
                    @click="openModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 active:scale-[0.99]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajukan Tukar dengan Rekan
                </button>
            </div>

            <!-- Schedule Tabs Component -->
            <Tabs :items="tabs" />

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Riwayat Pengajuan Tukar Jadwal Tim</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar permohonan pertukaran shift antara Anda dan kolega Anda</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5">Pihak Pemohon</th>
                                <th class="px-5 py-3.5">Pihak Dituju</th>
                                <th class="px-5 py-3.5">Jadwal Pemohon</th>
                                <th class="px-5 py-3.5">Jadwal Rekan</th>
                                <th class="px-5 py-3.5">Alasan</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Diajukan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal">
                            <tr v-if="!swaps.data || swaps.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center text-sky-500 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">Belum ada pertukaran jadwal tim</p>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">Anda dapat menukar shift jaga atau hari kerja dengan rekan kantor yang bertugas di departemen yang sama.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="item in swaps.data" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="item.requestor_id === currentEmployeeId"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 uppercase"
                                        >
                                            Saya
                                        </span>
                                        <div>
                                            <p class="font-medium text-slate-900">{{ item.requestor?.name || '-' }}</p>
                                            <p class="text-xs text-slate-400">NIP: {{ item.requestor?.nip || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="item.target_employee_id === currentEmployeeId"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase"
                                        >
                                            Saya
                                        </span>
                                        <div>
                                            <p class="font-medium text-slate-900">{{ item.target_employee?.name || '-' }}</p>
                                            <p class="text-xs text-slate-400">NIP: {{ item.target_employee?.nip || '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-900 whitespace-nowrap font-medium">
                                    {{ formatDate(item.requestor_work_date) }}
                                </td>
                                <td class="px-5 py-4 text-slate-900 whitespace-nowrap font-medium">
                                    {{ formatDate(item.target_work_date) }}
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="line-clamp-2 text-xs leading-relaxed text-slate-600">{{ item.reason }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            getStatusBadge(item.status).class,
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset'
                                        ]"
                                    >
                                        {{ getStatusBadge(item.status).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(item.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="swaps.links && swaps.links.length > 3" class="p-4 border-t border-slate-100">
                    <TablePagination :links="swaps.links" />
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Tukar Jadwal Tim -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <template #title>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
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
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Pilih Rekan Kerja <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="form.target_employee_id"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        required
                    >
                        <option value="" disabled>-- Pilih Karyawan Rekan --</option>
                        <option v-for="colleague in colleagues" :key="colleague.id" :value="colleague.id">
                            {{ colleague.name }} (NIP: {{ colleague.nip }})
                        </option>
                    </select>
                    <p v-if="form.errors.target_employee_id" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.target_employee_id }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Jadwal Anda (Yang Ingin Ditukar) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        v-model="form.requestor_work_date"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        required
                    />
                    <p v-if="form.errors.requestor_work_date" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.requestor_work_date }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Jadwal Rekan (Yang Ingin Anda Ambil) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        v-model="form.target_work_date"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                        required
                    />
                    <p v-if="form.errors.target_work_date" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.target_work_date }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alasan Penukaran <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="form.reason"
                        rows="3"
                        class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                        placeholder="Alasan permohonan pertukaran shift..."
                        required
                    ></textarea>
                    <p v-if="form.errors.reason" class="text-xs text-rose-500 mt-1">
                        {{ form.errors.reason }}
                    </p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50"
                    >
                        <span v-if="form.processing">Mengirim...</span>
                        <span v-else>Kirim Permohonan</span>
                    </button>
                </div>
            </form>
        </Modal>
    </EmployeeLayout>
</template>
