<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    type: {
        type: String,
        default: "text",
    },
    currency: {
        type: Boolean,
        default: false,
    },
    clearable: {
        type: Boolean,
        default: false,
    },
    prefix: {
        type: String,
        default: "",
    },
    suffix: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "",
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
        default: "",
    },
    help: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "md", // sm, md, lg
    },
    defaultTime: {
        type: String,
        default: "",
    },
    id: {
        type: String,
        default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
    },
});

const emit = defineEmits(["update:modelValue", "clear", "click"]);

const clear = () => {
    emit("update:modelValue", isCurrency.value ? 0 : "");
    emit("clear");
};

const sizeClass = computed(() => {
    switch (props.size) {
        case "sm":
            return "input-sm";
        case "lg":
            return "input-lg";
        default:
            return "";
    }
});
const isCurrency = computed(() => props.currency || props.type === "currency");

const effectivePrefix = computed(() => {
    if (props.prefix) return props.prefix;
    if (isCurrency.value) return "Rp";
    return "";
});

const formatNumberWithDots = (val) => {
    if (val === "" || val === null || val === undefined) return "";
    const numStr = String(val).replace(/\D/g, "");
    if (!numStr) return "";
    return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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
            .replace(/\D/g, "").length;

        // Extract digits only
        const rawDigits = originalValue.replace(/\D/g, "");

        if (!rawDigits) {
            input.value = "";
            emit("update:modelValue", 0);
            return;
        }

        const numericValue = parseInt(rawDigits, 10);
        const formatted = rawDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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

        emit("update:modelValue", numericValue);
    } else {
        emit("update:modelValue", event.target.value);
    }
};

const onFocus = (event) => {
    if (
        props.type === "time" &&
        (!props.modelValue || props.modelValue === "") &&
        props.defaultTime
    ) {
        emit("update:modelValue", props.defaultTime);
        event.target.value = props.defaultTime;
    }
    if (
        isCurrency.value &&
        (props.modelValue === 0 ||
            props.modelValue === "0" ||
            props.modelValue === "")
    ) {
        event.target.select();
    }
};

const onKeyDown = (event) => {
    if (isCurrency.value && event.key === "Backspace") {
        const input = event.target;
        const pos = input.selectionStart;
        if (
            pos === input.selectionEnd &&
            pos > 0 &&
            input.value[pos - 1] === "."
        ) {
            event.preventDefault();
            const val = input.value;
            const newVal = val.slice(0, pos - 2) + val.slice(pos);
            const rawDigits = newVal.replace(/\D/g, "");
            const numericValue = rawDigits ? parseInt(rawDigits, 10) : 0;
            const formatted = rawDigits
                ? rawDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                : "";
            input.value = formatted;
            emit("update:modelValue", numericValue);

            const newPos = Math.max(0, pos - 2);
            input.setSelectionRange(newPos, newPos);
        }
    }
};

const onClick = (event) => {
    if (
        props.type === "time" &&
        (!props.modelValue || props.modelValue === "") &&
        props.defaultTime
    ) {
        emit("update:modelValue", props.defaultTime);
        event.target.value = props.defaultTime;
    }
    if (props.type === "time" || props.type === "date") {
        try {
            event.target.showPicker?.();
        } catch (e) {
            // Browser might throw if user activation is deemed insufficient or already open
        }
    }
    emit("click", event);
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
        <!-- Input with Prefix and/or Suffix as Attached Addon -->
        <div
            v-if="effectivePrefix || suffix"
            class="flex items-center w-full rounded-lg border transition-all duration-150 overflow-hidden bg-white shadow-2xs"
            :class="[
                size === 'sm' ? 'h-8' : size === 'lg' ? 'h-12' : 'h-10',
                error
                    ? 'border-danger-500 focus-within:ring-2 focus-within:ring-rose-500/20'
                    : 'border-slate-300 hover:border-slate-400 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20',
                disabled ? 'bg-slate-50 cursor-not-allowed opacity-75' : '',
            ]"
        >
            <!-- Prefix Addon -->
            <span
                v-if="effectivePrefix"
                class="inline-flex items-center h-full px-3.5 bg-slate-50 border-r border-slate-200 text-xs font-semibold text-slate-500 select-none shrink-0"
            >
                {{ effectivePrefix }}
            </span>

            <!-- Input Element -->
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
                @click="onClick"
                class="flex-1 w-full h-full px-3 py-2 text-[0.8125rem] text-slate-800 border-0 focus:outline-hidden focus:ring-0 bg-transparent min-w-0"
                :class="[
                    type === 'time' || type === 'date' ? 'cursor-pointer' : '',
                ]"
            />

            <!-- Clear button -->
            <button
                v-if="clearable && modelValue && !disabled"
                type="button"
                @click="clear"
                class="px-2 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer focus:outline-hidden shrink-0"
                title="Hapus"
                aria-label="Hapus"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Suffix Addon -->
            <span
                v-if="suffix"
                class="inline-flex items-center h-full px-3.5 bg-slate-50 border-l border-slate-200 text-xs font-semibold text-slate-500 select-none shrink-0"
            >
                {{ suffix }}
            </span>
        </div>

        <!-- Standard Input (No prefix / No suffix) -->
        <div v-else class="relative w-full">
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
                @click="onClick"
                class="input"
                :class="[
                    sizeClass,
                    { 'border-danger-500': error },
                    clearable && modelValue ? '!pr-9' : '',
                    type === 'time' || type === 'date' ? 'cursor-pointer' : '',
                ]"
            />
            <button
                v-if="clearable && modelValue && !disabled"
                type="button"
                @click="clear"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer focus:outline-hidden"
                title="Hapus"
                aria-label="Hapus"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
        <p v-if="help && !error" class="form-help">
            {{ help }}
        </p>
        <p v-if="error" class="form-error">
            {{ error }}
        </p>
    </div>
</template>
