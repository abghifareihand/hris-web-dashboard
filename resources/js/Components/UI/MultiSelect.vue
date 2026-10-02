<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih opsi...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    wrapperClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const openUpward = ref(false);
const multiselectRef = ref(null);

// Normalize options to { value, label }
const normalizedOptions = computed(() => {
    return props.options.map((opt) => {
        if (typeof opt === 'object' && opt !== null) {
            return {
                value: opt.value ?? opt.id,
                label: opt.label ?? opt.name ?? String(opt.value ?? opt.id),
            };
        }
        return {
            value: opt,
            label: String(opt),
        };
    });
});

// Selected options objects for badges
const selectedItems = computed(() => {
    const values = props.modelValue || [];
    return normalizedOptions.value.filter((opt) => values.includes(opt.value));
});

const isSelected = (value) => {
    return (props.modelValue || []).includes(value);
};

const toggleOption = (value) => {
    if (props.disabled) return;
    const current = [...(props.modelValue || [])];
    const index = current.indexOf(value);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(value);
    }
    emit('update:modelValue', current);
    emit('change', current);
};

const removeItem = (value) => {
    if (props.disabled) return;
    const current = (props.modelValue || []).filter((v) => v !== value);
    emit('update:modelValue', current);
    emit('change', current);
};

const clearAll = () => {
    if (props.disabled) return;
    emit('update:modelValue', []);
    emit('change', []);
};

const toggleOpen = () => {
    if (props.disabled) return;
    if (!isOpen.value && multiselectRef.value) {
        const rect = multiselectRef.value.getBoundingClientRect();
        openUpward.value = window.innerHeight - rect.bottom < 260 && rect.top > 260;
    }
    isOpen.value = !isOpen.value;
};

const close = () => {
    isOpen.value = false;
};

const handleClickOutside = (e) => {
    if (multiselectRef.value && !multiselectRef.value.contains(e.target)) {
        close();
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div
        ref="multiselectRef"
        :class="['w-full relative text-left', isOpen ? 'z-30' : '', wrapperClass]"
    >
        <!-- Label -->
        <label
            v-if="label"
            class="form-label"
            :class="{ 'form-label-required': required }"
        >
            {{ label }}
        </label>

        <!-- Trigger Input Area (Matches Input.vue & Dropdown.vue styling) -->
        <div
            @click="toggleOpen"
            :class="[
                'w-full min-h-[40px] px-3.5 py-1.5 flex items-center justify-between gap-2.5 bg-white rounded-lg border transition-all select-none text-[13px]',
                disabled
                    ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                    : 'cursor-pointer',
                isOpen
                    ? 'border-emerald-500 shadow-[0_0_0_3px_rgba(16,185,129,0.15)] text-slate-900 bg-white'
                    : error
                        ? 'border-rose-400 shadow-[0_0_0_3px_rgba(244,63,94,0.15)] text-slate-800'
                        : 'border-slate-300 hover:border-slate-400 text-slate-800',
            ]"
        >
            <!-- Tags Container or Placeholder -->
            <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0 py-0.5">
                <template v-if="selectedItems.length > 0">
                    <span
                        v-for="item in selectedItems"
                        :key="item.value"
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80 select-none animate-in fade-in zoom-in-95 duration-100"
                    >
                        <span>{{ item.label }}</span>
                        <button
                            type="button"
                            @click.stop="removeItem(item.value)"
                            class="text-emerald-600 hover:text-emerald-900 transition-colors p-0.5 rounded cursor-pointer"
                            title="Hapus"
                            aria-label="Hapus"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                </template>
                <span
                    v-else
                    class="text-[13px] text-slate-400 select-none"
                >
                    {{ placeholder }}
                </span>
            </div>

            <!-- Right Controls: Clear All & Chevron -->
            <div class="flex items-center gap-2 shrink-0 ml-1">
                <!-- Clear Button (when has values) -->
                <button
                    v-if="selectedItems.length > 0 && !disabled"
                    type="button"
                    @click.stop="clearAll"
                    class="text-slate-400 hover:text-slate-600 p-0.5 rounded transition-colors cursor-pointer"
                    title="Hapus semua"
                    aria-label="Hapus semua"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Vertical Divider (when clear button is visible) -->
                <div
                    v-if="selectedItems.length > 0 && !disabled"
                    class="h-4 w-px bg-slate-200"
                />

                <!-- Rotating Chevron Arrow -->
                <svg
                    class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0"
                    :class="{ 'rotate-180 text-emerald-500': isOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="form-error mt-1">
            {{ error }}
        </p>

        <!-- Dropdown Menu Panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 right-0 z-50 bg-white rounded-lg border border-slate-200 shadow-xl overflow-hidden"
                :class="openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
            >
                <div class="max-h-60 overflow-y-auto p-1.5 space-y-0.5">
                    <div
                        v-for="opt in normalizedOptions"
                        :key="opt.value"
                        @click="toggleOption(opt.value)"
                        :class="[
                            'flex items-center gap-2.5 px-3 py-2 rounded-md transition-colors cursor-pointer select-none text-[13px]',
                            isSelected(opt.value)
                                ? 'bg-emerald-50/70 text-emerald-900 font-medium'
                                : 'hover:bg-slate-50 text-slate-700'
                        ]"
                    >
                        <Checkbox
                            :model-value="isSelected(opt.value)"
                            @click.stop="toggleOption(opt.value)"
                        />
                        <span class="text-[13px]">
                            {{ opt.label }}
                        </span>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
