<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/UI/Button.vue";
import Input from "@/Components/UI/Input.vue";
import Select from "@/Components/UI/Select.vue";
import DatePicker from "@/Components/UI/DatePicker.vue";
import Textarea from "@/Components/UI/Textarea.vue";

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    employee_id: "",
    date: new Date().toISOString().split("T")[0],
    amount: "",
    reason: "",
    payout_method: "payroll",
    attachment: null,
});

const fileInputRef = ref(null);
const selectedFileName = ref("");
const selectedFileSize = ref("");

const employeeOptions = computed(() => {
    return props.employees.map((emp) => ({
        value: emp.id,
        label: `${emp.name} (${emp.nip || "Tanpa NIP"})`,
    }));
});

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const onFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.attachment = file;
        selectedFileName.value = file.name;
        const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
        selectedFileSize.value = `${sizeInMb} MB`;
    } else {
        removeFile();
    }
};

const removeFile = () => {
    form.attachment = null;
    selectedFileName.value = "";
    selectedFileSize.value = "";
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};

const submit = () => {
    form.post(route("owner.finance.reimbursements.store"), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Input Klaim Biaya Baru - Frans HRIS" />

        <div class="w-full space-y-6">
            <!-- Page Header -->
            <div class="flex items-start gap-3.5 sm:gap-4">
                <Link
                    :href="route('owner.finance.reimbursements.index')"
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5"
                    title="Kembali ke Data Klaim Biaya"
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
                    <h1
                        class="text-2xl font-bold text-slate-900 tracking-tight"
                    >
                        Input Klaim Biaya Baru
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Buat pengajuan reimbursement langsung atas nama
                        karyawan.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Form Card -->
                <div
                    class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-6"
                >
                    <!-- Row 1: Nama Karyawan & Tanggal Pengeluaran -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <Select
                                label="Nama Karyawan"
                                v-model="form.employee_id"
                                :options="employeeOptions"
                                placeholder="Pilih Karyawan"
                                required
                                :error="form.errors.employee_id"
                            />
                        </div>

                        <div>
                            <DatePicker
                                label="Tanggal Pengeluaran"
                                v-model="form.date"
                                placeholder="Pilih Tanggal Pengeluaran"
                                required
                                :error="form.errors.date"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Nominal Klaim & Bukti/Struk Transaksi -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <Input
                                label="Nominal Klaim"
                                v-model="form.amount"
                                currency
                                placeholder="0"
                                required
                                :error="form.errors.amount"
                            />
                        </div>

                        <div>
                            <label class="form-label">
                                Bukti / Struk Transaksi
                                <span class="text-slate-400 font-normal"
                                    >(Opsional)</span
                                >
                            </label>

                            <!-- Hidden native file input -->
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*,application/pdf"
                                @change="onFileChange"
                                class="hidden"
                            />

                            <!-- Custom File Upload UI -->
                            <div
                                @click="triggerFileInput"
                                :class="[
                                    'w-full min-h-[40px] h-10 px-3 bg-white border rounded-lg flex items-center justify-between gap-2 cursor-pointer transition select-none',
                                    selectedFileName
                                        ? 'border-emerald-500 bg-emerald-50/20'
                                        : 'border-slate-300 hover:border-slate-400 hover:bg-slate-50/50',
                                    form.errors.attachment
                                        ? 'border-rose-400'
                                        : '',
                                ]"
                            >
                                <!-- File Info or Placeholder -->
                                <div class="flex items-center gap-2 truncate">
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
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                        />
                                    </svg>

                                    <span
                                        class="text-[13px] truncate"
                                        :class="
                                            selectedFileName
                                                ? 'text-slate-900 font-medium'
                                                : 'text-slate-400 font-normal'
                                        "
                                    >
                                        {{
                                            selectedFileName ||
                                            "Pilih struk atau nota pembayaran..."
                                        }}
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
                                        @click.stop="removeFile"
                                        class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus file terpilih"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
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
                                        class="text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-md transition"
                                    >
                                        Choose File
                                    </span>
                                </div>
                            </div>

                            <p class="text-[11px] text-slate-400 mt-1">
                                Format: JPG, PNG, atau PDF (Maksimal 5MB).
                            </p>
                            <p
                                v-if="form.errors.attachment"
                                class="form-error mt-1"
                            >
                                {{ form.errors.attachment }}
                            </p>
                        </div>
                    </div>

                    <!-- Row 3: Metode Pencairan -->
                    <div>
                        <label class="form-label">
                            Metode Pencairan
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Option 1: Payroll -->
                            <label
                                class="flex items-start p-4 rounded-xl border cursor-pointer transition select-none"
                                :class="
                                    form.payout_method === 'payroll'
                                        ? 'border-emerald-500 bg-emerald-50/50'
                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
                                "
                            >
                                <input
                                    type="radio"
                                    value="payroll"
                                    v-model="form.payout_method"
                                    class="mt-1 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div class="ml-3.5">
                                    <span
                                        class="block text-sm font-bold text-slate-900"
                                    >
                                        Cairkan Bersama Gaji Bulanan (Payroll)
                                    </span>
                                    <span
                                        class="block text-xs text-slate-500 mt-1 leading-relaxed"
                                    >
                                        Otomatis ditambahkan sebagai komponen
                                        penambah pada slip gaji periode berjalan
                                        dan dibayarkan saat penggajian.
                                    </span>
                                </div>
                            </label>

                            <!-- Option 2: Direct -->
                            <label
                                class="flex items-start p-4 rounded-xl border cursor-pointer transition select-none"
                                :class="
                                    form.payout_method === 'direct'
                                        ? 'border-emerald-500 bg-emerald-50/50'
                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
                                "
                            >
                                <input
                                    type="radio"
                                    value="direct"
                                    v-model="form.payout_method"
                                    class="mt-1 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div class="ml-3.5">
                                    <span
                                        class="block text-sm font-bold text-slate-900"
                                    >
                                        Bayar via Transfer Langsung Sekarang
                                    </span>
                                    <span
                                        class="block text-xs text-slate-500 mt-1 leading-relaxed"
                                    >
                                        Klaim ditransfer manual terpisah hari
                                        ini (di luar penggajian bulanan) dan
                                        langsung ditandai Lunas (Paid).
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Row 4: Keterangan / Keperluan Klaim -->
                    <div>
                        <Textarea
                            label="Keterangan / Keperluan Klaim"
                            v-model="form.reason"
                            rows="3"
                            placeholder="Deskripsikan tujuan dan detail pengeluaran biaya..."
                            required
                            :error="form.errors.reason"
                        />
                    </div>

                    <!-- Catatan Kuning / Amber Note -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                        <p class="text-xs text-amber-800 leading-relaxed">
                            <span class="font-bold">Catatan:</span>
                            Klaim biaya yang dibuat langsung melalui panel Owner ini akan otomatis berstatus Disetujui (Approved) dan langsung tercatat pada rekapitulasi data keuangan.
                        </p>
                    </div>
                </div>

                <!-- Submit Section (Outside Form Card) -->
                <div class="flex items-center justify-end gap-3 pt-2 pb-8">
                    <Link :href="route('owner.finance.reimbursements.index')">
                        <Button variant="ghost" type="button"> Batal </Button>
                    </Link>
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
    </AppLayout>
</template>
