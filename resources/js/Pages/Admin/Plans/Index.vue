<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    plans: Array,
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <Head title="Paket Langganan SaaS" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Paket & Tier Langganan SaaS</h1>
                    <p class="text-sm text-slate-500 mt-1">Konfigurasi batasan kuota karyawan, fitur platform, dan skema tarif berlangganan.</p>
                </div>
            </div>

            <!-- Plans Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        plan.is_popular
                            ? 'border-orange-500 ring-2 ring-orange-500/20 shadow-md'
                            : 'border-slate-200/80 shadow-xs',
                        'bg-white rounded-2xl border p-6 flex flex-col justify-between relative transition-all duration-200'
                    ]"
                >
                    <!-- Popular Tag -->
                    <div
                        v-if="plan.is_popular"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-orange-600 text-white text-[11px] font-bold rounded-full uppercase tracking-wider shadow-sm"
                    >
                        {{ plan.badge }}
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ plan.badge }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                Kuota: {{ plan.max_employees }}
                            </span>
                        </div>

                        <h3 class="text-xl font-bold text-slate-900 mt-2">{{ plan.name }}</h3>

                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-900">{{ formatRupiah(plan.price) }}</span>
                            <span class="text-xs text-slate-400">/ {{ plan.period }}</span>
                        </div>

                        <!-- Feature Checklist -->
                        <div class="mt-6 space-y-3 pt-6 border-t border-slate-100">
                            <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Fitur Termasuk:</p>
                            <ul class="space-y-2.5">
                                <li
                                    v-for="(feat, idx) in plan.features"
                                    :key="idx"
                                    class="flex items-start gap-2.5 text-xs text-slate-600"
                                >
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold transition-all text-center"
                            :class="[
                                plan.is_popular
                                    ? 'bg-orange-600 hover:bg-orange-700 text-white shadow-sm'
                                    : 'bg-slate-100 hover:bg-slate-200 text-slate-800'
                            ]"
                        >
                            Konfigurasi Fitur Tier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
