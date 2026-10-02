<script setup>
import { ref, computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
});

const fileInputRef = ref(null);
const logoPreview = ref(null);
const deleteLogoFlag = ref(false);

const currentLogo = computed(() => {
    if (logoPreview.value) {
        return logoPreview.value;
    }
    if (props.company.logo && !deleteLogoFlag.value) {
        return "/storage/" + props.company.logo;
    }
    return null;
});

const form = useForm({
    name_company: props.company.name_company || props.company.name || "",
    email: props.company.email || "",
    phone: props.company.phone || "",
    business_type: props.company.business_type || "",
    province: props.company.province || "",
    city: props.company.city || "",
    logo: null,
    delete_logo: false,
});

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        deleteLogoFlag.value = false;
        form.delete_logo = false;
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    logoPreview.value = null;
    form.logo = null;
    if (props.company.logo) {
        deleteLogoFlag.value = true;
        form.delete_logo = true;
    }
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};

const submit = () => {
    form.post(route("owner.management.company.profile.update"), {
        _method: "put",
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Informasi Perusahaan" />

    <div class="w-full space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                Informasi Perusahaan
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Kelola data profil dan identitas resmi perusahaan Anda.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Left Column: Logo Perusahaan (lg:col-span-3) -->
                    <div class="lg:col-span-3 flex flex-col items-center text-center space-y-3 pt-1">
                        <label class="text-xs font-semibold text-slate-700">
                            Logo Perusahaan
                        </label>

                        <!-- Square Dashed Container with Grayish background -->
                        <div class="w-36 h-36 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-100/90 flex items-center justify-center overflow-hidden relative">
                            <!-- Image Preview if available -->
                            <template v-if="currentLogo">
                                <img
                                    :src="currentLogo"
                                    alt="Logo Perusahaan"
                                    class="w-full h-full object-cover rounded-2xl"
                                />
                                <!-- Delete / Cancel button (Icon X) -->
                                <button
                                    type="button"
                                    @click.stop="removeLogo"
                                    title="Hapus Logo"
                                    class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full bg-slate-900/75 hover:bg-rose-600 text-white shadow-md transition-all duration-150 cursor-pointer hover:scale-110 z-10"
                                >
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </template>
                            <!-- Placeholder Building Icon -->
                            <div v-else class="text-slate-400 flex items-center justify-center">
                                <svg class="w-12 h-12 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            class="hidden"
                            @change="handleLogoChange"
                        />

                        <!-- Button "Pilih Logo" / "Ganti Logo" -->
                        <button
                            type="button"
                            @click="fileInputRef?.click()"
                            class="w-36 inline-flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold text-emerald-600 bg-white hover:bg-emerald-50/60 border border-emerald-500 rounded-lg transition-colors cursor-pointer select-none"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ currentLogo ? 'Ganti Logo' : 'Pilih Logo' }}
                        </button>

                        <p class="text-[11px] text-slate-400">
                            Format: JPG, PNG, SVG. Maks 2MB
                        </p>

                        <p v-if="form.errors.logo" class="text-xs text-rose-500 font-medium">
                            {{ form.errors.logo }}
                        </p>
                    </div>

                    <!-- Right Column: Form Fields (lg:col-span-9) -->
                    <div class="lg:col-span-9 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <Input
                            label="Nama Perusahaan"
                            v-model="form.name_company"
                            placeholder="PT Nama Perusahaan"
                            required
                            :error="form.errors.name_company"
                        />

                        <Input
                            label="Bidang Usaha"
                            v-model="form.business_type"
                            placeholder="Technology"
                            required
                            :error="form.errors.business_type"
                        />

                        <Input
                            label="Email Perusahaan"
                            v-model="form.email"
                            type="email"
                            placeholder="info@perusahaan.com"
                            required
                            :error="form.errors.email"
                        />

                        <Input
                            label="No. Telepon"
                            v-model="form.phone"
                            placeholder="081234567890"
                            :error="form.errors.phone"
                        />

                        <Input
                            label="Provinsi"
                            v-model="form.province"
                            placeholder="Jawa Barat"
                            required
                            :error="form.errors.province"
                        />

                        <Input
                            label="Kota"
                            v-model="form.city"
                            placeholder="Bandung"
                            required
                            :error="form.errors.city"
                        />
                    </div>
                </div>
            </div>

        <!-- Submit Section -->
        <div class="flex items-center justify-end pt-2 pb-8">
            <Button
                variant="primary"
                type="submit"
                :loading="form.processing"
                :disabled="form.processing"
            >
                Simpan
            </Button>
        </div>
    </form>
</div>
</template>
