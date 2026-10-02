<script setup>
defineProps({
    modelValue: {
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
    rows: {
        type: Number,
        default: 3,
    },
    placeholder: {
        type: String,
        default: '',
    },
    disabled: {
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
    id: {
        type: String,
        default: () => `textarea-${Math.random().toString(36).substr(2, 9)}`,
    },
});

defineEmits(['update:modelValue']);
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
        <textarea
            :id="id"
            :rows="rows"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            @input="$emit('update:modelValue', $event.target.value)"
            class="input"
            :class="{ 'border-danger-500': error }"
        ></textarea>
        <p v-if="help && !error" class="form-help">
            {{ help }}
        </p>
        <p v-if="error" class="form-error">
            {{ error }}
        </p>
    </div>
</template>
