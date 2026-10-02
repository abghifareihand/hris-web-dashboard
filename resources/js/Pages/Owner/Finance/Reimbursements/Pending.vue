<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import Badge from '@/Components/UI/Badge.vue'
import TablePagination from '@/Components/Table/TablePagination.vue'

const props = defineProps({
    pendingReimbursements: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const handleSearch = () => {
    router.get(route('owner.finance.reimbursements.pending.index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

// Modal State
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedItem = ref(null)

const approveForm = useForm({
    payout_method: 'payroll',
})

const rejectForm = useForm({
    reject_reason: '',
})

const openApprove = (item) => {
    selectedItem.value = item
    approveForm.reset()
    approveForm.payout_method = 'payroll'
    showApproveModal.value = true
}

const openReject = (item) => {
    selectedItem.value = item
    rejectForm.reset()
    showRejectModal.value = true
}

const submitApprove = () => {
    if (!selectedItem.value) return
    approveForm.post(route('owner.finance.reimbursements.pending.approve', selectedItem.value.id), {
        onSuccess: () => {
            showApproveModal.value = false
            selectedItem.value = null
        }
    })
}

const submitReject = () => {
    if (!selectedItem.value) return
    rejectForm.post(route('owner.finance.reimbursements.pending.reject', selectedItem.value.id), {
        onSuccess: () => {
            showRejectModal.value = false
            selectedItem.value = null
        }
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Persetujuan Klaim Biaya - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Persetujuan Klaim Biaya (Pending)</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Verifikasi pengajuan reimbursement karyawan dan tentukan metode pencairannya.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.reimbursements.index')">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Riwayat Klaim
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filter & Search -->
            <div class="bg-white p-4 rounded-xl border border-secondary-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-80">
                    <Input
                        v-model="search"
                        placeholder="Cari nama karyawan / NIP..."
                        @keyup.enter="handleSearch"
                    >
                        <template #prefix>
                            <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </template>
                    </Input>
                </div>
                <div class="text-sm text-secondary-600">
                    Menampilkan <span class="font-bold text-secondary-900">{{ pendingReimbursements.total }}</span> permohonan pending
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Tanggal Klaim</th>
                                <th class="px-5 py-3">Karyawan</th>
                                <th class="px-5 py-3">Deskripsi / Alasan</th>
                                <th class="px-5 py-3 text-right">Nominal</th>
                                <th class="px-5 py-3 text-center">Bukti / Nota</th>
                                <th class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="item in pendingReimbursements.data" :key="item.id" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-secondary-900 font-medium">
                                    {{ item.date }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-secondary-900">{{ item.employee?.name || '-' }}</div>
                                    <div class="text-xs text-secondary-500 font-mono">{{ item.employee?.nip || '-' }} • {{ item.employee?.division?.name || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="line-clamp-2 text-secondary-800">{{ item.reason }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right font-bold text-emerald-600 text-base">
                                    {{ formatCurrency(item.amount) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <a
                                        v-if="item.attachment"
                                        :href="'/storage/' + item.attachment"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs text-primary-600 font-medium hover:underline bg-primary-50 px-2.5 py-1.5 rounded-lg border border-primary-100"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        Lihat File
                                    </a>
                                    <span v-else class="text-xs text-secondary-400 italic">Tidak ada lampiran</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            @click="openApprove(item)"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Setujui
                                        </Button>
                                        <Button
                                            variant="danger"
                                            size="sm"
                                            @click="openReject(item)"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Tolak
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pendingReimbursements.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-secondary-500">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <p class="font-medium text-secondary-700">Tidak ada pengajuan klaim pending</p>
                                        <p class="text-xs text-secondary-400 mt-1">Semua klaim biaya telah diproses.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pendingReimbursements.links && pendingReimbursements.links.length > 3" class="p-4 border-t border-secondary-200">
                    <TablePagination :links="pendingReimbursements.links" />
                </div>
            </div>
        </div>

        <!-- Modal Setujui -->
        <Modal :show="showApproveModal" title="Persetujuan Klaim Biaya" @close="showApproveModal = false">
            <div class="space-y-4" v-if="selectedItem">
                <div class="p-4 rounded-xl bg-secondary-50 border border-secondary-200 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-secondary-500">Karyawan:</span>
                        <span class="font-semibold text-secondary-900">{{ selectedItem.employee?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-secondary-500">Nominal Klaim:</span>
                        <span class="font-bold text-emerald-600 text-base">{{ formatCurrency(selectedItem.amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-secondary-500">Keterangan:</span>
                        <span class="text-secondary-800">{{ selectedItem.reason }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-secondary-700">Pilih Metode Pencairan Dana:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label
                            class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                            :class="approveForm.payout_method === 'payroll' ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-secondary-200 hover:bg-secondary-50'"
                        >
                            <input type="radio" value="payroll" v-model="approveForm.payout_method" class="mt-1 text-primary-600 focus:ring-primary-500" />
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-secondary-900">Gabung Slip Gaji</span>
                                <span class="block text-xs text-secondary-500 mt-0.5">Dicairkan saat periode payroll bulanan berjalan.</span>
                            </div>
                        </label>
                        <label
                            class="flex items-start p-3.5 rounded-xl border cursor-pointer transition"
                            :class="approveForm.payout_method === 'direct' ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-secondary-200 hover:bg-secondary-50'"
                        >
                            <input type="radio" value="direct" v-model="approveForm.payout_method" class="mt-1 text-primary-600 focus:ring-primary-500" />
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-secondary-900">Transfer Langsung</span>
                                <span class="block text-xs text-secondary-500 mt-0.5">Langsung ditandai lunas (Paid) dan transfer terpisah.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-secondary-200">
                    <Button variant="secondary" @click="showApproveModal = false">Batal</Button>
                    <Button variant="primary" :disabled="approveForm.processing" @click="submitApprove">
                        {{ approveForm.processing ? 'Menyimpan...' : 'Konfirmasi Setujui' }}
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Modal Tolak -->
        <Modal :show="showRejectModal" title="Tolak Pengajuan Klaim" @close="showRejectModal = false">
            <div class="space-y-4" v-if="selectedItem">
                <p class="text-sm text-secondary-600">
                    Berikan alasan penolakan klaim biaya untuk <strong>{{ selectedItem.employee?.name }}</strong> sebesar <strong>{{ formatCurrency(selectedItem.amount) }}</strong>.
                </p>

                <div>
                    <label class="block text-sm font-semibold text-secondary-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea
                        v-model="rejectForm.reject_reason"
                        rows="3"
                        class="w-full rounded-xl border border-secondary-300 p-3 text-sm focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition outline-none"
                        placeholder="Contoh: Nota tidak jelas / pengeluaran di luar ketentuan dinas..."
                        required
                    ></textarea>
                    <span v-if="rejectForm.errors.reject_reason" class="text-xs text-rose-500 mt-1 block">
                        {{ rejectForm.errors.reject_reason }}
                    </span>
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-secondary-200">
                    <Button variant="secondary" @click="showRejectModal = false">Batal</Button>
                    <Button variant="danger" :disabled="rejectForm.processing || !rejectForm.reject_reason" @click="submitReject">
                        {{ rejectForm.processing ? 'Menyimpan...' : 'Tolak Klaim' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
