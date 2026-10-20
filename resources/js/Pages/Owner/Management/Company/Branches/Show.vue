<script setup>
import { onMounted, onBeforeUnmount, ref, nextTick } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Fix default marker icon in Vite
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    branch: {
        type: Object,
        required: true,
    },
});

const mapContainer = ref(null);
let map = null;

const initMap = () => {
    if (!mapContainer.value) return;
    if (map) {
        map.remove();
        map = null;
    }

    const lat = parseFloat(props.branch.latitude);
    const lng = parseFloat(props.branch.longitude);
    const radius = parseInt(props.branch.radius) || 100;

    if (isNaN(lat) || isNaN(lng)) return;

    // Inisialisasi peta berpusat pada koordinat cabang
    map = L.map(mapContainer.value, {
        center: [lat, lng],
        zoom: 17,
        zoomControl: true,
    });

    // Tile layer OpenStreetMap
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Lingkaran radius presensi (Geofence) persis seperti referensi gambar
    // interactive: false agar tidak memunculkan kotak hitam focus outline bawaan browser
    const circle = L.circle([lat, lng], {
        radius: radius,
        color: "#059669",
        weight: 2,
        fillColor: "#10b981",
        fillOpacity: 0.25,
        interactive: false,
    }).addTo(map);

    // Marker titik pusat kantor dengan popup rapi sesuai Gambar 2
    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(`
        <div class="p-1 min-w-[210px] text-slate-800">
            <h4 class="font-bold text-[15px] text-slate-900 leading-snug tracking-tight mb-0.5">${props.branch.name}</h4>
            <span class="inline-block text-xs font-mono font-medium text-slate-400 mb-2">${props.branch.code}</span>
            <div class="border-t border-slate-100 my-2"></div>
            <p class="text-xs text-slate-600 mb-3 leading-relaxed">${props.branch.address || '-'}</p>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/90">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Radius Presensi: ${radius} meter
            </div>
        </div>
    `, {
        maxWidth: 290,
    });

    // Invalidate size agar tile merender sempurna tanpa glitch
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
            if (radius > 200) {
                map.fitBounds(circle.getBounds(), { padding: [40, 40] });
            } else {
                map.setView([lat, lng], 17);
            }
            // Buka popup secara otomatis agar rapi seperti Gambar 2
            marker.openPopup();
        }
    }, 250);
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head :title="`Detail Cabang - ${branch.name}`" />

    <div class="w-full space-y-6">
        <!-- Header (Tanpa button Edit Cabang sesuai Gambar 3) -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-start gap-3.5 sm:gap-4">
                <Link
                    :href="route('owner.management.company.branches.index')"
                    class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50 shadow-xs transition-all shrink-0 mt-0.5 cursor-pointer"
                    title="Kembali ke Daftar Cabang"
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
                        {{ branch.name }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Informasi lengkap lokasi kantor, titik koordinat, dan radius presensi karyawan.
                    </p>
                </div>
            </div>
        </div>

        <!-- 4 Stats Quick Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Karyawan -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Total Karyawan</span>
                    <span class="text-xl font-bold text-slate-800">{{ branch.employees_count || 0 }} Orang</span>
                </div>
            </div>

            <!-- 2. Radius Presensi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-width="2" d="M12 3v3m0 12v3M3 12h3m12 0h3"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Radius Presensi</span>
                    <span class="text-xl font-bold text-slate-800">{{ branch.radius }} Meter</span>
                </div>
            </div>

            <!-- 3. Zona Waktu -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Zona Waktu</span>
                    <span class="text-xl font-bold text-slate-800">{{ branch.timezone }}</span>
                </div>
            </div>

            <!-- 4. Kode Cabang -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 block">Kode Kantor</span>
                    <span class="text-xl font-bold text-slate-800">{{ branch.code }}</span>
                </div>
            </div>
        </div>

        <!-- Section 1 & Section 2 -->
        <div class="space-y-6">
            <!-- 1. Informasi Cabang -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Informasi Cabang
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <span class="text-xs font-medium text-slate-400 block">Nama Cabang</span>
                            <span class="text-sm font-semibold text-slate-800 mt-1 block">{{ branch.name }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-slate-400 block">Kode Cabang</span>
                            <span class="text-sm font-semibold text-slate-800 mt-1 block">{{ branch.code }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-slate-400 block">Zona Waktu</span>
                            <span class="text-sm font-semibold text-slate-800 mt-1 block">{{ branch.timezone }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-slate-400 block">Radius Presensi</span>
                            <span class="text-sm font-semibold text-slate-800 mt-1 block">{{ branch.radius }} Meter</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Lokasi Kantor, Titik Koordinat & Peta Radius -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="px-6 py-4 border-b border-slate-100 rounded-t-2xl">
                    <h2 class="text-base font-bold text-slate-900">
                        Lokasi Kantor & Titik Koordinat
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Leaflet OpenStreetMap with Marker & Radius Geofence -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Peta Presensi & Geofence</span>
                            <div class="flex items-center gap-4 text-xs text-slate-500">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-sky-500 inline-block"></span>
                                    Titik Pusat Kantor
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/50 border border-emerald-600 inline-block"></span>
                                    Radius Presensi ({{ branch.radius }}m)
                                </span>
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div class="relative w-full rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs bg-slate-100">
                            <div
                                ref="mapContainer"
                                class="w-full z-0"
                                style="height: 380px; min-height: 380px; width: 100%;"
                            ></div>
                        </div>

                        <!-- Radius Info Banner -->
                        <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-50/70 border border-emerald-200/80 text-xs sm:text-[0.8125rem] text-emerald-900 leading-relaxed">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>
                                Area lingkaran hijau transparan menunjukkan radius presensi karyawan (<strong class="font-bold">{{ branch.radius }} meter</strong> dari titik kantor). Di luar area ini, absensi akan otomatis ditolak.
                            </p>
                        </div>
                    </div>

                    <!-- Coordinates & Address Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-xs font-medium text-slate-400 block">Latitude</span>
                            <span class="text-sm font-semibold font-mono text-slate-800 mt-1 block select-all">
                                {{ branch.latitude || '-' }}
                            </span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-xs font-medium text-slate-400 block">Longitude</span>
                            <span class="text-sm font-semibold font-mono text-slate-800 mt-1 block select-all">
                                {{ branch.longitude || '-' }}
                            </span>
                        </div>

                        <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-200/70">
                            <span class="text-xs font-medium text-slate-400 block">Alamat Lengkap</span>
                            <p class="text-sm text-slate-800 mt-1 leading-relaxed">
                                {{ branch.address || 'Belum ada alamat lengkap yang disimpan.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Modern styling for Leaflet Zoom Control on Show Page */
.leaflet-touch .leaflet-bar,
.leaflet-bar {
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06) !important;
    background-color: #ffffff !important;
    overflow: hidden !important;
}

.leaflet-touch .leaflet-bar a,
.leaflet-bar a {
    width: 32px !important;
    height: 32px !important;
    line-height: 32px !important;
    background-color: #ffffff !important;
    color: #334155 !important;
    border: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    text-decoration: none !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    transition: background-color 0.15s ease, color 0.15s ease !important;
}

.leaflet-touch .leaflet-bar a:last-child,
.leaflet-bar a:last-child {
    border-bottom: none !important;
}

.leaflet-touch .leaflet-bar a:hover,
.leaflet-bar a:hover {
    background-color: #f8fafc !important;
    color: #0f172a !important;
}

/* Modern popup card styling matching Gambar 2 */
.leaflet-popup-content-wrapper {
    border-radius: 14px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #f1f5f9 !important;
    padding: 4px 2px !important;
}

.leaflet-popup-tip {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
}

.leaflet-popup-content {
    margin: 8px 12px !important;
    line-height: 1.4 !important;
}

.leaflet-popup-close-button {
    top: 8px !important;
    right: 10px !important;
    color: #94a3b8 !important;
    font-size: 16px !important;
}

.leaflet-popup-close-button:hover {
    color: #334155 !important;
}

/* Disable focus outline boxes on svg paths */
.leaflet-container *:focus,
.leaflet-interactive:focus,
path:focus,
svg:focus {
    outline: none !important;
}
</style>
