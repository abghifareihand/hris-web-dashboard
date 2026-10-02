<script setup>
defineProps({
    title: {
        type: String,
        default: 'Tidak ada data ditemukan',
    },
    message: {
        type: String,
        default: 'Belum ada data yang tersedia pada tabel ini.',
    },
    colspan: {
        type: Number,
        default: 10,
    },
});
</script>

<template>
    <tr>
        <td :colspan="colspan" class="p-0">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div class="empty-state-title">{{ title }}</div>
                <div class="empty-state-description max-w-sm mx-auto text-xs">{{ message }}</div>
                <div v-if="$slots.action" class="mt-4">
                    <slot name="action" />
                </div>
            </div>
        </td>
    </tr>
</template>
