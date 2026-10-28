<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    company: {
        type: Object,
        default: null,
    },
});

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
    <Head title="Profil Saya" />

    <div class="w-full space-y-6">
        <!-- Profile Hero Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl sm:text-3xl shadow-sm shrink-0 border border-slate-200">
                    <img
                        v-if="user?.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="w-full h-full object-cover"
                    />
                    <span v-else class="uppercase">
                        {{ user?.name?.charAt(0) || 'O' }}
                    </span>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ user?.name }}</h1>
                        <Badge variant="success" size="sm">
                            Owner / HRD
                        </Badge>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-2">
                        <span>{{ user?.email }}</span>
                        <span>&bull;</span>
                        <span class="text-emerald-700 font-medium">{{ company?.name || 'Perusahaan' }}</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        Terdaftar sejak {{ formatDate(user?.created_at) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Detail Cards Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card 1: Informasi Akun Pemilik -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Informasi Akun Pemilik</h2>
                            <p class="text-xs text-slate-500">Data akun yang digunakan untuk mengelola sistem HRIS</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-slate-500 block">Nama Lengkap</span>
                            <span class="text-sm font-semibold text-slate-800">{{ user?.name || '-' }}</span>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Alamat Email</span>
                            <span class="text-sm font-semibold text-slate-800">{{ user?.email || '-' }}</span>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Nomor Telepon / WhatsApp</span>
                            <span class="text-sm font-semibold text-slate-800">{{ user?.phone || '-' }}</span>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Jenis Kelamin</span>
                            <span class="text-sm font-semibold text-slate-800">
                                {{ user?.gender === 'male' ? 'Laki-laki' : (user?.gender === 'female' ? 'Perempuan' : '-') }}
                            </span>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Tanggal Lahir</span>
                            <span class="text-sm font-semibold text-slate-800">{{ formatDate(user?.birth_date) }}</span>
                        </div>

                        <div>
                            <span class="text-xs text-slate-500 block">Hak Akses / Peran</span>
                            <div class="mt-1">
                                <Badge variant="primary" size="sm">
                                    Administrator Utama (Owner)
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 block">Alamat Tempat Tinggal (Domisili)</span>
                        <span class="text-sm font-semibold text-slate-800">{{ user?.address || '-' }}</span>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-xs text-slate-500 block">Akun Dibuat</span>
                        <span class="text-sm font-semibold text-slate-800">{{ formatDate(user?.created_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Informasi Perusahaan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Perusahaan Terafiliasi</h2>
                            <p class="text-xs text-slate-500">Perusahaan yang terhubung dengan akun owner Anda</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="text-xs text-slate-500 block">Nama Perusahaan</span>
                        <span class="text-sm font-semibold text-slate-800">{{ company?.name || '-' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 block">Email Perusahaan</span>
                        <span class="text-sm font-semibold text-slate-800">{{ company?.email || '-' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 block">Nomor Telepon</span>
                        <span class="text-sm font-semibold text-slate-800">{{ company?.phone || '-' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 block">Alamat Kantor</span>
                        <span class="text-sm font-semibold text-slate-800">{{ company?.address || '-' }}</span>
                    </div>

                    <div class="pt-2">
                        <Link :href="route('owner.management.company.profile.index')" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1">
                            <span>Kelola profil perusahaan selengkapnya</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
