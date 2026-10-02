<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'default', // sm, default/md, lg, xl, full
    },
    maxWidth: {
        type: String,
        default: '', // for backward compatibility with sm, md, lg, xl, 2xl
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'update:show']);

const close = () => {
    if (props.closeable) {
        emit('close');
        emit('update:show', false);
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = null;
        }
    }
);

const sizeClass = computed(() => {
    const s = props.size !== 'default' ? props.size : (props.maxWidth || 'default');
    switch (s) {
        case 'sm':
            return 'modal-sm';
        case 'lg':
            return 'modal-lg';
        case 'xl':
        case '2xl':
            return 'modal-xl';
        case 'full':
            return 'modal-full';
        default:
            return '';
    }
});
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="modal-backdrop"
                @click.self="close"
            >
                <transition
                    appear
                    enter-active-class="transition-all duration-200 ease-out transform"
                    enter-from-class="opacity-0 scale-95 translate-y-1"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-1"
                >
                    <div
                        v-if="show"
                        class="modal"
                        :class="sizeClass"
                    >
                    <!-- Header -->
                    <div v-if="title || $slots.title || $slots.header" class="modal-header">
                        <slot name="header">
                            <h3 class="modal-title">
                                <slot name="title">{{ title }}</slot>
                            </h3>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            @click="close"
                            class="modal-close"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="showFooter && $slots.footer" class="modal-footer">
                        <slot name="footer" />
                    </div>
                </div>
            </transition>
        </div>
    </transition>
    </teleport>
</template>
