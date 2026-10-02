<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Array,
        required: true,
        // Item format:
        // { name: string, label?: string, href?: string, active?: boolean, id?: string|number }
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    wrapperClass: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isItemActive = (item) => {
    if (typeof item.active === 'boolean') {
        return item.active;
    }
    const val = item.id ?? item.name;
    return props.modelValue === val;
};

const selectTab = (item) => {
    const val = item.id ?? item.name;
    emit('update:modelValue', val);
    emit('change', val);
};
</script>

<template>
    <div class="border-b border-slate-200" :class="wrapperClass">
        <nav class="-mb-px flex space-x-6 overflow-x-auto">
            <template v-for="item in items" :key="item.name || item.id">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    :class="[
                        isItemActive(item)
                            ? 'border-emerald-600 text-emerald-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                        'whitespace-nowrap py-3 px-1 border-b-2 text-xs transition-colors'
                    ]"
                >
                    {{ item.label || item.name }}
                </Link>

                <button
                    v-else
                    type="button"
                    @click="selectTab(item)"
                    :class="[
                        isItemActive(item)
                            ? 'border-emerald-600 text-emerald-600 font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium',
                        'whitespace-nowrap py-3 px-1 border-b-2 text-xs transition-colors focus:outline-none'
                    ]"
                >
                    {{ item.label || item.name }}
                </button>
            </template>
        </nav>
    </div>
</template>
