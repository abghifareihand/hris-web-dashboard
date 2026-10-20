<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Fix Leaflet's default marker icon paths in Vite
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const props = defineProps({
    latitude: {
        type: [String, Number],
        default: "",
    },
    longitude: {
        type: [String, Number],
        default: "",
    },
    zoom: {
        type: Number,
        default: 13,
    },
    height: {
        type: String,
        default: "340px",
    },
});

const emit = defineEmits([
    "update:latitude",
    "update:longitude",
    "addressResolved",
    "change",
]);

const mapContainer = ref(null);
let map = null;
let marker = null;
const isLocating = ref(false);
const isGeocoding = ref(false);

// Default coordinates: Monas / Jakarta Pusat
const DEFAULT_LAT = -6.200000;
const DEFAULT_LNG = 106.816666;

// Reverse geocode via OpenStreetMap Nominatim
const reverseGeocode = async (lat, lng) => {
    isGeocoding.value = true;
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
            {
                headers: {
                    "Accept-Language": "id,en",
                },
            }
        );
        if (response.ok) {
            const data = await response.json();
            if (data && data.display_name) {
                emit("addressResolved", data.display_name);
            }
        }
    } catch (error) {
        console.error("Gagal mengambil alamat dari OpenStreetMap:", error);
    } finally {
        isGeocoding.value = false;
    }
};

const setCoordinates = (lat, lng, shouldGeocode = true) => {
    const latStr = String(lat);
    const lngStr = String(lng);

    emit("update:latitude", latStr);
    emit("update:longitude", lngStr);
    emit("change", { lat: latStr, lng: lngStr });

    if (!marker) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        marker.on("dragend", (e) => {
            const newPos = e.target.getLatLng();
            setCoordinates(newPos.lat, newPos.lng, true);
        });
    } else {
        marker.setLatLng([lat, lng]);
    }

    if (shouldGeocode) {
        reverseGeocode(lat, lng);
    }
};

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert("Browser Anda tidak mendukung deteksi lokasi (Geolocation).");
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            isLocating.value = false;
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            map.setView([lat, lng], 16);
            setCoordinates(lat, lng, true);
        },
        (err) => {
            isLocating.value = false;
            console.warn("Gagal mendapatkan lokasi GPS:", err.message);
            alert("Tidak dapat mendeteksi lokasi saat ini. Pastikan izin lokasi aktif.");
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

onMounted(() => {
    nextTick(() => {
        if (!mapContainer.value) return;

        const hasInitialPos =
            props.latitude &&
            props.longitude &&
            !isNaN(parseFloat(props.latitude)) &&
            !isNaN(parseFloat(props.longitude));

        const initialLat = hasInitialPos ? parseFloat(props.latitude) : DEFAULT_LAT;
        const initialLng = hasInitialPos ? parseFloat(props.longitude) : DEFAULT_LNG;

        // Initialize Leaflet Map
        map = L.map(mapContainer.value, {
            center: [initialLat, initialLng],
            zoom: hasInitialPos ? 16 : props.zoom,
            zoomControl: true,
        });

        // OpenStreetMap Tile Layer
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        // Custom Locate Control (crosshair button matching screenshot)
        const LocateControl = L.Control.extend({
            options: { position: "topleft" },
            onAdd: function () {
                const container = L.DomUtil.create(
                    "div",
                    "leaflet-control-locate leaflet-bar leaflet-control"
                );
                const link = L.DomUtil.create(
                    "a",
                    "leaflet-bar-part",
                    container
                );
                link.href = "#";
                link.title = "Lokasi Saya Saat Ini";
                link.setAttribute("role", "button");
                link.setAttribute("aria-label", "Lokasi Saya Saat Ini");
                link.innerHTML = `
                    <svg class="w-4 h-4 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="12" cy="12" r="7" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3"/>
                        <circle cx="12" cy="12" r="2" fill="currentColor"/>
                    </svg>
                `;

                L.DomEvent.disableClickPropagation(container);
                L.DomEvent.on(link, "click", (e) => {
                    L.DomEvent.stop(e);
                    getCurrentLocation();
                });
                return container;
            },
        });
        map.addControl(new LocateControl());

        // Place initial marker if coordinates provided
        if (hasInitialPos) {
            marker = L.marker([initialLat, initialLng], {
                draggable: true,
            }).addTo(map);

            marker.on("dragend", (e) => {
                const newPos = e.target.getLatLng();
                setCoordinates(newPos.lat, newPos.lng, true);
            });
        }

        // Map Click Listener
        map.on("click", (e) => {
            const { lat, lng } = e.latlng;
            setCoordinates(lat, lng, true);
        });

        // Trigger map resize after mounting
        setTimeout(() => {
            map.invalidateSize();
        }, 200);
    });
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

// Watch for external coordinate updates (e.g. if edited externally)
watch(
    () => [props.latitude, props.longitude],
    ([newLat, newLng]) => {
        if (!map) return;
        const lat = parseFloat(newLat);
        const lng = parseFloat(newLng);
        if (!isNaN(lat) && !isNaN(lng)) {
            if (marker) {
                const cur = marker.getLatLng();
                if (cur.lat !== lat || cur.lng !== lng) {
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng], map.getZoom());
                }
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on("dragend", (e) => {
                    const newPos = e.target.getLatLng();
                    setCoordinates(newPos.lat, newPos.lng, true);
                });
                map.setView([lat, lng], 16);
            }
        }
    }
);
</script>

<template>
    <div class="w-full">
        <label class="form-label form-label-required block mb-2">
            Peta Lokasi
        </label>

        <!-- Map Container -->
        <div class="relative w-full rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs bg-slate-100">
            <div
                ref="mapContainer"
                class="w-full z-0"
                :style="{ height: height }"
            ></div>

            <!-- Loading overlay when reverse geocoding -->
            <transition
                enter-active-class="transition-opacity duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isGeocoding || isLocating"
                    class="absolute top-3 right-3 z-10 px-3 py-1.5 rounded-lg bg-white/95 backdrop-blur-xs border border-slate-200 shadow-sm flex items-center gap-2 text-xs font-medium text-slate-700"
                >
                    <svg class="w-3.5 h-3.5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>{{ isLocating ? 'Mendeteksi lokasi...' : 'Mengambil alamat...' }}</span>
                </div>
            </transition>
        </div>

        <!-- Helper caption below map matching screenshot -->
        <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1.5 select-none">
            Klik pada peta untuk menentukan lokasi presensi secara presisi
        </p>
    </div>
</template>

<style>
/* Modern unified styling for all Leaflet controls */
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

.leaflet-control-locate {
    margin-top: 8px !important;
}
</style>
