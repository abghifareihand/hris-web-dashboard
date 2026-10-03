<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import TimePicker from '@/Components/UI/TimePicker.vue';

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
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.management.schedules.shifts.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Daftar Shift Kerja"
            >
                <svg
                    class="w-5 h-5 transition-transform duration-200 group-hover:-translate-x-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Edit Shift Kerja
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Perbarui nama shift, jam masuk, atau jam pulang operasional.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
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
                        <TimePicker
                            v-model="form.clock_in"
                            default-time="08:00"
                            :error="form.errors.clock_in"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Jam Pulang (Clock-out) <span class="text-rose-500">*</span>
                        </label>
                        <TimePicker
                            v-model="form.clock_out"
                            default-time="17:00"
                            :error="form.errors.clock_out"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.management.schedules.shifts.index')">
                    <Button variant="ghost" type="button">Batal</Button>
                </Link>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                >
                    Simpan
                </Button>
            </div>
        </form>
    </div>
</template>
