<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

const props = defineProps({
    user: Object,
    employee: Object,
});

const activeTab = ref('personal'); // 'personal', 'bank', 'password'

// Form Kontak & Bank
const contactForm = useForm({
    phone_number: props.employee?.phone_number || '',
    address: props.employee?.address || '',
    bank_name: props.employee?.bank_name || '',
    bank_account_number: props.employee?.bank_account_number || '',
    bank_account_holder: props.employee?.bank_account_holder || '',
});

// Form Ubah Password
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitContactForm = () => {
    contactForm.post(route('employee.profile.update'), {
        preserveScroll: true,
    });
};

const submitPasswordForm = () => {
    passwordForm.put(route('employee.profile.change-password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Profil Karyawan" />

    <EmployeeLayout>
        <div class="space-y-6">
            <!-- Profile Hero Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-sky-600 text-white flex items-center justify-center font-bold text-2xl shadow-md shrink-0">
                        {{ employee?.name?.charAt(0) || user?.name?.charAt(0) || 'E' }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ employee?.name || user?.name }}</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                Aktif
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-2">
                            <span>NIP: {{ employee?.nip || '-' }}</span>
                            <span>&bull;</span>
                            <span>{{ employee?.position?.name || 'Staff' }}</span>
                            <span>&bull;</span>
                            <span>{{ employee?.division?.name || 'Divisi Umum' }}</span>
                        </p>
                        <p class="text-xs text-sky-600 font-medium mt-1">
                            {{ employee?.company?.name || 'Frans HRIS Company' }} &mdash; Cabang: {{ employee?.branch?.name || 'Pusat' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs Navigation -->
            <div class="flex items-center border-b border-slate-200">
                <nav class="flex gap-6 -mb-px">
                    <button
                        type="button"
                        @click="activeTab = 'personal'"
                        :class="[
                            activeTab === 'personal'
                                ? 'border-sky-600 text-sky-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'pb-3 text-sm font-semibold border-b-2 transition-colors'
                        ]"
                    >
                        Informasi Kepegawaian
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'bank'"
                        :class="[
                            activeTab === 'bank'
                                ? 'border-sky-600 text-sky-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'pb-3 text-sm font-semibold border-b-2 transition-colors'
                        ]"
                    >
                        Kontak & Rekening Bank
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'password'"
                        :class="[
                            activeTab === 'password'
                                ? 'border-sky-600 text-sky-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'pb-3 text-sm font-semibold border-b-2 transition-colors'
                        ]"
                    >
                        Keamanan & Sandi
                    </button>
                </nav>
            </div>

            <!-- TAB 1: DATA KEPEGAWAIAN (READ-ONLY) -->
            <div v-if="activeTab === 'personal'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Data Pokok Kepegawaian</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Informasi resmi kontrak dan penempatan kerja Anda dari HRD</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Nomor Induk Pegawai (NIP)</span>
                        <p class="text-sm font-bold text-slate-800 mt-1">{{ employee?.nip || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Nama Lengkap</span>
                        <p class="text-sm font-bold text-slate-800 mt-1">{{ employee?.name || user?.name }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Email Terdaftar</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ user?.email }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Perusahaan</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ employee?.company?.name || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Cabang Penempatan</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ employee?.branch?.name || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Divisi / Departemen</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ employee?.division?.name || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Jabatan / Posisi</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ employee?.position?.name || '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Tanggal Bergabung</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ formatDate(employee?.join_date) }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Status Pekerjaan</span>
                        <p class="text-sm font-medium text-slate-800 mt-1">{{ employee?.status || 'Tetap' }}</p>
                    </div>
                </div>
            </div>

            <!-- TAB 2: KONTAK & REKENING BANK (EDITABLE) -->
            <div v-if="activeTab === 'bank'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Kontak Pribadi & Rekening Bank</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Perbarui nomor telepon, domisili, dan nomor rekening untuk keperluan transfer payroll</p>
                </div>

                <form @submit.prevent="submitContactForm" class="space-y-5 max-w-2xl">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP
                        </label>
                        <input
                            type="text"
                            v-model="contactForm.phone_number"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            placeholder="Contoh: 081234567890"
                        />
                        <p v-if="contactForm.errors.phone_number" class="text-xs text-rose-500 mt-1">
                            {{ contactForm.errors.phone_number }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Tempat Tinggal
                        </label>
                        <textarea
                            v-model="contactForm.address"
                            rows="2"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors placeholder:text-slate-400"
                            placeholder="Alamat domisili saat ini..."
                        ></textarea>
                        <p v-if="contactForm.errors.address" class="text-xs text-rose-500 mt-1">
                            {{ contactForm.errors.address }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 mb-3">Informasi Rekening Bank Payroll</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Bank
                                </label>
                                <input
                                    type="text"
                                    v-model="contactForm.bank_name"
                                    class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                                    placeholder="Contoh: BCA, Mandiri, BRI"
                                />
                                <p v-if="contactForm.errors.bank_name" class="text-xs text-rose-500 mt-1">
                                    {{ contactForm.errors.bank_name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor Rekening
                                </label>
                                <input
                                    type="text"
                                    v-model="contactForm.bank_account_number"
                                    class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                                    placeholder="Contoh: 1234567890"
                                />
                                <p v-if="contactForm.errors.bank_account_number" class="text-xs text-rose-500 mt-1">
                                    {{ contactForm.errors.bank_account_number }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Pemilik Rekening (Sesuai Buku Tabungan)
                            </label>
                            <input
                                type="text"
                                v-model="contactForm.bank_account_holder"
                                class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                                placeholder="Nama lengkap pemilik rekening"
                            />
                            <p v-if="contactForm.errors.bank_account_holder" class="text-xs text-rose-500 mt-1">
                                {{ contactForm.errors.bank_account_holder }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="contactForm.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50"
                        >
                            <span v-if="contactForm.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 3: GANTI PASSWORD -->
            <div v-if="activeTab === 'password'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Ubah Kata Sandi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pastikan kata sandi Anda kuat untuk melindungi akses akun portal Anda</p>
                </div>

                <form @submit.prevent="submitPasswordForm" class="space-y-4 max-w-md">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.current_password"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-500 mt-1">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                        <p v-if="passwordForm.errors.password" class="text-xs text-rose-500 mt-1">
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            class="w-full text-sm rounded-xl border-slate-200 focus:border-sky-500 focus:ring-sky-500/20 transition-colors"
                            required
                        />
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all disabled:opacity-50"
                        >
                            <span v-if="passwordForm.processing">Mengubah Sandi...</span>
                            <span v-else>Perbarui Kata Sandi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </EmployeeLayout>
</template>
