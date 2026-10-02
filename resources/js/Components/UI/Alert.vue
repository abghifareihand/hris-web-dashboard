<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info', // success, danger, warning, info
    },
    type: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dismiss']);
const visible = ref(true);

const alertType = computed(() => {
    return props.type || props.variant || 'info';
});

const dismiss = () => {
    visible.value = false;
    emit('dismiss');
};
</script>

<template>
    <div
        v-if="visible"
        class="alert"
        :class="`alert-${alertType}`"
    >
        <!-- Success Icon -->
        <svg
            v-if="alertType === 'success'"
            class="alert-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Danger Icon -->
        <svg
            v-else-if="alertType === 'danger'"
            class="alert-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Warning Icon -->
        <svg
            v-else-if="alertType === 'warning'"
            class="alert-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        <!-- Info Icon -->
        <svg
            v-else
            class="alert-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <div class="alert-content">
            <div v-if="title" class="alert-title">{{ title }}</div>
            <div class="alert-message">
                <slot />
            </div>
        </div>

        <button
            v-if="dismissible"
            type="button"
            @click="dismiss"
            class="ml-auto text-current opacity-50 hover:opacity-100 cursor-pointer"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>
