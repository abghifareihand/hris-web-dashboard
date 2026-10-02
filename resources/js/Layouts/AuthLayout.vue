<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Masuk ke Akun Anda',
    },
    subtitle: {
        type: String,
        default: 'Kelola sumber daya manusia dengan mudah, cepat, dan akurat.',
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || 'Frans HRIS');
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 antialiased">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center gap-3">
                <img src="/images/logo/logo.png" alt="Frans HRIS" class="w-12 h-12 object-contain rounded-xl shadow-xs" />
                <span class="text-2xl font-bold text-slate-800 tracking-tight">
                    {{ appName }}
                </span>
            </Link>
            <h2 class="mt-6 text-xl font-bold text-slate-800">
                {{ title }}
            </h2>
            <p class="mt-1.5 text-xs text-slate-500">
                {{ subtitle }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-2xl border border-slate-100">
                <slot />
            </div>
            <div class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. Hak Cipta Dilindungi.
            </div>
        </div>
    </div>
</template>
