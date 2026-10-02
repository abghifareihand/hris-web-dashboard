<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'secondary', // primary, secondary, success, warning, danger, info
    },
    type: {
        type: String,
        default: '', // support either variant or type
    },
    pill: {
        type: Boolean,
        default: false,
    },
});

const badgeVariant = computed(() => {
    return props.type || props.variant || 'secondary';
});
</script>

<template>
    <span
        class="badge"
        :class="[
            `badge-${badgeVariant}`,
            { 'badge-pill': pill },
        ]"
    >
        <slot />
    </span>
</template>
