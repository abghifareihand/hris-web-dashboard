<script setup>
defineProps({
    headers: {
        type: Array,
        default: () => [],
    },
    striped: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="table-container shadow-xs">
        <table
            class="table"
            :class="{
                'table-striped': striped,
                'table-compact': compact,
            }"
        >
            <thead>
                <tr>
                    <slot name="header">
                        <th
                            v-for="(header, idx) in headers"
                            :key="idx"
                            :class="header.class || ''"
                        >
                            {{ header.label || header }}
                        </th>
                    </slot>
                </tr>
            </thead>
            <tbody>
                <slot />
            </tbody>
        </table>
    </div>
</template>
