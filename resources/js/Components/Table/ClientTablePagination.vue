<script setup>
import { computed } from 'vue'

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1,
    },
    totalItems: {
        type: Number,
        default: 0,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    wrapperClass: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:currentPage', 'pageChange'])

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(props.totalItems / props.perPage))
})

const from = computed(() => {
    if (props.totalItems === 0) return 0
    return (props.currentPage - 1) * props.perPage + 1
})

const to = computed(() => {
    return Math.min(props.totalItems, props.currentPage * props.perPage)
})

const pages = computed(() => {
    const total = totalPages.value
    const current = props.currentPage

    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1)
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 5, '...', total]
    }

    if (current >= total - 3) {
        return [1, '...', total - 4, total - 3, total - 2, total - 1, total]
    }

    return [1, '...', current - 1, current, current + 1, '...', total]
})

const setPage = (page) => {
    if (typeof page !== 'number') return
    if (page < 1 || page > totalPages.value || page === props.currentPage) return
    emit('update:currentPage', page)
    emit('pageChange', page)
}
</script>

<template>
    <div
        v-if="totalItems > 0"
        :class="[
            'flex flex-col sm:flex-row items-center justify-between gap-3 w-full text-sm pt-4 pb-1 px-1',
            wrapperClass,
        ]"
    >
        <!-- Left: Record Range Info -->
        <div class="flex items-center gap-3">
            <p class="text-xs text-slate-500">
                <span class="font-semibold text-slate-700">{{ from }}-{{ to }}</span>
                dari
                <span class="font-semibold text-slate-700">{{ totalItems }}</span>
                data
            </p>
        </div>

        <!-- Right: Connected Segment Pagination Buttons -->
        <div
            v-if="totalPages > 1"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden text-sm"
        >
            <!-- Previous Button -->
            <button
                type="button"
                :disabled="currentPage <= 1"
                @click="setPage(currentPage - 1)"
                title="Halaman Sebelumnya"
                :class="[
                    'h-9 flex items-center justify-center border-r border-slate-200 transition-colors text-xs select-none w-9 px-0',
                    currentPage <= 1
                        ? 'text-slate-300 bg-slate-50/70 cursor-not-allowed'
                        : 'text-slate-600 bg-white hover:bg-slate-50 font-medium cursor-pointer',
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Page Numbers -->
            <template v-for="(p, idx) in pages" :key="idx">
                <button
                    v-if="typeof p === 'number'"
                    type="button"
                    @click="setPage(p)"
                    :title="`Halaman ${p}`"
                    :class="[
                        'h-9 flex items-center justify-center border-r border-slate-200 transition-colors text-xs select-none min-w-[36px] px-3 cursor-pointer',
                        currentPage === p
                            ? 'text-white bg-gradient-to-r from-emerald-500 to-emerald-600 font-bold'
                            : 'text-slate-600 bg-white hover:bg-slate-50 font-medium',
                    ]"
                >
                    {{ p }}
                </button>
                <span
                    v-else
                    class="h-9 flex items-center justify-center border-r border-slate-200 text-xs font-medium select-none text-slate-400 bg-slate-50 px-2.5"
                >
                    ...
                </span>
            </template>

            <!-- Next Button -->
            <button
                type="button"
                :disabled="currentPage >= totalPages"
                @click="setPage(currentPage + 1)"
                title="Halaman Berikutnya"
                :class="[
                    'h-9 flex items-center justify-center transition-colors text-xs select-none w-9 px-0',
                    currentPage >= totalPages
                        ? 'text-slate-300 bg-slate-50/70 cursor-not-allowed'
                        : 'text-slate-600 bg-white hover:bg-slate-50 font-medium cursor-pointer',
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</template>
