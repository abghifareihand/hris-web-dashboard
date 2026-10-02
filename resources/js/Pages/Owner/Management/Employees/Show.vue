<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Badge from "@/Components/UI/Badge.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const fixedIncomeTotal = computed(() => {
    return (Number(props.employee.basic_salary) || 0) + (Number(props.employee.fixed_allowance) || 0);
});

const perMinuteLateCut = computed(() => {
    const nominal = Number(props.employee.late_penalty_nominal || 0);
    return Math.round(nominal / 60);
});

const perDayAlphaCut = computed(() => {
    if (props.employee.alpha_penalty_type === "prorate") {
        const salary = Number(props.employee.basic_salary || 0);
        return Math.round(salary / 30);
    }
    return Number(props.employee.alpha_penalty_nominal || 0);
});
</script>

<template>
    <Head :title="`Detail Karyawan - ${employee.name}`" />

    <div class="w-full space-y-6">
        <!-- Top Profile Banner -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Avatar with soft ring -->
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-2xl flex items-center justify-center shrink-0 shadow-xs ring-4 ring-emerald-50">
                    {{ employee.name?.charAt(0).toUpperCase() }}
                </div>
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {{ employee.name }}
                        </h1>
                        <Badge :variant="employee.is_active ? 'success' : 'danger'" size="sm">
                            {{ employee.is_active ? 'Karyawan Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>

                    <!-- Metadata Pills -->
                    <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                        <span v-if="employee.nip" class="font-mono bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md font-medium">
                            NIP: {{ employee.nip }}
                        </span>
                        <span>{{ employee.email }}</span>
                        <span class="text-slate-300">&bull;</span>
                        <span>{{ employee.phone || 'No. Telp belum diatur' }}</span>
                        <span v-if="employee.position" class="text-slate-300">&bull;</span>
                        <span v-if="employee.position" class="font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100/80">
                            {{ employee.position.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2.5 shrink-0">
                <Link :href="route('owner.management.employees.index')">
                    <Button variant="secondary">Kembali</Button>
                </Link>
                <Link :href="route('owner.management.employees.edit', employee.id)">
                    <Button variant="primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Karyawan
                    </Button>
                </Link>
            </div>
        </div>

        <!-- 2 Column Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Kolom Kiri: Personal, Job, Bank (lg:col-span-2) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Data Pribadi -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">
                            Informasi Pribadi
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Nama Lengkap</div>
                                <div class="text-xs sm:text-sm font-semibold text-slate-900 mt-0.5">
                                    {{ employee.name }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">NIK (KTP)</div>
                                <div class="text-xs sm:text-sm font-mono font-medium text-slate-900 mt-0.5">
                                    {{ employee.nik || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Alamat Email</div>
                                <div class="text-xs sm:text-sm font-medium text-slate-900 mt-0.5 break-all">
                                    {{ employee.email }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">No. Telepon / WhatsApp</div>
                                <div class="text-xs sm:text-sm font-medium text-slate-900 mt-0.5">
                                    {{ employee.phone || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Jenis Kelamin</div>
                                <div class="text-xs sm:text-sm font-medium text-slate-900 mt-0.5">
                                    {{ employee.gender === 'male' ? 'Laki-laki' : (employee.gender === 'female' ? 'Perempuan' : '-') }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Tanggal Lahir</div>
                                <div class="text-xs sm:text-sm font-medium text-slate-900 mt-0.5">
                                    {{ formatDate(employee.birth_date) }}
                                </div>
                            </div>

                            <div class="sm:col-span-2 p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Alamat Tempat Tinggal</div>
                                <div class="text-xs sm:text-sm text-slate-800 mt-0.5 leading-relaxed">
                                    {{ employee.address || 'Belum ada data alamat' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Informasi Pekerjaan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">
                            Informasi Pekerjaan
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">NIP (Nomor Induk Pegawai)</div>
                                <div class="text-xs sm:text-sm font-mono font-bold text-slate-900 mt-0.5">
                                    {{ employee.nip || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Tanggal Bergabung</div>
                                <div class="text-xs sm:text-sm font-medium text-slate-900 mt-0.5">
                                    {{ formatDate(employee.joined_at) }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Cabang Penempatan</div>
                                <div class="text-xs sm:text-sm font-semibold text-slate-900 mt-0.5">
                                    {{ employee.branch?.name || 'Semua Cabang' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Divisi Kerja</div>
                                <div class="text-xs sm:text-sm font-semibold text-slate-900 mt-0.5">
                                    {{ employee.division?.name || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Jabatan Struktural</div>
                                <div class="text-xs sm:text-sm font-semibold text-slate-900 mt-0.5">
                                    {{ employee.position?.name || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Status Akun Login</div>
                                <div class="mt-1">
                                    <span v-if="employee.user" class="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                        Terkoneksi User
                                    </span>
                                    <span v-else class="text-xs text-slate-400 italic">
                                        Belum terhubung akun
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Rekening Bank Payroll -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">
                            Rekening Bank Payroll
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Nama Bank</div>
                                <div class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5">
                                    {{ employee.bank_name || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Nomor Rekening</div>
                                <div class="text-xs sm:text-sm font-mono font-bold text-slate-900 mt-0.5">
                                    {{ employee.bank_account_number || '-' }}
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50/60 rounded-xl border border-slate-100">
                                <div class="text-[11px] font-medium text-slate-500">Atas Nama Rekening</div>
                                <div class="text-xs sm:text-sm font-semibold text-slate-900 mt-0.5">
                                    {{ employee.bank_account_name || '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Payroll, Penalties, Tax/BPJS (lg:col-span-1) -->
            <div class="space-y-6">
                <!-- 1. Gaji & Tunjangan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900">
                            Gaji & Tunjangan
                        </h2>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100/80">
                            {{ employee.payroll_cycle === 'weekly' ? 'Mingguan' : 'Bulanan' }}
                        </span>
                    </div>

                    <div class="p-6">
                        <div class="space-y-2.5 text-xs sm:text-sm">
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Gaji Pokok</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(employee.basic_salary) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Tunjangan Tetap</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(employee.fixed_allowance) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Tunjangan Harian</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(employee.daily_allowance) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Tunjangan Lainnya</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ formatCurrency(employee.other_allowance) }}</span>
                            </div>

                            <!-- Highlight Subtotal Pendapatan Tetap -->
                            <div class="p-3 bg-emerald-50/50 border border-emerald-200/60 rounded-xl flex justify-between items-center text-emerald-950 font-bold mt-2">
                                <div class="text-xs">
                                    <div>Subtotal Tetap</div>
                                    <div class="text-[10px] font-normal text-emerald-700">(Gaji Pokok + Tunj. Tetap)</div>
                                </div>
                                <span class="font-mono text-sm sm:text-base text-emerald-900">{{ formatCurrency(fixedIncomeTotal) }}</span>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex justify-between items-center text-xs text-slate-500">
                                <span>Upah Lembur per Jam</span>
                                <span class="font-mono font-semibold text-slate-800">{{ formatCurrency(employee.overtime_rate_per_hour) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Ketentuan Potongan Absensi -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">
                            Aturan Potongan Absensi
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-3">
                            <!-- Denda Keterlambatan -->
                            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="13" r="8" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4l2.5 2.5M10 2h4M12 2v3" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900">Denda Keterlambatan</div>
                                </div>

                                <div class="flex items-center justify-between text-xs pt-1">
                                    <span class="text-slate-500">
                                        {{ employee.late_penalty_type === 'flat' ? 'By Nominal (Flat)' : 'Hitung per Jam (Prorate)' }}
                                    </span>
                                    <span class="font-mono font-bold text-slate-900">
                                        {{ formatCurrency(employee.late_penalty_nominal) }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200/60">
                                    <template v-if="employee.late_penalty_type === 'flat'">
                                        Dikenakan sekali flat per shift kerja yang terlambat.
                                    </template>
                                    <template v-else>
                                        Potongan <span class="font-semibold text-slate-800">Rp {{ new Intl.NumberFormat("id-ID").format(perMinuteLateCut) }}</span> per menit keterlambatan.
                                    </template>
                                </div>
                            </div>

                            <!-- Denda Alpha -->
                            <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <line x1="17" y1="8" x2="22" y2="13" stroke-linecap="round" />
                                            <line x1="22" y1="8" x2="17" y2="13" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900">Denda Alpha (Mangkir)</div>
                                </div>

                                <div class="flex items-center justify-between text-xs pt-1">
                                    <span class="text-slate-500">
                                        {{ employee.alpha_penalty_type === 'flat' ? 'By Nominal (Flat)' : 'Prorate Gaji Pokok' }}
                                    </span>
                                    <span class="font-mono font-bold text-slate-900">
                                        {{ employee.alpha_penalty_type === 'flat' ? formatCurrency(employee.alpha_penalty_nominal) : 'Gaji / 30 hari' }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200/60">
                                    Potongan <span class="font-semibold text-slate-800">Rp {{ new Intl.NumberFormat("id-ID").format(perDayAlphaCut) }}</span> per 1 hari tidak masuk kerja.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Pajak PPh 21 & BPJS -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                        <h2 class="text-base font-bold text-slate-900">
                            Pajak PPh 21 & BPJS
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-3 text-xs sm:text-sm">
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Status PPh 21</span>
                                <Badge :variant="employee.taxable ? 'success' : 'neutral'" size="sm">
                                    {{ employee.taxable ? 'Wajib Pajak' : 'Non-Pajak' }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Nomor NPWP</span>
                                <span class="font-mono text-xs font-medium text-slate-900">{{ employee.npwp || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">Status PTKP</span>
                                <span class="font-semibold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded">{{ employee.ptkp_status || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">BPJS Kesehatan</span>
                                <span class="font-mono text-xs font-medium text-slate-900">{{ employee.bpjs_kesehatan_no || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-slate-500 text-xs">BPJS Ketenagakerjaan</span>
                                <span class="font-mono text-xs font-medium text-slate-900">{{ employee.jht_no || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
