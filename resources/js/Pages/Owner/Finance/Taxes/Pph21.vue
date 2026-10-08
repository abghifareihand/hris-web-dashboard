<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    ptkpList: {
        type: Array,
        default: () => []
    },
    progressiveRates: {
        type: Array,
        default: () => []
    }
})

// Fallback jika progressiveRates belum ada dari props
const defaultProgressiveRates = [
    {
        tier: 'Tier 1',
        range_label: 'Rp 0 - Rp 60.000.000',
        rate_label: '5%',
        description: 'Penghasilan kena pajak sampai dengan Rp 60 juta',
        color: 'blue'
    },
    {
        tier: 'Tier 2',
        range_label: 'Rp 60.000.000 - Rp 250.000.000',
        rate_label: '15%',
        description: 'Penghasilan kena pajak di atas Rp 60 juta s/d Rp 250 juta',
        color: 'indigo'
    },
    {
        tier: 'Tier 3',
        range_label: 'Rp 250.000.000 - Rp 500.000.000',
        rate_label: '25%',
        description: 'Penghasilan kena pajak di atas Rp 250 juta s/d Rp 500 juta',
        color: 'amber'
    },
    {
        tier: 'Tier 4',
        range_label: 'Rp 500.000.000 - Rp 5.000.000.000',
        rate_label: '30%',
        description: 'Penghasilan kena pajak di atas Rp 500 juta s/d Rp 5 miliar',
        color: 'orange'
    },
    {
        tier: 'Tier 5',
        range_label: 'Di atas Rp 5.000.000.000',
        rate_label: '35%',
        description: 'Penghasilan kena pajak di atas Rp 5 miliar',
        color: 'rose'
    }
]

const tierList = props.progressiveRates && props.progressiveRates.length > 0 
    ? props.progressiveRates 
    : defaultProgressiveRates

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0)
}

const getPtkpBadgeClass = (code) => {
    if (code.startsWith('TK/')) {
        return 'bg-blue-50 text-blue-600 border-blue-200/80'
    }
    if (code.startsWith('K/I/')) {
        return 'bg-purple-50 text-purple-600 border-purple-200/80'
    }
    return 'bg-emerald-50 text-emerald-600 border-emerald-200/80'
}

const getTierBadgeClass = (tier) => {
    switch (tier) {
        case 'Tier 1':
            return 'bg-blue-50 text-blue-600 border-blue-200/80'
        case 'Tier 2':
            return 'bg-indigo-50 text-indigo-600 border-indigo-200/80'
        case 'Tier 3':
            return 'bg-amber-50 text-amber-600 border-amber-200/80'
        case 'Tier 4':
            return 'bg-orange-50 text-orange-600 border-orange-200/80'
        case 'Tier 5':
            return 'bg-rose-50 text-rose-600 border-rose-200/80'
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200/80'
    }
}

const getTierRateTextClass = (tier) => {
    switch (tier) {
        case 'Tier 1':
            return 'text-blue-600'
        case 'Tier 2':
            return 'text-indigo-600'
        case 'Tier 3':
            return 'text-amber-600'
        case 'Tier 4':
            return 'text-orange-600'
        case 'Tier 5':
            return 'text-rose-600'
        default:
            return 'text-slate-700'
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Pajak Penghasilan (PPh 21) & PTKP - Frans HRIS" />

        <div class="space-y-6">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pajak Penghasilan (PPh 21) & PTKP</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Tabel informatif kategori Penghasilan Tidak Kena Pajak (PTKP) dan dasar acuan pemotongan PPh Pasal 21.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Standar PMK No. 101/PMK.010/2016
                    </span>
                </div>
            </div>

            <!-- 4 Kartu Ringkasan (Stat Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Kartu 1: PTKP Dasar -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PTKP DASAR (WAJIB PAJAK)</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">Rp 54.000.000</div>
                        <div class="text-xs text-slate-400 mt-0.5">Rp 4.500.000 / bulan (TK/0)</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Kartu 2: Tambahan Status Kawin -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TAMBAHAN STATUS KAWIN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">+ Rp 4.500.000</div>
                        <div class="text-xs text-slate-400 mt-0.5">+ Rp 375.000 / bulan (K/0)</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Kartu 3: Tambahan Per Tanggungan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TAMBAHAN PER TANGGUNGAN</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">+ Rp 4.500.000</div>
                        <div class="text-xs text-slate-400 mt-0.5">Maks. 3 orang (+ Rp 375.000 / anak)</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                </div>

                <!-- Kartu 4: Tarif Pajak PPh 21 (Tier 1) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TARIF PAJAK PPH 21 (TIER 1)</div>
                        <div class="text-xl font-bold text-slate-900 mt-1">5%</div>
                        <div class="text-xs text-slate-400 mt-0.5">PKP s/d Rp 60.000.000 / tahun</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tabel 1: Tabel Master Kategori PTKP -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-200/80">
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Tabel Master Kategori PTKP</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Daftar batas Penghasilan Tidak Kena Pajak yang digunakan sebagai pengurang penghasilan bruto karyawan.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5 whitespace-nowrap">KODE STATUS</th>
                                <th class="px-5 py-3.5 whitespace-nowrap">KATEGORI</th>
                                <th class="px-5 py-3.5 whitespace-nowrap">KETERANGAN</th>
                                <th class="px-5 py-3.5 whitespace-nowrap text-right">PTKP / TAHUN</th>
                                <th class="px-5 py-3.5 whitespace-nowrap text-right">PTKP / BULAN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ptkp in ptkpList" :key="ptkp.code" class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold border"
                                        :class="getPtkpBadgeClass(ptkp.code)"
                                    >
                                        {{ ptkp.code }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs font-medium text-slate-800">
                                    {{ ptkp.category }}
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-600">
                                    {{ ptkp.description }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-slate-900 text-sm">
                                    {{ formatCurrency(ptkp.yearly) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right font-semibold text-emerald-600 text-sm">
                                    {{ formatCurrency(ptkp.monthly) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel 2: Tabel Lapisan Tarif Progresif PPh 21 (Pasal 17) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Tabel Lapisan Tarif Progresif PPh 21 (Pasal 17)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Ketentuan lapisan tarif pajak progresif wajib pajak orang pribadi berdasarkan UU No. 7 Tahun 2021 (UU HPP).
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200/80 shrink-0">
                        Pasal 17 Ayat (1) Huruf a UU PPh
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5 whitespace-nowrap">LAPISAN (TIER)</th>
                                <th class="px-5 py-3.5 whitespace-nowrap">RENTANG PENGHASILAN KENA PAJAK (PKP) SETAHUN</th>
                                <th class="px-5 py-3.5 whitespace-nowrap text-center">TARIF PAJAK</th>
                                <th class="px-5 py-3.5 whitespace-nowrap">KETERANGAN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="rate in tierList" :key="rate.tier" class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border"
                                        :class="getTierBadgeClass(rate.tier)"
                                    >
                                        {{ rate.tier }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-medium text-slate-800 text-sm">
                                    {{ rate.range_label }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-center font-bold text-sm" :class="getTierRateTextClass(rate.tier)">
                                    {{ rate.rate_label }}
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-600">
                                    {{ rate.description }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card Edukasi: Bagaimana PPh 21 Karyawan Dihitung? -->
            <div class="bg-blue-50/50 border border-blue-200/70 rounded-2xl p-5 sm:p-6 flex flex-col md:flex-row items-start gap-4">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 w-full">
                    <h3 class="text-sm font-bold text-slate-900">Bagaimana PPh 21 Karyawan Dihitung?</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                        <div class="bg-white/90 p-4 rounded-xl border border-blue-100/80 shadow-2xs">
                            <div class="text-xs font-bold text-slate-800">1. Penghasilan Bersih (Neto)</div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Penghasilan Bruto dikurangi Biaya Jabatan (5%, maks. Rp 500.000/bln) dan iuran BPJS yang menjadi tanggungan karyawan.
                            </p>
                        </div>
                        <div class="bg-white/90 p-4 rounded-xl border border-blue-100/80 shadow-2xs">
                            <div class="text-xs font-bold text-slate-800">2. Penghasilan Kena Pajak (PKP)</div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Penghasilan Bersih Setahun dikurangi dengan nilai PTKP sesuai tabel di atas. Jika hasilnya minus/nol, maka bebas pajak (PPh 21 = Rp 0).
                            </p>
                        </div>
                        <div class="bg-white/90 p-4 rounded-xl border border-blue-100/80 shadow-2xs">
                            <div class="text-xs font-bold text-slate-800">3. Pemotongan PPh 21 Bulanan</div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                PKP Setahun dikalikan tarif pajak (5% untuk layer pertama), kemudian dibagi 12 bulan untuk dipotong pada slip gaji karyawan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
