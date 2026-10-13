<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import Textarea from "@/Components/UI/Textarea.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    divisions: {
        type: Array,
        default: () => [],
    },
});

const getTodayStr = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const getFutureDateStr = (days = 7) => {
    const d = new Date();
    d.setDate(d.getDate() + days);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const form = useForm({
    title: "",
    branch_id: "",
    division_id: "",
    start_date: getTodayStr(),
    end_date: getFutureDateStr(7),
    content: "",
    image: null,
});

const fileInputRef = ref(null);
const selectedFileName = ref("");
const selectedFileSize = ref("");
const imagePreview = ref(null);

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const onFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.image = file;
        selectedFileName.value = file.name;
        const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
        selectedFileSize.value = `${sizeInMb} MB`;
        imagePreview.value = URL.createObjectURL(file);
    } else {
        removeImage();
    }
};

const removeImage = () => {
    form.image = null;
    selectedFileName.value = "";
    selectedFileSize.value = "";
    imagePreview.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};

const branchOptions = computed(() => [
    { value: "", label: "Semua Cabang (Global)" },
    ...props.branches.map((b) => ({ value: String(b.id), label: b.name })),
]);

const divisionOptions = computed(() => [
    { value: "", label: "Semua Divisi (Global)" },
    ...props.divisions.map((d) => ({ value: String(d.id), label: d.name })),
]);

const submit = () => {
    form.post(route("owner.announcements.store"));
};
</script>

<template>
    <Head title="Tambah Pengumuman Baru" />

    <div class="w-full space-y-6">
        <!-- Header with Back Button -->
        <div class="flex items-start gap-3.5 sm:gap-4">
            <Link
                :href="route('owner.announcements.index')"
                class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                title="Kembali ke Daftar Pengumuman"
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
                    Tambah Pengumuman
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Lengkapi formulir untuk menyiarkan informasi pengumuman baru kepada karyawan.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. Informasi Pengumuman -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Pengumuman
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Judul -->
                    <Input
                        label="Judul Pengumuman"
                        v-model="form.title"
                        placeholder="Contoh: Pemberitahuan Libur Bersama Nasional & Penyesuaian Shift"
                        :error="form.errors.title"
                        required
                    />

                    <!-- Isi Pengumuman -->
                    <Textarea
                        label="Isi Pengumuman"
                        v-model="form.content"
                        placeholder="Tuliskan isi pengumuman, instruksi, atau detail informasi kepada seluruh karyawan..."
                        :error="form.errors.content"
                        :rows="6"
                        required
                    />

                    <!-- Upload Gambar Banner / Lampiran -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            Banner / Lampiran Gambar <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>

                        <!-- Hidden native file input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/jpg"
                            @change="onFileChange"
                            class="hidden"
                        />

                        <!-- Custom File Upload UI -->
                        <div
                            @click="triggerFileInput"
                            :class="[
                                'w-full min-h-[44px] h-11 px-3.5 bg-white border rounded-xl flex items-center justify-between gap-2 cursor-pointer transition select-none shadow-xs',
                                selectedFileName
                                    ? 'border-emerald-500 bg-emerald-50/20'
                                    : 'border-slate-300 hover:border-slate-400 hover:bg-slate-50/50',
                                form.errors.image ? 'border-rose-400 ring-2 ring-rose-100' : '',
                            ]"
                        >
                            <!-- File Info or Placeholder -->
                            <div class="flex items-center gap-2.5 truncate">
                                <svg
                                    v-if="selectedFileName"
                                    class="w-4 h-4 text-emerald-600 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-4 h-4 text-slate-400 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>

                                <span
                                    class="text-xs sm:text-sm truncate"
                                    :class="
                                        selectedFileName
                                            ? 'text-slate-900 font-medium'
                                            : 'text-slate-400 font-normal'
                                    "
                                >
                                    {{ selectedFileName || "Pilih file gambar banner (JPG, PNG, WEBP)..." }}
                                </span>

                                <span
                                    v-if="selectedFileSize"
                                    class="text-[11px] text-slate-400 font-normal shrink-0"
                                >
                                    ({{ selectedFileSize }})
                                </span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <!-- Clear button if file selected -->
                                <button
                                    v-if="selectedFileName"
                                    type="button"
                                    @click.stop="removeImage"
                                    class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Hapus file terpilih"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>

                                <!-- Choose File badge -->
                                <span
                                    class="text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 px-3 py-1.5 rounded-lg transition shadow-2xs"
                                >
                                    Choose File
                                </span>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal ukuran file 5 MB.
                        </p>

                        <!-- Image Preview Box -->
                        <div
                            v-if="imagePreview"
                            class="mt-3.5 max-w-md rounded-2xl overflow-hidden border border-slate-200/80 shadow-xs bg-slate-50 relative group"
                        >
                            <img
                                :src="imagePreview"
                                alt="Preview Lampiran"
                                class="w-full h-auto max-h-64 object-cover"
                            />
                        </div>
                        <p v-if="form.errors.image" class="text-xs text-rose-600 mt-1.5">
                            {{ form.errors.image }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Sasaran & Periode Tayang -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Sasaran & Periode Tayang
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Sasaran Cabang & Divisi -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <Select
                                label="Cabang Sasaran"
                                v-model="form.branch_id"
                                :options="branchOptions"
                                :error="form.errors.branch_id"
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                Pilih cabang tertentu atau biarkan "Semua Cabang" untuk disiarkan ke seluruh cabang.
                            </p>
                        </div>

                        <div>
                            <Select
                                label="Divisi Sasaran"
                                v-model="form.division_id"
                                :options="divisionOptions"
                                :error="form.errors.division_id"
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                Pilih divisi tertentu atau biarkan "Semua Divisi" untuk disiarkan ke seluruh divisi.
                            </p>
                        </div>
                    </div>

                    <!-- Mulai & Berakhir Tayang -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-3 border-t border-slate-100">
                        <DatePicker
                            label="Mulai Tayang"
                            v-model="form.start_date"
                            placeholder="Pilih Tanggal Mulai"
                            :error="form.errors.start_date"
                            required
                        />

                        <DatePicker
                            label="Berakhir Tayang"
                            v-model="form.end_date"
                            placeholder="Pilih Tanggal Berakhir"
                            :error="form.errors.end_date"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                <Link :href="route('owner.announcements.index')">
                    <Button variant="ghost" type="button">
                        Batal
                    </Button>
                </Link>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                >
                    Terbitkan Pengumuman
                </Button>
            </div>
        </form>
    </div>
</template>
