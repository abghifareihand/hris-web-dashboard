<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    pagination: {
        type: Object,
        default: null,
    },
    links: {
        type: Array,
        default: null,
    },
    wrapperClass: {
        type: String,
        default: '',
    },
});

const resolvedLinks = computed(() => props.links || props.pagination?.links || []);
const hasPagination = computed(() => {
    return (props.pagination && props.pagination.total > 0) || resolvedLinks.value.length > 3;
});

const isPrev = (link, index) => {
    return index === 0 || String(link.label).toLowerCase().includes('prev') || String(link.label).includes('&laquo;') || String(link.label).includes('«');
};

const isNext = (link, index) => {
    return index === resolvedLinks.value.length - 1 || String(link.label).toLowerCase().includes('next') || String(link.label).includes('&raquo;') || String(link.label).includes('»');
};
</script>

<template>
    <div
        v-if="hasPagination"
        :class="[
            'flex flex-col sm:flex-row items-center justify-between gap-3 w-full text-sm pt-2 pb-1 px-1 -mt-3',
            wrapperClass,
        ]"
    >
        <!-- Left: Record Range Info (if pagination object provided) -->
        <div class="flex items-center gap-3">
            <p v-if="pagination && (pagination.from || pagination.total)" class="text-xs text-slate-500">
                <span class="font-semibold text-slate-700">{{ pagination.from || 0 }}-{{ pagination.to || 0 }}</span>
                dari
                <span class="font-semibold text-slate-700">{{ pagination.total || 0 }}</span>
                data
            </p>
        </div>

        <!-- Right: Connected Segment Pagination Buttons -->
        <div
            v-if="resolvedLinks.length > 3"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden text-sm"
        >
            <template v-for="(link, index) in resolvedLinks" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :title="isPrev(link, index) ? 'Halaman Sebelumnya' : isNext(link, index) ? 'Halaman Berikutnya' : `Halaman ${link.label}`"
                    :class="[
                        'h-9 flex items-center justify-center border-r border-slate-200 last:border-r-0 transition-colors text-xs select-none',
                        isPrev(link, index) || isNext(link, index) ? 'w-9 px-0' : 'min-w-[36px] px-3',
                        link.active
                            ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 font-bold'
                            : 'text-slate-600 bg-white hover:bg-slate-50 font-medium',
                    ]"
                >
                    <!-- Previous Icon -->
                    <svg
                        v-if="isPrev(link, index)"
                        class="w-4 h-4 text-slate-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>

                    <!-- Next Icon -->
                    <svg
                        v-else-if="isNext(link, index)"
                        class="w-4 h-4 text-slate-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>

                    <!-- Page Number / Ellipsis -->
                    <span v-else v-html="link.label" />
                </Link>

                <span
                    v-else
                    :class="[
                        'h-9 flex items-center justify-center border-r border-slate-200 last:border-r-0 text-xs font-medium cursor-not-allowed select-none text-slate-300 bg-slate-50/70',
                        isPrev(link, index) || isNext(link, index) ? 'w-9 px-0' : 'min-w-[36px] px-3',
                    ]"
                >
                    <!-- Disabled Previous Icon -->
                    <svg
                        v-if="isPrev(link, index)"
                        class="w-4 h-4 text-slate-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>

                    <!-- Disabled Next Icon -->
                    <svg
                        v-else-if="isNext(link, index)"
                        class="w-4 h-4 text-slate-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>

                    <!-- Disabled Number / Ellipsis -->
                    <span v-else v-html="link.label" />
                </span>
            </template>
        </div>
    </div>
</template>
