<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Input from '@/Components/UI/Input.vue'
import Modal from '@/Components/UI/Modal.vue'
import DataTable from '@/Components/Table/DataTable.vue'
import TableEmpty from '@/Components/Table/TableEmpty.vue'
import ClientTablePagination from '@/Components/Table/ClientTablePagination.vue'

const props = defineProps({
    thr: Object,
})

// Search Query Filter & Pagination
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)

watch(searchQuery, () => {
    currentPage.value = 1
})

const filteredItems = computed(() => {
    if (!props.thr?.items) return []
    if (!searchQuery.value.trim()) return props.thr.items

    const q = searchQuery.value.toLowerCase().trim()
    return props.thr.items.filter(item => {
        const name = item.employee?.name?.toLowerCase() || ''
        const nip = item.employee?.nip?.toLowerCase() || ''
        const division = item.employee?.division?.name?.toLowerCase() || ''
        const position = item.employee?.position?.name?.toLowerCase() || ''
        const branch = item.employee?.branch?.name?.toLowerCase() || ''
        return name.includes(q) || nip.includes(q) || division.includes(q) || position.includes(q) || branch.includes(q)
    })
})

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredItems.value.slice(start, start + perPage.value)
})

// Computed Stats
const averageThr = computed(() => {
    const count = Number(props.thr?.total_employees || 0)
    const total = Number(props.thr?.total_amount || 0)
    return count > 0 ? Math.round(total / count) : 0
})

// Modal Konfirmasi Status
const isStatusModalOpen = ref(false)
const isUpdatingStatus = ref(false)

const openConfirmStatusModal = () => {
    isStatusModalOpen.value = true
}

const executePay = () => {
    isUpdatingStatus.value = true
    router.post(route('owner.finance.thr.pay', props.thr.id), {}, {
        onFinish: () => {
            isUpdatingStatus.value = false
            isStatusModalOpen.value = false
        }
    })
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head :title="`Detail THR ${thr.code} - Frans HRIS`" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-start gap-3.5 sm:gap-4">
                    <Link
                        :href="route('owner.finance.thr.index')"
                        class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                        title="Kembali ke Daftar THR"
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

                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                                {{ thr.holiday_name }}
                            </h1>
                            <Badge v-if="thr.status === 'paid'" variant="success">Lunas (Paid)</Badge>
                            <Badge v-else-if="thr.status === 'process'" variant="warning">Draft / Siap Bayar</Badge>
                            <Badge v-else variant="secondary">{{ thr.status }}</Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/80 text-xs">
                                {{ thr.code }}
                            </span>
                            <span class="text-slate-300">•</span>
                            <span>Tanggal Bayar: <strong class="font-semibold text-slate-700">{{ formatDate(thr.payment_date) }}</strong></span>
                            <span class="text-slate-300">•</span>
                            <span>Cakupan: <strong class="font-semibold text-slate-700">{{ thr.branch?.name || 'Semua Cabang' }}</strong></span>
                            <span class="text-slate-300">•</span>
                            <span>Tahun: <strong class="font-semibold text-slate-700">{{ thr.year }}</strong></span>
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi di Atas -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <a :href="route('owner.finance.thr.slips', thr.id)" target="_blank">
                        <Button variant="secondary" size="md">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak Semua Slip
                        </Button>
                    </a>

                    <!-- Tombol Perubahan Status -->
                    <Button
                        v-if="thr.status === 'process'"
                        variant="primary"
                        size="md"
                        @click="openConfirmStatusModal"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Tandai Selesai (PAID)
                    </Button>
                </div>
            </div>

            <!-- Stats Overview Cards (3 Kolom Ringkas) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- 1. Total Dana THR -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Alokasi Dana THR</div>
                        <div class="text-2xl font-bold text-emerald-600 truncate mt-0.5">{{ formatCurrency(thr.total_amount) }}</div>
                    </div>
                </div>

                <!-- 2. Jumlah Penerima -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jumlah Penerima</div>
                        <div class="text-2xl font-bold text-indigo-700 truncate mt-0.5">{{ thr.total_employees }} Karyawan</div>
                    </div>
                </div>

                <!-- 3. Rata-rata per Karyawan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rata-rata per Karyawan</div>
                        <div class="text-2xl font-bold text-slate-900 truncate mt-0.5">{{ formatCurrency(averageThr) }}</div>
                    </div>
                </div>
            </div>

            <!-- Table Card: Rincian THR per Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Header Card & Search Filter -->
                <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Daftar Rincian THR per Karyawan</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar kalkulasi nominal pembagian THR seluruh karyawan pada hari raya ini.</p>
                    </div>

                    <div class="w-full md:w-72">
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari karyawan, NIP, divisi..."
                            clearable
                            size="sm"
                        />
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    :headers="[
                        'Karyawan',
                        'Masa Kerja',
                        'Basis Gaji',
                        'Skema THR',
                        'Nominal THR',
                        '',
                    ]"
                >
                    <template v-if="paginatedItems && paginatedItems.length > 0">
                        <tr v-for="item in paginatedItems" :key="item.id" class="hover:bg-slate-50/70 transition">
                            <!-- Karyawan -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ item.employee?.name?.charAt(0).toUpperCase() || 'E' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 text-sm truncate">
                                            {{ item.employee?.name || '-' }}
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5 truncate flex items-center gap-1.5">
                                            <span>{{ item.employee?.nip || '-' }}</span>
                                            <span>•</span>
                                            <span>{{ item.employee?.division?.name || item.employee?.position?.name || '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Masa Kerja -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ item.tenure_months }} bulan
                                </span>
                            </td>

                            <!-- Basis Gaji -->
                            <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-800 text-sm">
                                {{ formatCurrency(item.basis_amount) }}
                            </td>

                            <!-- Skema THR (1x Gaji Penuh / Prorata) -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="item.prorate_multiplier >= 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' : 'bg-amber-50 text-amber-700 border border-amber-200/80'"
                                >
                                    <svg v-if="item.prorate_multiplier >= 1" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ item.prorate_multiplier >= 1 ? '1x Gaji Penuh' : `Prorata (${item.tenure_months}/12)` }}</span>
                                </span>
                            </td>

                            <!-- Nominal THR -->
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-emerald-600 text-sm">
                                {{ formatCurrency(item.net_amount) }}
                            </td>

                            <!-- Aksi (Cetak Slip) -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <a
                                        :href="route('owner.finance.thr.slip-single', { id: thr.id, itemId: item.id })"
                                        target="_blank"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition inline-flex items-center justify-center cursor-pointer"
                                        title="Cetak Slip THR Karyawan"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <TableEmpty
                            title="Tidak ada karyawan ditemukan"
                            message="Tidak ada data rincian THR yang sesuai dengan kata kunci pencarian Anda."
                            :colspan="6"
                        />
                    </template>
                </DataTable>
            </div>

            <!-- Table Pagination -->
            <ClientTablePagination
                v-model:current-page="currentPage"
                :total-items="filteredItems.length"
                :per-page="perPage"
            />
        </div>

        <!-- Modal Konfirmasi Pembayaran THR -->
        <Modal :show="isStatusModalOpen" @close="isStatusModalOpen = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Konfirmasi Pembayaran THR</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Tandai perhitungan THR ini sebagai <strong>PAID / Lunas</strong> kepada seluruh karyawan.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <Button variant="secondary" size="md" @click="isStatusModalOpen = false" :disabled="isUpdatingStatus">
                        Batal
                    </Button>
                    <Button variant="primary" size="md" @click="executePay" :loading="isUpdatingStatus">
                        Ya, Tandai Selesai
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
