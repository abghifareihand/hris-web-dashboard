<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Boolean, Array, Number, String],
        default: false,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: () => `checkbox-${Math.random().toString(36).substr(2, 9)}`,
    },
});

const emit = defineEmits(['update:modelValue']);

const isChecked = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }
    return Boolean(props.modelValue);
});

const handleChange = (event) => {
    if (props.disabled) return;
    const checked = event.target.checked;
    if (Array.isArray(props.modelValue)) {
        const newValue = [...props.modelValue];
        if (checked) {
            if (!newValue.includes(props.value)) {
                newValue.push(props.value);
            }
        } else {
            const index = newValue.indexOf(props.value);
            if (index > -1) {
                newValue.splice(index, 1);
            }
        }
        emit('update:modelValue', newValue);
    } else {
        emit('update:modelValue', checked);
    }
};
</script>

<template>
    <label
        class="relative inline-flex items-center gap-2.5 cursor-pointer select-none group"
        :class="{ 'opacity-50 !cursor-not-allowed': disabled }"
    >
        <input
            type="checkbox"
            :checked="isChecked"
            :value="value"
            :disabled="disabled"
            @change="handleChange"
            class="sr-only"
        />
        <span
            class="w-4 h-4 shrink-0 rounded-[4px] border flex items-center justify-center transition-colors select-none"
            :class="[
                isChecked
                    ? 'bg-emerald-600 border-emerald-600 text-white'
                    : 'bg-white border-slate-300 group-hover:border-slate-400'
            ]"
        >
            <svg
                class="w-2.5 h-2.5 text-white pointer-events-none transition-transform duration-75 block"
                :class="isChecked ? 'opacity-100 scale-100' : 'opacity-0 scale-75'"
                viewBox="0 0 12 10"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M1 5L4.5 8.5L11 1.5"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>
        <span v-if="label" class="text-sm font-medium text-slate-700 leading-normal truncate">
            {{ label }}
        </span>
        <slot />
    </label>
</template>
