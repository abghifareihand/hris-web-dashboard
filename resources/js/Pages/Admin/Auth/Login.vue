<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Platform Master Login" />

    <div class="mb-4 p-3 rounded-xl bg-slate-900 text-white text-xs font-medium flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        Portal Akses Khusus Super Administrator
    </div>

    <form @submit.prevent="submit" class="space-y-5">
        <div>
            <label for="admin-email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                Email Administrator
            </label>
            <Input
                id="admin-email"
                v-model="form.email"
                type="email"
                placeholder="admin@platform.com"
                required
                autofocus
                :error="form.errors.email"
            />
        </div>

        <div>
            <label for="admin-password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                Kata Sandi
            </label>
            <Input
                id="admin-password"
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                required
                :error="form.errors.password"
            />
        </div>

        <div class="flex items-center justify-between">
            <Checkbox v-model="form.remember" label="Ingat sesi ini" />
        </div>

        <div class="pt-2">
            <Button
                type="submit"
                variant="primary"
                class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white shadow-none"
                :loading="form.processing"
                :disabled="form.processing"
            >
                Masuk ke Platform Admin
            </Button>
        </div>

        <div class="text-center pt-2">
            <Link href="/" class="text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                &larr; Ke Halaman Publik
            </Link>
        </div>
    </form>
</template>
