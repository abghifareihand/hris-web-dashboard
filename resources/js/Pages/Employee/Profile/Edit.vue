<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import DatePicker from '@/Components/UI/DatePicker.vue';

const props = defineProps({
    user: Object,
    employee: Object,
});

const form = useForm({
    name: props.employee?.name || props.user?.name || '',
    phone: props.employee?.phone || '',
    gender: props.employee?.gender || '',
    birth_date: props.employee?.birth_date ? props.employee.birth_date.substring(0, 10) : '',
    address: props.employee?.address || '',
    avatar: null,
});

const avatarPreview = ref(null);
const fileInput = ref(null);

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const genderOptions = [
    { value: '', label: 'Pilih Jenis Kelamin' },
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

const submit = () => {
    form.post(route('employee.profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Profil Saya" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Profil Saya</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Perbarui foto profil, data pribadi, dan kontak domisili Anda.
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- 1. Avatar Section -->
                    <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-center gap-5">
                        <div class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden bg-emerald-600 text-white flex items-center justify-center font-bold text-3xl shadow-sm shrink-0 border-2 border-white">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Preview Avatar"
                                class="w-full h-full object-cover"
                            />
                            <img
                                v-else-if="user?.avatar"
                                :src="user.avatar.startsWith('http') ? user.avatar : '/storage/' + user.avatar"
                                :alt="user.name"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>
                                {{ employee?.name?.charAt(0) || user?.name?.charAt(0) || 'E' }}
                            </span>
                        </div>

                        <div class="space-y-1.5 text-center sm:text-left">
                            <h3 class="text-sm font-bold text-slate-900">Foto Profil</h3>
                            <p class="text-xs text-slate-500">
                                Format: JPG, PNG, atau WEBP. Ukuran maksimal 2MB.
                            </p>
                            <div class="pt-1 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="text-xs gap-1.5"
                                    @click="triggerFileInput"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Pilih Foto Baru</span>
                                </Button>
                                <span v-if="form.avatar" class="text-xs text-emerald-700 font-medium">
                                    Berkas terpilih: {{ form.avatar.name }}
                                </span>
                            </div>
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                class="hidden"
                                @change="handleAvatarChange"
                            />
                            <p v-if="form.errors.avatar" class="text-xs text-rose-600 font-medium mt-1">
                                {{ form.errors.avatar }}
                            </p>
                        </div>
                    </div>

                    <!-- 2. Data Pribadi Section -->
                    <div class="space-y-4">
                        <div class="border-b border-slate-100 pb-2">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Data Pribadi
                            </h3>
                            <p class="text-xs text-slate-500">Informasi identitas dan kontak dasar</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Lengkap -->
                            <div>
                                <Input
                                    label="Nama Lengkap"
                                    v-model="form.name"
                                    :error="form.errors.name"
                                    placeholder="Masukkan nama lengkap Anda..."
                                    required
                                />
                            </div>

                            <!-- Nomor HP / WhatsApp -->
                            <div>
                                <Input
                                    label="Nomor HP / WhatsApp"
                                    v-model="form.phone"
                                    :error="form.errors.phone"
                                    placeholder="Contoh: 081234567890"
                                />
                            </div>

                            <!-- Jenis Kelamin -->
                            <div>
                                <Select
                                    label="Jenis Kelamin"
                                    v-model="form.gender"
                                    :options="genderOptions"
                                    :error="form.errors.gender"
                                />
                            </div>

                            <!-- Tanggal Lahir -->
                            <div>
                                <DatePicker
                                    label="Tanggal Lahir"
                                    v-model="form.birth_date"
                                    placeholder="Pilih Tanggal Lahir"
                                    :error="form.errors.birth_date"
                                />
                            </div>
                        </div>

                        <!-- Alamat Domisili -->
                        <div>
                            <Textarea
                                label="Alamat Tempat Tinggal (Domisili)"
                                v-model="form.address"
                                :rows="3"
                                placeholder="Alamat lengkap tempat tinggal Anda saat ini..."
                                :error="form.errors.address"
                            />
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <Link :href="route('employee.profile.index')">
                            <Button type="button" variant="secondary">
                                Batal
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            variant="primary"
                            :loading="form.processing"
                        >
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
