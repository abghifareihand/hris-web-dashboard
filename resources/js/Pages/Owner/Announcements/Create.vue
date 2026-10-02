<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    branches: Array,
    divisions: Array,
});

const form = useForm({
    title: '',
    branch_id: '',
    division_id: '',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
    content: '',
    image: null,
});

const imagePreview = ref(null);

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
};

const submit = () => {
    form.post(route('owner.announcements.store'));
};
</script>

<template>
    <Head title="Tambah Pengumuman Baru" />

    <AppLayout>
        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('owner.announcements.index')"
                        class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Tambah Pengumuman</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Buat dan terbitkan pengumuman baru untuk karyawan perusahaan.</p>
                    </div>
                </div>
                <Link
                    :href="route('owner.announcements.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                >
                    Kembali
                </Link>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Judul Pengumuman <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.title"
                            placeholder="Contoh: Pemberitahuan Libur Bersama Nasional & Penyesuaian Jam Operasional"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                            required
                        />
                        <p v-if="form.errors.title" class="text-rose-600 text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- Target: Branch & Division -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Cabang Sasaran
                            </label>
                            <select v-model="form.branch_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Cabang (Global)</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Biarkan kosong jika berlaku untuk semua cabang.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Divisi Sasaran
                            </label>
                            <select v-model="form.division_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Divisi (Global)</option>
                                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Biarkan kosong jika berlaku untuk semua divisi.</p>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Mulai Tayang <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.start_date"
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            />
                            <p v-if="form.errors.start_date" class="text-rose-600 text-xs mt-1">{{ form.errors.start_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Berakhir Tayang <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.end_date"
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            />
                            <p v-if="form.errors.end_date" class="text-rose-600 text-xs mt-1">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Isi Pengumuman <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.content"
                            rows="6"
                            placeholder="Tuliskan isi pengumuman atau instruksi yang jelas kepada seluruh karyawan..."
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"
                            required
                        ></textarea>
                        <p v-if="form.errors.content" class="text-rose-600 text-xs mt-1">{{ form.errors.content }}</p>
                    </div>

                    <!-- Image / Attachment -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Gambar Banner / Lampiran (Opsional)
                        </label>
                        <div class="flex items-start gap-4">
                            <input
                                type="file"
                                @change="onImageChange"
                                accept="image/jpeg,image/png,image/webp"
                                class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            />
                            <button
                                v-if="imagePreview"
                                type="button"
                                @click="removeImage"
                                class="text-xs text-rose-600 hover:text-rose-800 font-semibold"
                            >
                                Hapus Gambar
                            </button>
                        </div>
                        <div v-if="imagePreview" class="mt-3 max-w-sm rounded-xl overflow-hidden border border-slate-200">
                            <img :src="imagePreview" alt="Preview Gambar" class="w-full h-auto object-cover" />
                        </div>
                        <p v-if="form.errors.image" class="text-rose-600 text-xs mt-1">{{ form.errors.image }}</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <Link
                            :href="route('owner.announcements.index')"
                            class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Terbitkan Pengumuman' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
