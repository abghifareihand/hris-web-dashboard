<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, danger, ghost, white, accent, success, info
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const variantClass = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'btn-secondary';
        case 'danger':
            return 'btn-danger';
        case 'ghost':
            return 'btn-ghost';
        case 'white':
            return 'btn-white';
        case 'accent':
            return 'btn-accent';
        case 'success':
            return 'btn-success';
        case 'info':
            return 'btn-info';
        case 'primary':
        default:
            return 'btn-primary';
    }
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'btn-sm';
        case 'lg':
            return 'btn-lg';
        case 'md':
        default:
            return '';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="btn select-none"
        :class="[
            variantClass,
            sizeClass,
        ]"
    >
        <svg
            v-if="loading"
            class="animate-spin -ml-0.5 h-4 w-4 text-current"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <slot />
    </button>
</template>
