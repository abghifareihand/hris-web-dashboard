<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <AppLayout :title="title">
        <slot />
    </AppLayout>
</template>
