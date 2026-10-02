<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
});

const formatTimeForInput = (val) => {
    if (!val) return '';
    return val.substring(0, 5);
};

const form = useForm({
    name: props.shift.name || '',
    clock_in: formatTimeForInput(props.shift.clock_in),
    clock_out: formatTimeForInput(props.shift.clock_out),
});

const submit = () => {
    form.put(route('owner.management.schedules.shifts.update', props.shift.id));
};
</script>

<template>
    <Head :title="`Edit Shift - ${shift.name}`" />

    <div class="w-full space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Shift Kerja</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui nama shift, jam masuk, atau jam pulang operasional.
                </p>
            </div>
            <Link :href="route('owner.management.schedules.shifts.index')">
                <Button variant="secondary">Kembali</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
            <!-- Nama Shift -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Nama Shift <span class="text-rose-500">*</span>
                </label>
                <Input
                    v-model="form.name"
                    placeholder="Contoh: Shift Pagi, Shift Siang"
                    :error="form.errors.name"
                    required
                />
            </div>

            <!-- Jam Masuk & Pulang -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Jam Masuk (Clock-in) <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.clock_in"
                        type="time"
                        :error="form.errors.clock_in"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Jam Pulang (Clock-out) <span class="text-rose-500">*</span>
                    </label>
                    <Input
                        v-model="form.clock_out"
                        type="time"
                        :error="form.errors.clock_out"
                        required
                    />
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <Link :href="route('owner.management.schedules.shifts.index')">
                    <Button variant="secondary" type="button">Batal</Button>
                </Link>
                <Button variant="primary" type="submit" :loading="form.processing">
                    Perbarui Shift
                </Button>
            </div>
        </form>
    </div>
</template>
