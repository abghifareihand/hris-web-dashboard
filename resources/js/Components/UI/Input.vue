<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    currency: {
        type: Boolean,
        default: false,
    },
    prefix: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    help: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    id: {
        type: String,
        default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
    },
});

const emit = defineEmits(['update:modelValue']);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'input-sm';
        case 'lg':
            return 'input-lg';
        default:
            return '';
    }
});
const isCurrency = computed(() => props.currency || props.type === 'currency');

const effectivePrefix = computed(() => {
    if (props.prefix) return props.prefix;
    if (isCurrency.value) return 'Rp';
    return '';
});

const formatNumberWithDots = (val) => {
    if (val === '' || val === null || val === undefined) return '';
    const numStr = String(val).replace(/\D/g, '');
    if (!numStr) return '';
    return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const displayValue = computed(() => {
    if (isCurrency.value) {
        return formatNumberWithDots(props.modelValue);
    }
    return props.modelValue;
});

const onInput = (event) => {
    if (isCurrency.value) {
        const input = event.target;
        const originalValue = input.value;
        const cursorPosition = input.selectionStart || 0;

        // Count digits before cursor
        const digitsBeforeCursor = originalValue
            .slice(0, cursorPosition)
            .replace(/\D/g, '').length;

        // Extract digits only
        const rawDigits = originalValue.replace(/\D/g, '');

        if (!rawDigits) {
            input.value = '';
            emit('update:modelValue', 0);
            return;
        }

        const numericValue = parseInt(rawDigits, 10);
        const formatted = rawDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = formatted;

        // Restore cursor position based on digitsBeforeCursor
        let newCursorPos = formatted.length;
        let digitCount = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (/\d/.test(formatted[i])) {
                digitCount++;
            }
            if (digitCount === digitsBeforeCursor) {
                newCursorPos = i + 1;
                break;
            }
        }
        input.setSelectionRange(newCursorPos, newCursorPos);

        emit('update:modelValue', numericValue);
    } else {
        emit('update:modelValue', event.target.value);
    }
};

const onFocus = (event) => {
    if (isCurrency.value && (props.modelValue === 0 || props.modelValue === '0' || props.modelValue === '')) {
        event.target.select();
    }
};

const onKeyDown = (event) => {
    if (isCurrency.value && event.key === 'Backspace') {
        const input = event.target;
        const pos = input.selectionStart;
        if (pos === input.selectionEnd && pos > 0 && input.value[pos - 1] === '.') {
            event.preventDefault();
            const val = input.value;
            const newVal = val.slice(0, pos - 2) + val.slice(pos);
            const rawDigits = newVal.replace(/\D/g, '');
            const numericValue = rawDigits ? parseInt(rawDigits, 10) : 0;
            const formatted = rawDigits ? rawDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            input.value = formatted;
            emit('update:modelValue', numericValue);

            const newPos = Math.max(0, pos - 2);
            input.setSelectionRange(newPos, newPos);
        }
    }
};
</script>

<template>
    <div class="w-full">
        <label
            v-if="label"
            :for="id"
            class="form-label"
            :class="{ 'form-label-required': required }"
        >
            {{ label }}
        </label>
        <div class="relative w-full">
            <span
                v-if="effectivePrefix"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 select-none pointer-events-none"
            >
                {{ effectivePrefix }}
            </span>
            <input
                :id="id"
                :type="isCurrency ? 'text' : type"
                :inputmode="isCurrency ? 'numeric' : undefined"
                :value="displayValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                @input="onInput"
                @focus="onFocus"
                @keydown="onKeyDown"
                class="input"
                :class="[
                    sizeClass,
                    { 'border-danger-500': error },
                    effectivePrefix ? '!pl-9' : '',
                ]"
            />
        </div>
        <p v-if="help && !error" class="form-help">
            {{ help }}
        </p>
        <p v-if="error" class="form-error">
            {{ error }}
        </p>
    </div>
</template>
