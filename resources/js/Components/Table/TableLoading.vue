<script setup>
defineProps({
    colspan: {
        type: Number,
        default: 10,
    },
    rows: {
        type: Number,
        default: 5,
    },
});
</script>

<template>
    <tr v-for="i in rows" :key="i" class="animate-pulse">
        <td :colspan="colspan" class="px-5 py-4">
            <div class="h-4 bg-slate-100 rounded-md w-full"></div>
        </td>
    </tr>
</template>
