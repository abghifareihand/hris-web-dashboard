<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    user: Object,
    employee: Object,
    initialTab: {
        type: String,
        default: 'personal',
    },
});

const validTabs = ['personal', 'salary', 'tax'];

const getActiveTab = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab && validTabs.includes(tab)) return tab;
    }
    return props.initialTab && validTabs.includes(props.initialTab) ? props.initialTab : 'personal';
};

const activeTab = ref(getActiveTab());

const setTab = (tab) => {
    activeTab.value = tab;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const totalRegularSalary = computed(() => {
    const basic = Number(props.employee?.basic_salary || 0);
    const fixed = Number(props.employee?.fixed_allowance || 0);
    return basic + fixed;
});
</script>

<template>
    <Head title="Profil Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Profile Hero Card with Edit Button -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl shadow-sm shrink-0 border border-slate-200">
                        <img
                            v-if="user?.avatar"
                            :src="user.avatar.startsWith('http') ? user.avatar : '/storage/' + user.avatar"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                        />
                        <span v-else>
                            {{ employee?.name?.charAt(0) || user?.name?.charAt(0) || 'E' }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ employee?.name || user?.name }}</h1>
                            <Badge variant="success" size="sm">
                                {{ employee?.is_active ? 'Aktif' : 'Non-Aktif' }}
                            </Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-2">
                            <span>NIP: {{ employee?.nip || '-' }}</span>
                            <span>&bull;</span>
                            <span>{{ employee?.position?.name || 'Staff' }}</span>
                            <span>&bull;</span>
                            <span>{{ employee?.division?.name || 'Divisi Umum' }}</span>
                        </p>
                        <p class="text-xs text-emerald-700 font-medium mt-1">
                            {{ employee?.company?.name_company || employee?.company?.name || 'Perusahaan' }} &mdash; Cabang: {{ employee?.branch?.name || 'Kantor Utama' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs Navigation (3 Clean Tabs) -->
            <div class="flex items-center border-b border-slate-200 overflow-x-auto">
                <nav class="flex gap-6 -mb-px min-w-max">
                    <button
                        type="button"
                        @click="setTab('personal')"
                        :class="[
                            activeTab === 'personal'
                                ? 'border-emerald-600 text-emerald-700 font-bold'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                            'pb-3 text-sm border-b-2 transition-colors cursor-pointer'
                        ]"
                    >
                        Informasi Kepegawaian
                    </button>
                    <button
                        type="button"
                        @click="setTab('salary')"
                        :class="[
                            activeTab === 'salary'
                                ? 'border-emerald-600 text-emerald-700 font-bold'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                            'pb-3 text-sm border-b-2 transition-colors cursor-pointer'
                        ]"
                    >
                        Gaji & Tunjangan
                    </button>
                    <button
                        type="button"
                        @click="setTab('tax')"
                        :class="[
                            activeTab === 'tax'
                                ? 'border-emerald-600 text-emerald-700 font-bold'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                            'pb-3 text-sm border-b-2 transition-colors cursor-pointer'
                        ]"
                    >
                        Pajak & BPJS
                    </button>
                </nav>
            </div>

            <!-- TAB 1: INFORMASI KEPEGAWAIAN & PENEMPATAN -->
            <div v-if="activeTab === 'personal'" class="space-y-6">
                <!-- Data Pokok Karyawan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Data Pokok Kepegawaian</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Informasi resmi identitas dan status kontrak kerja Anda dari HRD</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nomor Induk Pegawai (NIP)</span>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ employee?.nip || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nomor KTP (NIK)</span>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ employee?.nik || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nama Lengkap</span>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ employee?.name || user?.name }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Email Akun</span>
                            <p class="text-sm font-medium text-slate-900 mt-1">{{ user?.email }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nomor HP / WhatsApp</span>
                            <p class="text-sm font-semibold text-slate-900 mt-1 font-mono">{{ employee?.phone || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Jenis Kelamin</span>
                            <p class="text-sm font-medium text-slate-900 mt-1">
                                {{ employee?.gender === 'male' ? 'Laki-laki' : (employee?.gender === 'female' ? 'Perempuan' : '-') }}
                            </p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tanggal Lahir</span>
                            <p class="text-sm font-medium text-slate-900 mt-1">{{ formatDate(employee?.birth_date) }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tanggal Bergabung</span>
                            <p class="text-sm font-medium text-slate-900 mt-1">{{ formatDate(employee?.joined_at) }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Status Kepegawaian</span>
                            <p class="text-sm font-semibold text-emerald-700 mt-1">
                                {{ employee?.is_active ? 'Karyawan Aktif' : 'Non-Aktif' }}
                            </p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Siklus Penggajian</span>
                            <p class="text-sm font-medium text-slate-900 mt-1">
                                {{ employee?.payroll_cycle === 'monthly' ? 'Bulanan (Monthly)' : (employee?.payroll_cycle === 'weekly' ? 'Mingguan' : 'Harian') }}
                            </p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 sm:col-span-2">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Alamat Tempat Tinggal (Domisili)</span>
                            <p class="text-sm font-medium text-slate-800 mt-1 leading-relaxed">{{ employee?.address || '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Rekening Bank Payroll -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Rekening Bank Payroll</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Informasi rekening bank resmi Anda untuk penerimaan gaji bulanan dan THR</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nama Bank</span>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ employee?.bank_name || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nomor Rekening</span>
                            <p class="text-sm font-mono font-bold text-slate-900 mt-1">{{ employee?.bank_account_number || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nama Pemilik Rekening</span>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ employee?.bank_account_name || '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Perusahaan & Penempatan Kerja -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Perusahaan & Penempatan Kerja</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Entitas hukum tempat Anda bernaung dan penempatan unit kerja</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 sm:col-span-2">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nama Perusahaan</span>
                            <p class="text-sm font-bold text-slate-900 mt-1">
                                {{ employee?.company?.name_company || employee?.company?.name || '-' }}
                            </p>
                            <p v-if="employee?.company?.address_company || employee?.company?.address" class="text-xs text-slate-500 mt-0.5">
                                {{ employee?.company?.address_company || employee?.company?.address }}
                            </p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Kontak Perusahaan</span>
                            <p class="text-xs font-medium text-slate-800 mt-1">{{ employee?.company?.email_company || employee?.company?.email || '-' }}</p>
                            <p class="text-xs text-slate-500">{{ employee?.company?.phone_company || employee?.company?.phone || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Cabang Penempatan</span>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ employee?.branch?.name || '-' }}</p>
                            <p v-if="employee?.branch?.address" class="text-xs text-slate-500 mt-0.5 truncate">{{ employee?.branch?.address }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Divisi / Departemen</span>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ employee?.division?.name || '-' }}</p>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Jabatan / Posisi</span>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ employee?.position?.name || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: GAJI, TUNJANGAN & KOMPENSASI -->
            <div v-if="activeTab === 'salary'" class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Rincian Paket Gaji & Tunjangan</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Komponen upah resmi yang terdaftar pada sistem payroll perusahaan</p>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/80 text-left sm:text-right">
                            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Gaji Pokok + Tunjangan Tetap</span>
                            <span class="text-lg font-bold text-emerald-700">{{ formatRupiah(totalRegularSalary) }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Gaji Pokok (Basic Salary)</span>
                            <p class="text-base font-bold text-slate-900 mt-1">{{ formatRupiah(employee?.basic_salary) }}</p>
                            <span class="text-[10px] text-slate-400">Komponen upah pokok per bulan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tunjangan Tetap</span>
                            <p class="text-base font-bold text-slate-900 mt-1">{{ formatRupiah(employee?.fixed_allowance) }}</p>
                            <span class="text-[10px] text-slate-400">Tunjangan bulanan rutin</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tunjangan Kehadiran / Harian</span>
                            <p class="text-base font-bold text-slate-900 mt-1">{{ formatRupiah(employee?.daily_allowance) }}</p>
                            <span class="text-[10px] text-slate-400">Dihitung berdasarkan kehadiran kerja</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tunjangan Lainnya</span>
                            <p class="text-base font-bold text-slate-900 mt-1">{{ formatRupiah(employee?.other_allowance) }}</p>
                            <span class="text-[10px] text-slate-400">Tunjangan fungsional/kebijakan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Tarif Upah Lembur / Jam</span>
                            <p class="text-base font-bold text-slate-900 mt-1">{{ formatRupiah(employee?.overtime_rate_per_hour) }}</p>
                            <span class="text-[10px] text-slate-400">Nominal dasar per jam lembur</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Siklus Periode Penggajian</span>
                            <p class="text-base font-bold text-slate-900 mt-1">
                                {{ employee?.payroll_cycle === 'monthly' ? 'Bulanan (Monthly)' : (employee?.payroll_cycle === 'weekly' ? 'Mingguan' : 'Harian') }}
                            </p>
                            <span class="text-[10px] text-slate-400">Jadwal tutup buku payroll</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            Informasi nominal gaji dan tunjangan di atas diatur resmi oleh manajemen HRD. Rincian penerimaan dan potongan bulanan aktual dapat Anda lihat pada menu <strong>Slip Gaji</strong>.
                        </span>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PAJAK & BPJS -->
            <div v-if="activeTab === 'tax'" class="space-y-6">
                <!-- Pajak Penghasilan (PPh 21) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Perpajakan Penghasilan (PPh 21)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Identitas perpajakan yang digunakan sebagai acuan pemotongan pajak penghasilan resmi</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Nomor Pokok Wajib Pajak (NPWP)</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.npwp || 'Belum Terdaftar / Tidak Ada' }}
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Status PTKP (Penghasilan Tidak Kena Pajak)</span>
                            <p class="text-base font-bold text-slate-900 mt-1">
                                {{ employee?.ptkp_status || 'TK/0' }}
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">Status Wajib Pajak</span>
                            <p class="text-base font-bold text-emerald-700 mt-1">
                                {{ employee?.taxable ? 'Wajib Pajak (Kena PPh 21)' : 'Bebas Pajak' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Jaminan Sosial (BPJS) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Jaminan Sosial Ketenagakerjaan & Kesehatan (BPJS)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Nomor kepesertaan jaminan perlindungan ketenagakerjaan dan kesehatan resmi</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">No. BPJS Kesehatan</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.bpjs_kesehatan_no || '-' }}
                            </p>
                            <span class="text-[10px] text-slate-400">Jaminan Layanan Kesehatan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">No. JHT (Jaminan Hari Tua)</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.jht_no || '-' }}
                            </p>
                            <span class="text-[10px] text-slate-400">BPJS Ketenagakerjaan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">No. JP (Jaminan Pensiun)</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.jp_no || '-' }}
                            </p>
                            <span class="text-[10px] text-slate-400">BPJS Ketenagakerjaan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">No. JKK (Kecelakaan Kerja)</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.jkk_no || '-' }}
                            </p>
                            <span class="text-[10px] text-slate-400">BPJS Ketenagakerjaan</span>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold block">No. JKM (Jaminan Kematian)</span>
                            <p class="text-base font-bold text-slate-900 mt-1 font-mono">
                                {{ employee?.jkm_no || '-' }}
                            </p>
                            <span class="text-[10px] text-slate-400">BPJS Ketenagakerjaan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
