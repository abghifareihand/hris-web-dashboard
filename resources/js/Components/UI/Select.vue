<script setup>
import Dropdown from './Dropdown.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, null],
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Semua',
    },
    allLabel: {
        type: String,
        default: '',
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
    size: {
        type: String,
        default: 'md',
    },
    searchable: {
        type: Boolean,
        default: false,
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari...',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);
</script>

<template>
    <Dropdown
        :model-value="modelValue"
        :options="options"
        :label="label"
        :required="required"
        :placeholder="placeholder"
        :all-label="allLabel"
        :wrapper-class="wrapperClass"
        :button-class="buttonClass"
        :panel-class="panelClass"
        :full-width="fullWidth"
        :show-dot="showDot"
        :default-dot-color="defaultDotColor"
        :disabled="disabled"
        :error="error"
        :size="size"
        :searchable="searchable"
        :search-placeholder="searchPlaceholder"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
    />
</template>
