<script setup>
defineProps({
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    showSearch: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['search', 'reset']);
</script>

<template>
    <div class="flex flex-wrap items-center gap-3 w-full mb-4">
        <slot name="search">
            <div v-if="showSearch" class="relative flex-1 min-w-[220px]">
                <slot name="search-input" />
            </div>
        </slot>
        <slot name="filters" />
        <slot name="actions" />
    </div>
</template>
