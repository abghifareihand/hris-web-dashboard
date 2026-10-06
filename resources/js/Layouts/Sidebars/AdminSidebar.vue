<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    sidebarOpen: Boolean,
    desktopSidebarOpen: {
        type: Boolean,
        default: true,
    },
    authUser: Object,
    appName: {
        type: String,
        default: 'Frans HRIS',
    },
});

defineEmits(['close']);
</script>

<template>
    <!-- Sidebar - Super Admin Warm Orange & Amber Theme -->
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            desktopSidebarOpen ? 'lg:translate-x-0 lg:w-64 lg:static' : 'lg:-translate-x-full lg:w-0 lg:absolute lg:border-r-0',
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-amber-50/50 via-white to-orange-50/40 border-r border-orange-200/80 transform transition-all duration-300 ease-in-out flex flex-col shadow-lg shadow-orange-900/5 overflow-hidden"
    >
        <!-- Logo Brand -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-orange-100 shrink-0 bg-white/60 backdrop-blur-xs">
            <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-500 to-amber-500 p-0.5 shadow-sm shadow-orange-500/20 shrink-0 flex items-center justify-center">
                    <img src="/images/logo/logo.png" alt="Logo" class="w-full h-full object-contain rounded-[10px] bg-white p-0.5" />
                </div>
                <div class="min-w-0">
                    <span class="block text-base font-bold bg-gradient-to-r from-orange-600 via-amber-600 to-amber-700 bg-clip-text text-transparent truncate tracking-tight">
                        {{ appName }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-800 bg-orange-100/90 px-1.5 py-0.5 rounded-md border border-orange-200 uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                        Super Admin
                    </span>
                </div>
            </Link>

            <button @click="$emit('close')" class="lg:hidden text-slate-400 hover:text-orange-600 cursor-pointer p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="sidebar-nav flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <!-- Section Label -->
            <div class="!mt-1 !mb-2 px-3">
                <span class="text-[11px] font-bold text-orange-800/80 uppercase tracking-widest">Platform Master</span>
            </div>

            <!-- Dashboard -->
            <Link
                :href="route('admin.dashboard')"
                class="sidebar-link-admin"
                :class="{ 'active': route().current('admin.dashboard') }"
            >
                <svg class="sidebar-icon-admin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard Master</span>
            </Link>

            <!-- Section Label: MANAJEMEN SAAS -->
            <div class="!mt-5 !mb-2 px-3">
                <span class="text-[11px] font-bold text-orange-800/80 uppercase tracking-widest">Kelola SaaS</span>
            </div>

            <!-- Perusahaan (Tenants) -->
            <Link
                :href="route('admin.companies.index')"
                class="sidebar-link-admin"
                :class="{ 'active': route().current('admin.companies.*') }"
            >
                <svg class="sidebar-icon-admin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Perusahaan Tenant</span>
            </Link>

            <!-- Paket Langganan -->
            <Link
                :href="route('admin.plans.index')"
                class="sidebar-link-admin"
                :class="{ 'active': route().current('admin.plans.*') }"
            >
                <svg class="sidebar-icon-admin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Paket Langganan</span>
            </Link>

            <!-- Transaksi & Tagihan -->
            <Link
                :href="route('admin.transactions.index')"
                class="sidebar-link-admin"
                :class="{ 'active': route().current('admin.transactions.*') }"
            >
                <svg class="sidebar-icon-admin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Transaksi & Tagihan</span>
            </Link>

            <!-- Section Label: SISTEM -->
            <div class="!mt-5 !mb-2 px-3">
                <span class="text-[11px] font-bold text-orange-800/80 uppercase tracking-widest">Konfigurasi</span>
            </div>

            <!-- Pengaturan Platform -->
            <Link
                :href="route('admin.settings.index')"
                class="sidebar-link-admin"
                :class="{ 'active': route().current('admin.settings.*') }"
            >
                <svg class="sidebar-icon-admin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                </svg>
                <span>Pengaturan Platform</span>
            </Link>
        </nav>

        <!-- Sidebar Bottom User Section -->
        <div class="p-3 border-t border-orange-100 bg-white/70 shrink-0">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-orange-50/70 border border-orange-200/60 shadow-2xs">
                <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold overflow-hidden shadow-xs shrink-0"
                    :class="authUser?.avatar ? 'bg-white border border-orange-200' : 'bg-gradient-to-tr from-orange-600 to-amber-500 text-white'"
                >
                    <img
                        v-if="authUser?.avatar"
                        :src="'/storage/' + authUser.avatar"
                        :alt="authUser.name"
                        class="w-full h-full object-cover"
                    />
                    <span v-else>{{ authUser?.name?.charAt(0).toUpperCase() || 'A' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ authUser?.name }}</p>
                    <p class="text-[11px] font-medium text-orange-700 truncate">Super Administrator</p>
                </div>
            </div>
        </div>
    </aside>
</template>
