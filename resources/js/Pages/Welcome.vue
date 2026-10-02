<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const scrolled = ref(false);
const mobileMenuOpen = ref(false);
const activeFaq = ref(null);

const handleScroll = () => {
    scrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const plans = [
    {
        name: 'Starter',
        slug: 'starter',
        description: 'Untuk usaha rintisan dan UMKM yang baru mulai digitalisasi HR.',
        price: 'Gratis',
        period: '/selamanya',
        features: [
            'Hingga 5 karyawan',
            'Absensi GPS & Foto Selfie',
            'Manajemen cuti online',
            'Akses aplikasi mobile karyawan',
        ],
        ctaText: 'Mulai Gratis',
        ctaHref: '/login',
        popular: false,
    },
    {
        name: 'Professional',
        slug: 'professional',
        description: 'Solusi lengkap untuk perusahaan berkembang yang butuh efisiensi.',
        price: 'Rp 15.000',
        period: '/user/bulan',
        features: [
            'Karyawan tanpa batas',
            'Payroll otomatis & slip gaji digital',
            'Kalkulasi PPh 21 TER & BPJS akurat',
            'Multi-lokasi kantor & shift fleksibel',
            'Export laporan HR & Finance lengkap',
        ],
        ctaText: 'Coba 14 Hari Gratis',
        ctaHref: '/login',
        popular: true,
    },
    {
        name: 'Enterprise',
        slug: 'enterprise',
        description: 'Fitur terlengkap dengan dedicated server dan integrasi kustom.',
        price: 'Custom',
        period: '',
        features: [
            'Semua fitur Professional',
            'Dedicated Account Manager',
            'Integrasi REST API khusus',
            'SLA 99.9% uptime garansi',
            'Pelatihan & onboarding khusus tim',
        ],
        ctaText: 'Hubungi Sales',
        ctaHref: '#kontak',
        popular: false,
    },
];

const faqs = [
    {
        q: 'Apakah data saya aman di HRIS Web App?',
        a: 'Ya, keamanan data adalah prioritas utama kami. Semua data dienkripsi dengan standar industri (AES-256) dan kami menggunakan server dengan sertifikasi ISO 27001. Kami juga melakukan backup harian dan memiliki disaster recovery plan.',
    },
    {
        q: 'Bagaimana cara migrasi dari sistem payroll lama?',
        a: 'Kami menyediakan fitur import data via Excel/CSV untuk data karyawan. Tim kami juga siap membantu proses migrasi untuk paket Professional dan Enterprise. Proses migrasi biasanya selesai dalam 1-3 hari kerja.',
    },
    {
        q: 'Apakah sistem sudah sesuai regulasi pajak Indonesia?',
        a: 'Ya, sistem sudah terintegrasi dengan perhitungan PPh 21 menggunakan metode Tarif Efektif Rata-rata (TER) sesuai PMK terbaru, BPJS Kesehatan & Ketenagakerjaan, serta regulasi ketenagakerjaan Indonesia lainnya. Kami update sistem secara berkala mengikuti perubahan regulasi.',
    },
    {
        q: 'Apakah ada mobile app untuk karyawan?',
        a: 'Ya, kami menyediakan aplikasi mobile untuk karyawan (Android & iOS). Karyawan bisa melakukan clock in/out dengan GPS dan selfie, ajukan cuti, lihat slip gaji, dan ajukan reimbursement langsung dari HP mereka.',
    },
    {
        q: 'Bagaimana dukungan pelanggan HRIS Web App?',
        a: 'Semua paket mendapat dukungan email dan chat. Paket Professional mendapat prioritas response time, dan Enterprise mendapat dedicated account manager serta phone support. Tim support kami 100% berbahasa Indonesia.',
    },
];
</script>

<template>
    <Head title="HRIS Web App - Software Payroll & HRIS #1 Indonesia | Kelola Gaji Otomatis" />

    <div class="font-sans antialiased bg-white text-secondary-900 selection:bg-primary-500 selection:text-white">
        <!-- Navbar -->
        <nav
            :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm' : 'bg-transparent'"
            class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5">
                        <img src="/images/logo/logo.png" alt="HRIS Logo" class="w-10 h-10 object-contain rounded-xl shadow-xs" />
                        <span :class="scrolled ? 'text-secondary-900' : 'text-white'" class="text-xl font-bold transition-colors">
                            HRIS Web App
                        </span>
                    </Link>

                    <!-- Desktop Menu -->
                    <div class="hidden lg:flex items-center gap-8">
                        <a href="#fitur" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Fitur</a>
                        <a href="#harga" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Harga</a>
                        <a href="#tentang" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Tentang</a>
                        <a href="#kontak" :class="scrolled ? 'text-secondary-600 hover:text-primary-600' : 'text-white/90 hover:text-white'" class="font-medium transition-colors">Kontak</a>
                    </div>

                    <!-- Desktop CTA -->
                    <div class="hidden lg:flex items-center gap-3">
                        <template v-if="authUser">
                            <Link
                                :href="authUser.role === 'owner' ? route('owner.dashboard') : (authUser.role === 'admin' ? route('admin.dashboard') : route('employee.dashboard'))"
                                class="btn btn-primary flex items-center text-xs py-2 px-4 shadow-sm shadow-primary-500/30"
                            >
                                Ke Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('admin.login')"
                                class="btn btn-accent flex items-center text-xs py-2 px-4 shadow-sm shadow-accent-500/20"
                            >
                                Admin
                            </Link>
                            <Link
                                :href="route('login')"
                                class="btn btn-primary flex items-center text-xs py-2 px-4 shadow-sm shadow-primary-500/30"
                            >
                                Login
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-2 focus:outline-none"
                        :class="scrolled ? 'text-secondary-700' : 'text-white'"
                    >
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu Dropdown -->
                <div
                    v-if="mobileMenuOpen"
                    class="lg:hidden bg-white rounded-2xl shadow-xl p-6 mb-4 transition-all"
                >
                    <div class="flex flex-col gap-3">
                        <a href="#fitur" @click="mobileMenuOpen = false" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Fitur</a>
                        <a href="#harga" @click="mobileMenuOpen = false" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Harga</a>
                        <a href="#tentang" @click="mobileMenuOpen = false" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Tentang</a>
                        <a href="#kontak" @click="mobileMenuOpen = false" class="text-secondary-700 hover:text-primary-600 font-medium py-2">Kontak</a>
                        <hr class="border-secondary-200">
                        <Link
                            :href="route('admin.login')"
                            class="btn btn-accent w-full flex items-center justify-center text-sm py-2.5 shadow-sm shadow-accent-500/20"
                        >
                            Admin
                        </Link>
                        <Link
                            :href="route('login')"
                            class="btn btn-primary w-full flex items-center justify-center shadow-sm shadow-primary-500/30 py-2.5"
                        >
                            Login
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="bg-hero-gradient min-h-screen flex items-center pt-32 relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] bg-white/3 rounded-full blur-3xl"></div>
                <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10 w-full">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="text-white">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 mb-6 border border-white/10">
                            <span class="w-2 h-2 bg-accent-400 rounded-full animate-pulse"></span>
                            <span class="text-sm font-medium">Software Payroll & HRIS #1 Indonesia</span>
                        </div>

                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6 tracking-tight">
                            Kelola Gaji & HR<br>
                            <span class="text-primary-300">Jadi Lebih Mudah</span>
                        </h1>

                        <p class="text-lg md:text-xl text-primary-100 mb-8 max-w-xl leading-relaxed">
                            Solusi lengkap penggajian, BPJS, kehadiran GPS, dan manajemen cuti. Digunakan oleh <strong>500+ perusahaan</strong> di Indonesia.
                        </p>

                        <!-- Mobile App Download -->
                        <div class="mb-8 max-sm:text-center">
                            <a
                                href="https://www.adrprogramming.com/"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-3 bg-secondary-800/80 hover:bg-secondary-900 border border-white/20 text-white text-sm font-medium py-3 px-5 rounded-xl transition-all shadow-lg"
                            >
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#34A853"/>
                                    <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                                    <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                                    <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                                </svg>
                                <span>
                                    <span class="block text-white/70 text-xs">Tersedia di</span>
                                    <span class="block font-semibold">Google Play</span>
                                </span>
                                <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 text-sm text-primary-200">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Tanpa kartu kredit
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Setup 5 menit
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Support Indonesia
                            </div>
                        </div>
                    </div>

                    <!-- Right - Dashboard Preview -->
                    <div class="relative">
                        <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-2 transform lg:rotate-1 hover:rotate-0 transition-transform duration-500 border border-white/20">
                            <!-- Dashboard Illustration Mockup -->
                            <div class="rounded-xl overflow-hidden bg-slate-50 border border-slate-200">
                                <div class="bg-slate-100 px-4 py-2.5 flex items-center gap-2 border-b border-slate-200">
                                    <div class="w-2.5 h-2.5 rounded-full bg-danger-400"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-warning-400"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-success-400"></div>
                                    <div class="text-[11px] text-slate-400 font-mono ml-2">app.hris-saas.id/owner/dashboard</div>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs">
                                            <div class="text-[11px] text-slate-500">Total Karyawan</div>
                                            <div class="text-xl font-bold text-slate-800">142 Org</div>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs">
                                            <div class="text-[11px] text-slate-500">Hadir Hari Ini</div>
                                            <div class="text-xl font-bold text-primary-600">138 (97%)</div>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs">
                                            <div class="text-[11px] text-slate-500">Total Payroll</div>
                                            <div class="text-xl font-bold text-slate-800">Rp 480Jt</div>
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-xs">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-slate-700">Grafik Kehadiran Mingguan</span>
                                            <span class="badge badge-success">Live</span>
                                        </div>
                                        <div class="h-24 bg-gradient-to-r from-primary-50 via-emerald-50 to-primary-100 rounded flex items-end justify-between px-4 pb-2 pt-6">
                                            <div class="w-6 bg-primary-400 rounded-t h-12"></div>
                                            <div class="w-6 bg-primary-500 rounded-t h-16"></div>
                                            <div class="w-6 bg-primary-600 rounded-t h-20"></div>
                                            <div class="w-6 bg-primary-500 rounded-t h-18"></div>
                                            <div class="w-6 bg-primary-600 rounded-t h-20"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 w-24 h-24 bg-accent-500/20 rounded-full blur-2xl"></div>
                        <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-primary-400/20 rounded-full blur-3xl"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-12 bg-white border-b border-secondary-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">500+</div>
                        <div class="text-secondary-600 text-sm">Perusahaan Aktif*</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">50.000+</div>
                        <div class="text-secondary-600 text-sm">Karyawan Dikelola*</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">99.9%</div>
                        <div class="text-secondary-600 text-sm">Uptime Server*</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-primary-600 mb-1">4.8/5</div>
                        <div class="text-secondary-600 text-sm">Rating Pengguna*</div>
                    </div>
                </div>
                <p class="text-center text-xs text-secondary-400 mt-6">*Data ilustrasi untuk keperluan demo</p>
            </div>
        </section>

        <!-- Video Demo Section -->
        <section class="py-16 lg:py-24 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10">
                    <span class="inline-flex items-center gap-2 bg-danger-100 text-danger-700 text-sm font-semibold px-4 py-2 rounded-full mb-4">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        Video Demo
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Demo Aplikasi
                    </h2>
                    <p class="text-lg text-secondary-600 max-w-2xl mx-auto">
                        Tonton demo lengkap fitur-fitur HRIS Web App dan cara penggunaannya
                    </p>
                </div>

                <div class="relative rounded-2xl overflow-hidden shadow-2xl bg-secondary-900">
                    <div class="aspect-video">
                        <iframe
                            src="https://www.youtube.com/embed/fOK4I0fk8Wg"
                            title="HRIS Web App Demo - Event dan Cara Pakainya"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                            class="w-full h-full"
                        ></iframe>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="fitur" class="py-20 lg:py-32 bg-slate-50/50 border-t border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Fitur Lengkap</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Semua yang Anda Butuhkan<br>dalam Satu Platform
                    </h2>
                    <p class="text-lg text-secondary-600">
                        Fitur lengkap untuk mengelola HR dan payroll perusahaan Anda dengan efisien. Hemat waktu hingga 80%.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Feature 1: Payroll -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-accent-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-accent-500 transition-colors">
                            <svg class="w-7 h-7 text-accent-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Payroll Otomatis</h3>
                        <p class="text-secondary-600 text-sm">Hitung gaji, PPh 21 TER, BPJS TK & Kesehatan, THR, lembur dalam hitungan menit.</p>
                    </div>

                    <!-- Feature 2: Kehadiran GPS -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-success-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-success-500 transition-colors">
                            <svg class="w-7 h-7 text-success-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Kehadiran GPS</h3>
                        <p class="text-secondary-600 text-sm">Clock in/out dengan GPS, selfie, validasi radius kantor. Support multiple lokasi.</p>
                    </div>

                    <!-- Feature 3: Absensi Foto Selfie -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-info-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-info-500 transition-colors">
                            <svg class="w-7 h-7 text-info-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Absensi Foto Selfie</h3>
                        <p class="text-secondary-600 text-sm">Bukti kehadiran autentik dengan pengambilan foto kamera langsung saat clock in dan clock out.</p>
                    </div>

                    <!-- Feature 4: Manajemen Cuti -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-warning-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-warning-500 transition-colors">
                            <svg class="w-7 h-7 text-warning-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Manajemen Cuti</h3>
                        <p class="text-secondary-600 text-sm">Pengajuan online, approval workflow, tracking saldo cuti real-time.</p>
                    </div>

                    <!-- Feature 5: Employee Database -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-primary-500 transition-colors">
                            <svg class="w-7 h-7 text-primary-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Employee Database</h3>
                        <p class="text-secondary-600 text-sm">Data karyawan lengkap, dokumen, kontrak, riwayat karir dalam satu tempat.</p>
                    </div>

                    <!-- Feature 6: Slip Gaji Digital -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-danger-50 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-danger-500 transition-colors">
                            <svg class="w-7 h-7 text-danger-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Slip Gaji Digital</h3>
                        <p class="text-secondary-600 text-sm">Generate slip gaji PDF otomatis. Karyawan akses via mobile app.</p>
                    </div>

                    <!-- Feature 7: Mobile App -->
                    <div class="bg-gradient-to-br from-success-50 to-emerald-50 rounded-2xl p-6 border-2 border-success-200 hover:border-success-300 hover:shadow-lg transition-all group relative overflow-hidden">
                        <div class="absolute top-2 right-2">
                            <span class="bg-success-500 text-white text-[10px] font-bold px-2 py-1 rounded-full">LIVE DI PLAY STORE</span>
                        </div>
                        <div class="w-14 h-14 bg-success-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-success-500 transition-colors">
                            <svg class="w-7 h-7 text-success-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Mobile App</h3>
                        <p class="text-secondary-600 text-sm mb-4">Aplikasi karyawan untuk Android. Absensi GPS, foto selfie, cuti, payslip dari HP.</p>
                        <a
                            href="https://www.adrprogramming.com/"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 bg-secondary-900 hover:bg-black text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92z" fill="#4285F4"/>
                                <path d="M14.499 12.707l2.302 2.302-10.937 6.333 8.635-8.635z" fill="#FBBC04"/>
                                <path d="M5.864 2.658L16.8 9.99l-2.302 2.302-8.634-8.634z" fill="#EA4335"/>
                                <path d="M17.698 10.509l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491z" fill="#34A853"/>
                            </svg>
                            Google Play
                        </a>
                    </div>

                    <!-- Feature 8: Career & Performance -->
                    <div class="bg-white rounded-2xl p-6 border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all group">
                        <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mb-5 group-hover:bg-primary-500 transition-colors">
                            <svg class="w-7 h-7 text-primary-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-secondary-900 mb-2">Career & Performance</h3>
                        <p class="text-secondary-600 text-sm">Jalur karir, KPI, performance review, promosi & mutasi karyawan.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose Us Section -->
        <section id="tentang" class="py-20 lg:py-32 bg-secondary-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Mengapa HRIS Web App?</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-6">
                            Software Payroll yang Dibuat Khusus untuk Indonesia
                        </h2>
                        <p class="text-lg text-secondary-600 mb-8">
                            Berbeda dengan software payroll asing, HRIS Web App dirancang khusus untuk regulasi Indonesia termasuk PPh 21 TER, BPJS Kesehatan & Ketenagakerjaan, THR, dan kepatuhan pajak.
                        </p>

                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900 mb-1">PPh 21 Tarif Efektif Rata-rata (TER)</h4>
                                    <p class="text-secondary-600 text-sm">Otomatis menghitung pajak sesuai regulasi PMK terbaru. Update berkala.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900 mb-1">BPJS Terintegrasi</h4>
                                    <p class="text-secondary-600 text-sm">Hitung iuran BPJS TK (JHT, JKK, JKM, JP) & Kesehatan otomatis.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900 mb-1">Support Bahasa Indonesia</h4>
                                    <p class="text-secondary-600 text-sm">Tim support lokal yang siap membantu via chat, email, dan telepon.</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-success-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-secondary-900 mb-1">Server di Indonesia</h4>
                                    <p class="text-secondary-600 text-sm">Data tersimpan di data center Indonesia untuk kecepatan dan kepatuhan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="bg-white rounded-2xl shadow-xl p-8 border border-secondary-100">
                            <div class="flex items-center justify-between mb-6">
                                <h4 class="font-semibold text-secondary-900">Perbandingan Waktu Proses</h4>
                            </div>
                            <div class="space-y-6">
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-secondary-600">Manual (Excel)</span>
                                        <span class="font-semibold text-secondary-900">2-3 hari</span>
                                    </div>
                                    <div class="h-3 bg-secondary-200 rounded-full">
                                        <div class="h-3 bg-danger-500 rounded-full" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-secondary-600">Software Lain</span>
                                        <span class="font-semibold text-secondary-900">4-8 jam</span>
                                    </div>
                                    <div class="h-3 bg-secondary-200 rounded-full">
                                        <div class="h-3 bg-warning-500 rounded-full" style="width: 50%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-primary-600 font-semibold">HRIS Web App</span>
                                        <span class="font-bold text-primary-600">15 menit</span>
                                    </div>
                                    <div class="h-3 bg-primary-100 rounded-full">
                                        <div class="h-3 bg-primary-500 rounded-full" style="width: 10%"></div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-sm text-secondary-500 mt-6 text-center">*Untuk 100 karyawan</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="py-20 lg:py-32 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Mudah Dimulai</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Mulai dalam 3 Langkah Mudah
                    </h2>
                    <p class="text-lg text-secondary-600">
                        Setup hanya membutuhkan beberapa menit saja. Tanpa instalasi rumit.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-start justify-center gap-8 md:gap-4 lg:gap-8">
                    <!-- Step 1 -->
                    <div class="text-center flex-1 max-w-xs">
                        <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-md shadow-primary-500/30">1</div>
                        <h3 class="text-xl font-semibold text-secondary-900 mb-3">Daftar Akun</h3>
                        <p class="text-secondary-600 text-sm">Buat akun gratis dalam 30 detik. Tanpa kartu kredit diperlukan.</p>
                    </div>

                    <!-- Arrow 1 -->
                    <div class="hidden md:flex items-center justify-center pt-6">
                        <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center flex-1 max-w-xs">
                        <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-md shadow-primary-500/30">2</div>
                        <h3 class="text-xl font-semibold text-secondary-900 mb-3">Setup Perusahaan</h3>
                        <p class="text-secondary-600 text-sm">Atur profil perusahaan, struktur organisasi, dan pengaturan payroll.</p>
                    </div>

                    <!-- Arrow 2 -->
                    <div class="hidden md:flex items-center justify-center pt-6">
                        <svg class="w-8 h-8 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center flex-1 max-w-xs">
                        <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-6 shadow-md shadow-primary-500/30">3</div>
                        <h3 class="text-xl font-semibold text-secondary-900 mb-3">Undang Tim</h3>
                        <p class="text-secondary-600 text-sm">Undang karyawan untuk mulai gunakan aplikasi mobile HRIS Web App.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-20 lg:py-32 bg-secondary-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Testimoni</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Testimoni Pengguna*
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Testimonial 1 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-secondary-100">
                        <div class="flex items-center gap-1 mb-4 text-warning-400">
                            <svg v-for="i in 5" :key="i" class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <p class="text-secondary-600 mb-6 text-sm leading-relaxed">
                            "Sebelumnya butuh 2 hari untuk proses gaji 150 karyawan. Sekarang dengan HRIS Web App cuma 30 menit! PPh 21 dan BPJS otomatis terhitung."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold">RS</div>
                            <div>
                                <div class="font-semibold text-secondary-900">Rina Susanti</div>
                                <div class="text-sm text-secondary-500">HR Manager, PT Maju Bersama</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-secondary-100">
                        <div class="flex items-center gap-1 mb-4 text-warning-400">
                            <svg v-for="i in 5" :key="i" class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <p class="text-secondary-600 mb-6 text-sm leading-relaxed">
                            "Absensi dengan foto selfie dan GPS sangat membantu mencegah titip absen. Sekarang data kehadiran 100% akurat dan real-time."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center text-success-600 font-bold">BW</div>
                            <div>
                                <div class="font-semibold text-secondary-900">Budi Wijaya</div>
                                <div class="text-sm text-secondary-500">CEO, Startup Teknologi</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-secondary-100">
                        <div class="flex items-center gap-1 mb-4 text-warning-400">
                            <svg v-for="i in 5" :key="i" class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <p class="text-secondary-600 mb-6 text-sm leading-relaxed">
                            "Karyawan saya senang bisa akses slip gaji dan ajukan cuti dari HP. Support team juga sangat responsif dan helpful."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-accent-100 rounded-full flex items-center justify-center text-accent-600 font-bold">AD</div>
                            <div>
                                <div class="font-semibold text-secondary-900">Anita Dewi</div>
                                <div class="text-sm text-secondary-500">Finance Director, PT Retail Indonesia</div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center text-xs text-secondary-400 mt-8">*Testimoni ilustrasi untuk keperluan demo</p>
            </div>
        </section>

        <!-- Pricing Preview -->
        <section id="harga" class="py-20 lg:py-32 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">Harga</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Harga Transparan,<br>Tanpa Biaya Tersembunyi
                    </h2>
                    <p class="text-lg text-secondary-600">
                        Pilih paket yang sesuai dengan kebutuhan bisnis Anda. Semua paket termasuk support.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <!-- Professional (Popular) -->
                    <div
                        v-for="plan in plans"
                        :key="plan.slug"
                        :class="[
                            plan.popular
                                ? 'bg-primary-600 rounded-2xl p-8 text-white relative transform md:-translate-y-4 shadow-xl'
                                : 'bg-white rounded-2xl p-8 border border-secondary-200 hover:border-primary-300 transition-colors'
                        ]"
                    >
                        <div
                            v-if="plan.popular"
                            class="absolute -top-4 left-1/2 -translate-x-1/2 bg-accent-500 text-white text-xs font-semibold px-4 py-1 rounded-full uppercase tracking-wider"
                        >
                            PALING POPULER
                        </div>

                        <h3 :class="plan.popular ? 'text-white' : 'text-secondary-900'" class="text-xl font-bold mb-2">
                            {{ plan.name }}
                        </h3>
                        <p :class="plan.popular ? 'text-primary-200' : 'text-secondary-500'" class="text-sm mb-6">
                            {{ plan.description }}
                        </p>

                        <div class="mb-6">
                            <span :class="plan.popular ? 'text-white' : 'text-secondary-900'" class="text-4xl font-bold">
                                {{ plan.price }}
                            </span>
                            <span :class="plan.popular ? 'text-primary-200' : 'text-secondary-500'" class="text-sm ml-1">
                                {{ plan.period }}
                            </span>
                        </div>

                        <ul class="space-y-3 mb-8 text-sm">
                            <li
                                v-for="(feat, idx) in plan.features"
                                :key="idx"
                                :class="plan.popular ? 'text-primary-100' : 'text-secondary-600'"
                                class="flex items-center gap-3"
                            >
                                <svg
                                    :class="plan.popular ? 'text-primary-200' : 'text-success-500'"
                                    class="w-5 h-5 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ feat }}</span>
                            </li>
                        </ul>

                        <Link
                            :href="plan.ctaHref"
                            :class="plan.popular ? 'btn btn-white w-full shadow-md' : 'btn btn-secondary w-full'"
                        >
                            {{ plan.ctaText }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-20 lg:py-32 bg-secondary-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="inline-block bg-primary-100 text-primary-700 text-sm font-semibold px-4 py-1.5 rounded-full mb-4">FAQ</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-secondary-900 mb-4">
                        Pertanyaan yang Sering Diajukan
                    </h2>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="bg-white rounded-2xl border border-secondary-200 overflow-hidden"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="w-full px-6 py-5 text-left flex items-center justify-between font-semibold text-secondary-900 hover:text-primary-600 transition"
                        >
                            <span>{{ faq.q }}</span>
                            <svg
                                :class="activeFaq === idx ? 'rotate-180 text-primary-600' : 'text-secondary-400'"
                                class="w-5 h-5 transition-transform flex-shrink-0 ml-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-show="activeFaq === idx" class="px-6 pb-5 border-t border-secondary-100 text-secondary-600 text-sm leading-relaxed pt-3">
                            <p>{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section id="kontak" class="py-20 lg:py-32 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-primary-600 rounded-3xl p-8 md:p-16 text-center text-white relative overflow-hidden shadow-2xl">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

                    <div class="relative z-10">
                        <h2 class="text-3xl md:text-4xl font-bold mb-4">
                            Siap Tingkatkan Efisiensi HR Anda?
                        </h2>
                        <p class="text-lg text-white/80 mb-8 max-w-xl mx-auto leading-relaxed">
                            Bergabung dengan 500+ perusahaan yang sudah menghemat waktu dan biaya dengan HRIS Web App.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link
                                :href="route('login')"
                                class="btn bg-white text-primary-600 hover:bg-white/90 text-base py-4 px-8 font-semibold shadow-lg"
                            >
                                Daftar Gratis Sekarang
                            </Link>
                            <a
                                href="#kontak"
                                class="btn border-2 border-white/50 text-white hover:bg-white/10 text-base py-4 px-8"
                            >
                                Jadwalkan Demo
                            </a>
                        </div>
                        <p class="text-sm text-white/60 mt-6">Tanpa kartu kredit. Setup 5 menit. Batalkan kapan saja.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-secondary-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-12">
                    <!-- Brand -->
                    <div class="col-span-2 md:col-span-4 lg:col-span-1">
                        <Link href="/" class="flex items-center gap-2 mb-4">
                            <img src="/images/logo/logo.png" alt="HRIS Logo" class="w-10 h-10 object-contain rounded-xl bg-white/10 p-1" />
                            <span class="text-xl font-bold">HRIS Web App</span>
                        </Link>
                        <p class="text-secondary-400 text-sm mb-6">Solusi HR & Payroll terlengkap untuk bisnis Indonesia.</p>
                        <div class="flex gap-3">
                            <a href="#" class="w-9 h-9 bg-secondary-800 hover:bg-primary-600 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="w-9 h-9 bg-secondary-800 hover:bg-primary-600 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" class="w-9 h-9 bg-secondary-800 hover:bg-primary-600 rounded-lg flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Produk -->
                    <div>
                        <h4 class="font-semibold text-white mb-4">Produk</h4>
                        <ul class="space-y-3">
                            <li><a href="#fitur" class="text-secondary-400 hover:text-white transition-colors text-sm">Fitur</a></li>
                            <li><a href="#harga" class="text-secondary-400 hover:text-white transition-colors text-sm">Harga</a></li>
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">Integrasi</a></li>
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">API Docs</a></li>
                        </ul>
                    </div>

                    <!-- Perusahaan -->
                    <div>
                        <h4 class="font-semibold text-white mb-4">Perusahaan</h4>
                        <ul class="space-y-3">
                            <li><a href="#tentang" class="text-secondary-400 hover:text-white transition-colors text-sm">Tentang Kami</a></li>
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">Blog</a></li>
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">Karir</a></li>
                            <li><a href="#kontak" class="text-secondary-400 hover:text-white transition-colors text-sm">Kontak</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h4 class="font-semibold text-white mb-4">Legal</h4>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">Kebijakan Privasi</a></li>
                            <li><a href="#" class="text-secondary-400 hover:text-white transition-colors text-sm">Syarat & Ketentuan</a></li>
                        </ul>
                    </div>

                    <!-- Kontak -->
                    <div>
                        <h4 class="font-semibold text-white mb-4">Kontak</h4>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-2 text-secondary-400 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                hello@hris-app.com
                            </li>
                            <li class="flex items-center gap-2 text-secondary-400 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                +62 21 1234 5678
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="border-t border-secondary-800 mt-12 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-secondary-500 text-sm">&copy; {{ new Date().getFullYear() }} HRIS Web App. All rights reserved.</p>
                    <p class="text-secondary-500 text-sm">
                        Powered by <a href="https://adrprogramming.com" target="_blank" rel="noopener noreferrer" class="text-primary-400 hover:text-primary-300 transition-colors font-medium">adrprogramming.com</a>
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
