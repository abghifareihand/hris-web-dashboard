<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/UI/Button.vue'
import Input from '@/Components/UI/Input.vue'

const props = defineProps({
    ptkpList: Array,
})

// Interactive Simulator
const simSalary = ref(10000000)
const simPtkp = ref('TK/0')

const selectedPtkpObj = computed(() => {
    return props.ptkpList.find(p => p.code === simPtkp.value) || props.ptkpList[0]
})

const simResult = computed(() => {
    const gross = Math.min(Number(simSalary.value) || 0, 12000000)
    const biayaJabatan = Math.min(gross * 0.05, 500000)
    const ptkpYearly = selectedPtkpObj.value?.yearly || 54000000
    
    // BPJS estimation (approximate for sim: 1% kes, 2% jht, 1% jp)
    const totalBpjsKaryawan = gross * 0.04
    const totalBpjsPerusahaan = gross * 0.0454

    const netoMonthly = gross - biayaJabatan - totalBpjsKaryawan - totalBpjsPerusahaan
    const netoYearly = netoMonthly * 12
    const pkpYearly = Math.max(0, netoYearly - ptkpYearly)
    const pph21Yearly = pkpYearly * 0.05
    const pph21Monthly = Math.round(pph21Yearly / 12)

    return {
        gross,
        biayaJabatan,
        netoMonthly,
        netoYearly,
        ptkpYearly,
        pkpYearly,
        pph21Monthly,
        terCategory: selectedPtkpObj.value?.ter_category,
    }
})

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}
</script>

<template>
    <AppLayout>
        <Head title="Kalkulasi Pajak PPh 21 & PTKP - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 tracking-tight">Kalkulasi Pajak PPh 21 & PTKP</h1>
                    <p class="text-sm text-secondary-500 mt-1">
                        Pedoman Penghasilan Tidak Kena Pajak (PTKP) dan Tarif Efektif Rata-Rata (TER) resmi.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('owner.finance.taxes.bpjs-tk')">
                        <Button variant="secondary" size="md">BPJS Ketenagakerjaan</Button>
                    </Link>
                    <Link :href="route('owner.finance.taxes.bpjs-kes')">
                        <Button variant="secondary" size="md">BPJS Kesehatan</Button>
                    </Link>
                </div>
            </div>

            <!-- Tax Simulator Widget -->
            <div class="bg-gradient-to-br from-primary-900 to-secondary-900 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="max-w-md space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-primary-200 border border-white/15">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Simulasi Cepat PPh 21 Karyawan
                        </div>
                        <h2 class="text-xl font-bold">Kalkulator Estimasi Potongan Pajak Bulanan</h2>
                        <p class="text-xs text-white/70 leading-relaxed">
                            Hitung otomatis estimasi potongan PPh 21 berdasarkan Gaji Pokok dan status tanggungan keluarga (PTKP) menurut regulasi terbaru.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md p-5 rounded-xl border border-white/15 w-full lg:w-auto lg:min-w-[420px] space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-white/80 mb-1">Gaji Pokok (Rp)</label>
                                <input
                                    type="number"
                                    v-model="simSalary"
                                    class="w-full bg-white/15 border border-white/20 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-primary-400"
                                    placeholder="Contoh: 10000000"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-white/80 mb-1">Status PTKP</label>
                                <select
                                    v-model="simPtkp"
                                    class="w-full bg-secondary-800 border border-white/20 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-primary-400"
                                >
                                    <option v-for="p in ptkpList" :key="p.code" :value="p.code">
                                        {{ p.code }} ({{ p.category }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-lg bg-black/20 border border-white/10 flex items-center justify-between">
                            <div>
                                <div class="text-xs text-white/60">Estimasi PPh 21 Bulanan:</div>
                                <div class="text-2xl font-black text-emerald-400 mt-0.5">
                                    {{ formatCurrency(simResult.pph21Monthly) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold px-2 py-1 rounded bg-white/10 text-primary-200">
                                    Kategori: {{ simResult.terCategory }}
                                </span>
                                <div class="text-[11px] text-white/50 mt-1">PKP: {{ formatCurrency(simResult.pkpYearly) }}/thn</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PTKP Master Reference Table Card -->
            <div class="bg-white rounded-xl border border-secondary-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-secondary-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-secondary-900">Tabel Referensi Resmi PTKP & Kategori TER</h2>
                        <p class="text-xs text-secondary-500 mt-0.5">Standar Penghasilan Tidak Kena Pajak sesuai PMK & PP Republik Indonesia.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-primary-50 text-primary-700 border border-primary-200">
                        Total 12 Kategori Pajak
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-secondary-600">
                        <thead class="bg-secondary-50 border-b border-secondary-200 text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Kode PTKP</th>
                                <th class="px-5 py-3">Kategori</th>
                                <th class="px-5 py-3">Keterangan / Status Pernikahan</th>
                                <th class="px-5 py-3 text-right">PTKP Setahun</th>
                                <th class="px-5 py-3 text-right">PTKP Sebulan</th>
                                <th class="px-5 py-3 text-center">Golongan TER</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary-200">
                            <tr v-for="ptkp in ptkpList" :key="ptkp.code" class="hover:bg-secondary-50/50 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono font-bold text-primary-700">
                                    {{ ptkp.code }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs font-semibold text-secondary-800">
                                    {{ ptkp.category }}
                                </td>
                                <td class="px-5 py-3.5 text-secondary-700">
                                    {{ ptkp.description }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-secondary-900">
                                    {{ formatCurrency(ptkp.yearly) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right text-secondary-700 font-medium">
                                    {{ formatCurrency(ptkp.monthly) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                                        :class="ptkp.ter_category === 'TER A' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (ptkp.ter_category === 'TER B' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-purple-50 text-purple-700 border border-purple-200')"
                                    >
                                        {{ ptkp.ter_category }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
