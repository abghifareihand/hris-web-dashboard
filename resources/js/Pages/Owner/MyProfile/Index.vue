<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

// Profile Info Form
const profileForm = useForm({
    _method: 'PUT',
    name: props.user.name || '',
    email: props.user.email || '',
    avatar: null,
});

const avatarPreview = ref(props.user.avatar || null);

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        profileForm.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const updateProfile = () => {
    profileForm.post(route('owner.my-profile.update'), {
        preserveScroll: true,
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('owner.my-profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Profil Saya" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Pengaturan Profil</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola informasi akun pemilik perusahaan dan perbarui keamanan kata sandi Anda.
            </p>
        </div>

        <!-- Section 1: Profil & Avatar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Informasi Akun</h2>
            <p class="text-xs text-slate-500 mb-6">Perbarui nama lengkap, alamat email kontak, serta foto profil.</p>

            <form @submit.prevent="updateProfile" class="space-y-6">
                <!-- Avatar Upload -->
                <div class="flex items-center gap-5">
                    <div class="relative w-20 h-20 rounded-2xl bg-slate-100 overflow-hidden border-2 border-slate-200 shrink-0 flex items-center justify-center">
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            alt="Avatar"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="text-2xl font-bold text-slate-400 uppercase">
                            {{ user.name?.charAt(0) }}
                        </span>
                    </div>

                    <div>
                        <label class="block">
                            <span class="sr-only">Pilih foto avatar</span>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleAvatarChange"
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition cursor-pointer"
                            />
                        </label>
                        <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, atau SVG. Maksimal 2MB.</p>
                        <p v-if="profileForm.errors.avatar" class="text-xs text-rose-500 mt-1">{{ profileForm.errors.avatar }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <Input v-model="profileForm.name" :error="profileForm.errors.name" required />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                        <Input v-model="profileForm.email" type="email" :error="profileForm.errors.email" required />
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <Button variant="primary" type="submit" :loading="profileForm.processing">
                        Simpan Perubahan
                    </Button>
                </div>
            </form>
        </div>

        <!-- Section 2: Keamanan Kata Sandi -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Keamanan Kata Sandi</h2>
            <p class="text-xs text-slate-500 mb-6">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap terlindungi.</p>

            <form @submit.prevent="updatePassword" class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kata Sandi Saat Ini</label>
                    <Input
                        v-model="passwordForm.current_password"
                        type="password"
                        placeholder="••••••••"
                        :error="passwordForm.errors.current_password"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kata Sandi Baru</label>
                    <Input
                        v-model="passwordForm.password"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        :error="passwordForm.errors.password"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                    <Input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        placeholder="Ulangi kata sandi baru"
                        required
                    />
                </div>

                <div class="pt-4 flex justify-end">
                    <Button variant="primary" type="submit" :loading="passwordForm.processing">
                        Perbarui Kata Sandi
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
