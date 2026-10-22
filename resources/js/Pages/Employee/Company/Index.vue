<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import TableEmpty from '@/Components/Table/TableEmpty.vue';
import Badge from '@/Components/UI/Badge.vue';
import Input from '@/Components/UI/Input.vue';

const props = defineProps({
    company: Object,
    branch: Object,
    colleagues: Array,
});

const searchQuery = ref('');

const filteredColleagues = computed(() => {
    if (!props.colleagues) return [];
    if (!searchQuery.value.trim()) return props.colleagues;
    const q = searchQuery.value.toLowerCase();
    return props.colleagues.filter((c) =>
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.nip && c.nip.toLowerCase().includes(q)) ||
        (c.division?.name && c.division.name.toLowerCase().includes(q)) ||
        (c.position?.name && c.position.name.toLowerCase().includes(q))
    );
});

const tableHeaders = [
    { label: 'Nama Rekan Kerja', class: 'w-64' },
    { label: 'NIP', class: 'w-36' },
    { label: 'Divisi', class: 'w-44' },
    { label: 'Posisi / Jabatan', class: 'w-48' },
    { label: 'Penempatan Cabang', class: 'w-48' },
    { label: 'Status', class: 'w-24 text-center' },
];
</script>

<template>
    <Head title="Informasi Perusahaan & Rekan Kerja" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Perusahaan & Direktori Rekan</h1>
                <p class="text-sm text-slate-500 mt-1">Profil perusahaan tempat Anda bernaung dan direktori kontak rekan satu kantor.</p>
            </div>

            <!-- Top Grid: Company Info & Branch Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Company Info Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl border border-emerald-100 shrink-0">
                            {{ company?.name?.charAt(0) || 'C' }}
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Perusahaan</span>
                            <h2 class="text-lg font-bold text-slate-900">{{ company?.name || 'Frans HRIS Corp' }}</h2>
                        </div>
                    </div>

                    <div class="space-y-2.5 pt-3 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ company?.address || 'Kantor Pusat' }}</span>
                        </div>
                        <div v-if="company?.email" class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ company.email }}</span>
                        </div>
                        <div v-if="company?.phone_number" class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>{{ company.phone_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Branch Info Card -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl border border-blue-100 shrink-0">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Cabang Penempatan Anda</span>
                            <h2 class="text-lg font-bold text-slate-900">{{ branch?.name || 'Kantor Cabang Utama' }}</h2>
                        </div>
                    </div>

                    <div class="space-y-2.5 pt-3 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ branch?.address || 'Alamat cabang belum disetel' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Radius Absensi Geofence: <strong>{{ branch?.radius || 100 }} Meter</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colleague Directory Section -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Direktori Rekan Kerja</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar kolega aktif di seluruh cabang dan departemen perusahaan</p>
                    </div>
                    <div class="w-full sm:w-72">
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari nama, NIP, divisi..."
                        >
                            <template #prefix>
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </template>
                        </Input>
                    </div>
                </div>

                <DataTable :headers="tableHeaders">
                    <tr v-if="filteredColleagues.length === 0">
                        <td colspan="6">
                            <TableEmpty
                                title="Tidak ada rekan kerja ditemukan"
                                description="Tidak ada data rekan kerja yang cocok dengan kata kunci pencarian Anda."
                            />
                        </td>
                    </tr>
                    <tr
                        v-for="c in filteredColleagues"
                        :key="c.id"
                        class="hover:bg-slate-50/60 transition-colors"
                    >
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                    {{ c.name?.charAt(0) || 'U' }}
                                </div>
                                <span class="font-semibold text-slate-900 text-sm">{{ c.name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs font-mono text-slate-500">
                            {{ c.nip || '-' }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-700">
                            {{ c.division?.name || '-' }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-700">
                            {{ c.position?.name || '-' }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500">
                            {{ c.branch?.name || '-' }}
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <Badge variant="success">
                                Aktif
                            </Badge>
                        </td>
                    </tr>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
