<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: '',
    },
    options: {
        type: Array,
        required: true,
        // Options format:
        // - Array of objects: [{ value: '', label: 'Semua Status', dotClass?: 'bg-slate-400' }]
        // - Array of primitives: ['Semua', 'Pending', 'Approved', 'Rejected']
    },
    placeholder: {
        type: String,
        default: 'Semua',
    },
    allLabel: {
        type: String,
        default: '', // If provided (e.g. 'Semua Status'), auto prepends empty option if not present
    },
    wrapperClass: {
        type: String,
        default: '',
    },
    buttonClass: {
        type: String,
        default: '',
    },
    panelClass: {
        type: String,
        default: '',
    },
    fullWidth: {
        type: Boolean,
        default: true,
    },
    showDot: {
        type: Boolean,
        default: false,
    },
    defaultDotColor: {
        type: String,
        default: 'bg-emerald-500',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md', // 'xs', 'sm', 'md', 'lg'
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref(null);
const panelRef = ref(null);

const scrollToSelected = () => {
    if (!panelRef.value) return;
    const selectedEl = panelRef.value.querySelector('[data-selected="true"]');
    if (selectedEl) {
        const panel = panelRef.value;
        const elTop = selectedEl.offsetTop;
        const elHeight = selectedEl.offsetHeight;
        const panelHeight = panel.clientHeight;
        if (panelHeight > 0) {
            panel.scrollTop = Math.max(0, elTop - (panelHeight / 2) + (elHeight / 2));
        }
    }
};

watch(isOpen, (val) => {
    if (val) {
        nextTick(() => {
            scrollToSelected();
            requestAnimationFrame(() => {
                scrollToSelected();
            });
        });
    }
});

// Normalize options to guarantee standard structure { value, label, dotClass }
const normalizedOptions = computed(() => {
    let list = [];

    // Auto prepend "Semua" option if allLabel prop is specified and no empty value option exists
    if (props.allLabel) {
        const hasEmpty = props.options.some((opt) => {
            const val = typeof opt === 'object' && opt !== null ? opt.value : opt;
            return val === '' || val === null || val === undefined;
        });
        if (!hasEmpty) {
            list.push({
                value: '',
                label: props.allLabel,
                dotClass: 'bg-slate-300',
            });
        }
    }

    props.options.forEach((opt) => {
        if (typeof opt === 'object' && opt !== null) {
            const val = opt.value ?? '';
            list.push({
                value: val,
                label: opt.label ?? opt.name ?? String(val),
                dotClass: opt.dotClass || (val === '' ? 'bg-slate-300' : props.defaultDotColor),
            });
        } else {
            const val = opt;
            list.push({
                value: val,
                label: String(val),
                dotClass: val === '' || String(val).toLowerCase().startsWith('semua') ? 'bg-slate-300' : props.defaultDotColor,
            });
        }
    });

    return list;
});

const selectedOption = computed(() => {
    return normalizedOptions.value.find((opt) => String(opt.value) === String(props.modelValue ?? ''));
});

const displayLabel = computed(() => {
    if (selectedOption.value) {
        return selectedOption.value.label;
    }
    return props.placeholder || 'Semua';
});

const isSelected = (value) => {
    return String(value) === String(props.modelValue ?? '');
};

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const selectOption = (value) => {
    emit('update:modelValue', value);
    emit('change', value);
    closeDropdown();
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

const handleKeyDown = (event) => {
    if (event.key === 'Escape' && isOpen.value) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

// Size configurations - unified with Input.vue height and styling
const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return {
                button: 'h-8 min-h-[32px] px-2.5 text-xs rounded-lg',
                panel: 'text-xs',
                item: 'px-2.5 py-1.5 text-xs',
            };
        case 'lg':
            return {
                button: 'h-12 min-h-[48px] px-4 text-base rounded-lg',
                panel: 'text-base',
                item: 'px-4 py-2.5 text-base',
            };
        case 'sm':
        case 'md':
        default:
            return {
                button: 'h-10 min-h-[40px] px-3 text-[13px] rounded-lg',
                panel: 'text-[13px]',
                item: 'px-3 py-2 text-[13px]',
            };
    }
});

const isPlaceholderState = computed(() => {
    if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
        if (!selectedOption.value) return true;
        if (props.placeholder && selectedOption.value.label === props.placeholder) return true;
    }
    return false;
});
</script>

<template>
    <div
        ref="dropdownRef"
        :class="[
            'relative text-left',
            fullWidth ? 'w-full block' : 'inline-block',
            isOpen ? 'z-30' : '',
            wrapperClass,
        ]"
    >
        <label
            v-if="label"
            class="form-label"
            :class="{ 'form-label-required': required }"
        >
            {{ label }}
        </label>

        <!-- Trigger Button (Matches Input.vue height, border, and emerald aura focus) -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'w-full flex items-center justify-between gap-2 bg-white font-normal transition-all select-none border text-left outline-none',
                sizeClasses.button,
                disabled ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'cursor-pointer',
                isOpen
                    ? 'border-emerald-500 shadow-[0_0_0_3px_rgba(16,185,129,0.15)] text-slate-900 bg-white'
                    : error
                        ? 'border-rose-400 shadow-[0_0_0_3px_rgba(244,63,94,0.15)] text-slate-900'
                        : 'border-slate-300 hover:border-slate-400 text-slate-900',
                buttonClass,
            ]"
            :aria-expanded="isOpen"
        >
            <div class="flex items-center gap-2 truncate">
                <span
                    class="truncate leading-normal"
                    :class="isPlaceholderState ? 'text-slate-400 font-normal' : 'text-slate-900 font-normal'"
                >
                    {{ displayLabel }}
                </span>
            </div>

            <!-- Rotating Chevron Arrow -->
            <svg
                class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0 ml-1.5"
                :class="{ 'rotate-180 text-emerald-500': isOpen }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu Panel with Smooth Custom Scrollbar -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            @after-enter="scrollToSelected"
        >
            <div
                v-if="isOpen"
                ref="panelRef"
                :class="[
                    'absolute left-0 top-full mt-1.5 w-full rounded-xl bg-white shadow-xl border border-slate-200/80 py-1.5 max-h-60 overflow-y-auto focus:outline-none z-50 dropdown-scroll',
                    sizeClasses.panel,
                    panelClass,
                ]"
            >
                <div v-if="normalizedOptions.length === 0" class="px-4 py-3 text-xs text-slate-400 text-center">
                    Tidak ada opsi tersedia
                </div>

                <button
                    v-for="opt in normalizedOptions"
                    :key="String(opt.value)"
                    type="button"
                    :data-selected="isSelected(opt.value) ? 'true' : undefined"
                    @click="selectOption(opt.value)"
                    :class="[
                        'w-full flex items-center justify-between transition-colors text-left cursor-pointer group select-none',
                        sizeClasses.item,
                        isSelected(opt.value)
                            ? 'bg-emerald-50 text-emerald-700 font-semibold'
                            : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-normal',
                    ]"
                >
                    <div class="flex items-center gap-2.5 truncate">
                        <!-- Bullet Dot Indicator -->
                        <span
                            v-if="showDot"
                            class="w-2 h-2 rounded-full shrink-0"
                            :class="opt.dotClass"
                        ></span>
                        <span class="truncate">{{ opt.label }}</span>
                    </div>

                    <!-- Checkmark for selected item -->
                    <svg
                        v-if="isSelected(opt.value)"
                        class="w-4 h-4 text-emerald-600 shrink-0 ml-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </div>
        </transition>

        <!-- Error Helper Text -->
        <p v-if="error" class="mt-1.5 text-xs text-rose-600 font-medium">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
/* Clean Custom Scrollbar matching Modern UI */
.dropdown-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}

.dropdown-scroll::-webkit-scrollbar {
    width: 6px;
}

.dropdown-scroll::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}

.dropdown-scroll::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.dropdown-scroll::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
</style>
