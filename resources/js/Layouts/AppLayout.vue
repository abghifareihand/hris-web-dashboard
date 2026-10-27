<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import OwnerSidebar from '@/Layouts/Sidebars/OwnerSidebar.vue';
import EmployeeSidebar from '@/Layouts/Sidebars/EmployeeSidebar.vue';
import AdminSidebar from '@/Layouts/Sidebars/AdminSidebar.vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const companyName = computed(() => authUser.value?.company?.name || authUser.value?.company?.name_company || '');
const unreadNotificationsCount = computed(() => page.props.auth?.unreadNotificationsCount || 0);
const recentNotifications = computed(() => page.props.auth?.recentNotifications || []);
const appName = computed(() => page.props.appName || 'ADR HRIS');
const flash = computed(() => page.props.flash || {});

// Toast Notification State & Auto-Dismiss Logic
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
let toastTimer = null;
let startTime = 0;
let remainingTime = 4000;
const progress = ref(100);
let progressInterval = null;
let pollInterval = null;
let isPolling = false;

const startProgress = (duration) => {
    progress.value = 100;
    if (progressInterval) clearInterval(progressInterval);
    const intervalTime = 50;
    const step = (intervalTime / duration) * 100;
    progressInterval = setInterval(() => {
        progress.value = Math.max(0, progress.value - step);
        if (progress.value <= 0) {
            clearInterval(progressInterval);
        }
    }, intervalTime);
};

const triggerToast = (msg, type = 'success') => {
    if (!msg) return;
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    remainingTime = 4000;
    startTime = Date.now();

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, remainingTime);

    startProgress(remainingTime);
};

const dismissToast = () => {
    showToast.value = false;
    if (toastTimer) clearTimeout(toastTimer);
    if (progressInterval) clearInterval(progressInterval);
};

const pauseToast = () => {
    if (toastTimer) {
        clearTimeout(toastTimer);
        remainingTime = Math.max(500, remainingTime - (Date.now() - startTime));
    }
    if (progressInterval) clearInterval(progressInterval);
};

const resumeToast = () => {
    if (!showToast.value || remainingTime <= 0) return;
    startTime = Date.now();
    toastTimer = setTimeout(() => {
        showToast.value = false;
    }, remainingTime);
    startProgress(remainingTime);
};

let lastFlashMessage = null;

watch(
    () => page.props.flash,
    (newFlash) => {
        if (isPolling) return;

        const currentMsg = newFlash?.success || newFlash?.error || newFlash?.warning || newFlash?.info;
        if (!currentMsg) {
            lastFlashMessage = null;
            return;
        }

        if (currentMsg === lastFlashMessage) return;
        lastFlashMessage = currentMsg;

        if (newFlash?.success) {
            triggerToast(newFlash.success, 'success');
        } else if (newFlash?.error) {
            triggerToast(newFlash.error, 'error');
        } else if (newFlash?.warning) {
            triggerToast(newFlash.warning, 'warning');
        } else if (newFlash?.info) {
            triggerToast(newFlash.info, 'info');
        }
    },
    { deep: true, immediate: true }
);

const sidebarOpen = ref(false);
const desktopSidebarOpen = ref(true);
const isUserDropdownOpen = ref(false);
const userDropdownRef = ref(null);
const isNotificationDropdownOpen = ref(false);
const notificationDropdownRef = ref(null);

// Incoming Approval Alert Notification (Distinct from CRUD Toast)
const showApprovalAlert = ref(false);
const currentApprovalAlert = ref(null);
const approvalProgress = ref(100);
let approvalTimer = null;
let approvalProgressInterval = null;
let approvalRemainingTime = 8000;
let approvalStartTime = 0;
let isInitialNotifCheckDone = false;

let notificationAudio = null;

const initAudio = () => {
    if (!notificationAudio && typeof window !== 'undefined') {
        notificationAudio = document.getElementById('approvalNotificationSound') || new Audio('/sounds/notification.wav');
        notificationAudio.volume = 0.9;
    }
};

const playSynthChime = () => {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const now = ctx.currentTime;

        // Tone 1: E5 (659.25 Hz)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(659.25, now);
        gain1.gain.setValueAtTime(0.45, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.3);

        // Tone 2: B5 (987.77 Hz)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(987.77, now + 0.12);
        gain2.gain.setValueAtTime(0.5, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.55);
    } catch {
        // Safe fallback
    }
};

const playAlertSound = () => {
    try {
        initAudio();
        if (notificationAudio) {
            notificationAudio.currentTime = 0;
            const playPromise = notificationAudio.play();
            if (playPromise !== undefined) {
                playPromise.catch(() => {
                    playSynthChime();
                });
            }
        } else {
            playSynthChime();
        }
    } catch {
        playSynthChime();
    }
};

const unlockAudio = () => {
    initAudio();
    document.removeEventListener('click', unlockAudio);
    document.removeEventListener('keydown', unlockAudio);
};

const getSeenNotifIds = () => {
    try {
        return new Set(JSON.parse(sessionStorage.getItem('hris_seen_approval_ids') || '[]'));
    } catch {
        return new Set();
    }
};

const markNotifAsSeen = (id) => {
    try {
        const seen = getSeenNotifIds();
        seen.add(id);
        sessionStorage.setItem('hris_seen_approval_ids', JSON.stringify([...seen]));
    } catch {}
};

const startApprovalProgress = (duration) => {
    approvalProgress.value = 100;
    if (approvalProgressInterval) clearInterval(approvalProgressInterval);
    const intervalTime = 50;
    const step = (intervalTime / duration) * 100;
    approvalProgressInterval = setInterval(() => {
        approvalProgress.value = Math.max(0, approvalProgress.value - step);
        if (approvalProgress.value <= 0) {
            clearInterval(approvalProgressInterval);
        }
    }, intervalTime);
};

const triggerApprovalAlert = (notif) => {
    if (!notif) return;
    markNotifAsSeen(notif.id);
    currentApprovalAlert.value = notif;
    showApprovalAlert.value = true;
    approvalRemainingTime = 8000;
    approvalStartTime = Date.now();

    playAlertSound();

    if (approvalTimer) clearTimeout(approvalTimer);
    approvalTimer = setTimeout(() => {
        showApprovalAlert.value = false;
    }, approvalRemainingTime);

    startApprovalProgress(approvalRemainingTime);
};

const dismissApprovalAlert = () => {
    showApprovalAlert.value = false;
    if (approvalTimer) clearTimeout(approvalTimer);
    if (approvalProgressInterval) clearInterval(approvalProgressInterval);
};

const pauseApprovalAlert = () => {
    if (approvalTimer) {
        clearTimeout(approvalTimer);
        approvalRemainingTime = Math.max(500, approvalRemainingTime - (Date.now() - approvalStartTime));
    }
    if (approvalProgressInterval) clearInterval(approvalProgressInterval);
};

const resumeApprovalAlert = () => {
    if (!showApprovalAlert.value || approvalRemainingTime <= 0) return;
    approvalStartTime = Date.now();
    approvalTimer = setTimeout(() => {
        showApprovalAlert.value = false;
    }, approvalRemainingTime);
    startApprovalProgress(approvalRemainingTime);
};

const checkPendingApprovalsNotification = () => {
    if (authUser.value?.role !== 'owner') return;
    const notifs = recentNotifications.value || [];
    const seen = getSeenNotifIds();

    const candidate = notifs.find(n => {
        if (!n || n.read_at) return false;
        if (seen.has(n.id)) return false;
        return true;
    });

    if (candidate) {
        triggerApprovalAlert(candidate);
    }
    isInitialNotifCheckDone = true;
};

watch(
    () => page.props.auth?.recentNotifications,
    () => {
        checkPendingApprovalsNotification();
    },
    { deep: true }
);

const markAllNotificationsRead = () => {
    router.post(route('owner.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

const handleGlobalClick = (event) => {
    if (isUserDropdownOpen.value && userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
        isUserDropdownOpen.value = false;
    }
    if (isNotificationDropdownOpen.value && notificationDropdownRef.value && !notificationDropdownRef.value.contains(event.target)) {
        isNotificationDropdownOpen.value = false;
    }
};

const handleGlobalKeydown = (event) => {
    if (event.key === 'Escape') {
        if (isUserDropdownOpen.value) isUserDropdownOpen.value = false;
        if (isNotificationDropdownOpen.value) isNotificationDropdownOpen.value = false;
    }
};

const pollNotifications = () => {
    if (document.hidden || isPolling || authUser.value?.role !== 'owner') return;
    isPolling = true;

    const onlyKeys = ['auth'];
    const isDashboard = page.component === 'Owner/Dashboard/Index' || (typeof route === 'function' && route().current && route().current('owner.dashboard'));
    if (isDashboard) {
        onlyKeys.push(
            'pendingApprovals',
            'totalPendingApprovals',
            'pendingLeavesCount',
            'todayPresentCount',
            'attendanceRate',
            'recentClockIns'
        );
    }

    router.reload({
        only: onlyKeys,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isPolling = false;
        },
    });
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        pollNotifications();
    }
};

onMounted(() => {
    document.addEventListener('click', handleGlobalClick);
    document.addEventListener('keydown', handleGlobalKeydown);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('click', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });

    if (authUser.value?.role === 'owner') {
        // Polling cepat setiap 8 detik (antara 5-10 detik)
        pollInterval = setInterval(pollNotifications, 8000);
        setTimeout(() => {
            checkPendingApprovalsNotification();
        }, 1200);
    }
});

onUnmounted(() => {
    if (toastTimer) clearTimeout(toastTimer);
    if (progressInterval) clearInterval(progressInterval);
    if (approvalTimer) clearTimeout(approvalTimer);
    if (approvalProgressInterval) clearInterval(approvalProgressInterval);
    if (pollInterval) clearInterval(pollInterval);
    document.removeEventListener('click', handleGlobalClick);
    document.removeEventListener('keydown', handleGlobalKeydown);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});



const logout = () => {
    if (authUser.value?.role === 'admin') {
        router.post(route('admin.logout'));
    } else {
        router.post(route('logout'));
    }
};
</script>

<template>
    <div class="h-screen flex overflow-hidden font-sans antialiased bg-slate-50 text-slate-800">
        <!-- Toast / Flash Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-x-8 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transform ease-in duration-200 transition"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-8 opacity-0"
        >
            <div
                v-if="showToast"
                @mouseenter="pauseToast"
                @mouseleave="resumeToast"
                class="fixed top-20 right-5 z-[9999] max-w-sm w-full sm:w-[380px] bg-white shadow-2xl shadow-slate-900/10 rounded-2xl border border-slate-200/90 overflow-hidden transition-all pointer-events-auto"
            >
                <div class="p-4 flex items-start gap-3">
                    <!-- Icon -->
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                        :class="{
                            'bg-emerald-100 text-emerald-600': toastType === 'success',
                            'bg-rose-100 text-rose-600': toastType === 'error',
                            'bg-amber-100 text-amber-600': toastType === 'warning',
                            'bg-sky-100 text-sky-600': toastType === 'info',
                        }"
                    >
                        <!-- Success Checkmark -->
                        <svg v-if="toastType === 'success'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                        <!-- Error Exclamation -->
                        <svg v-else-if="toastType === 'error'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <!-- Warning Triangle -->
                        <svg v-else-if="toastType === 'warning'" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        <!-- Info -->
                        <svg v-else class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p
                            class="text-xs font-bold uppercase tracking-wider mb-0.5"
                            :class="{
                                'text-emerald-700': toastType === 'success',
                                'text-rose-700': toastType === 'error',
                                'text-amber-700': toastType === 'warning',
                                'text-sky-700': toastType === 'info',
                            }"
                        >
                            {{ toastType === 'success' ? 'Berhasil' : (toastType === 'error' ? 'Gagal' : (toastType === 'warning' ? 'Perhatian' : 'Informasi')) }}
                        </p>
                        <p class="text-sm font-medium text-slate-700 leading-snug">
                            {{ toastMessage }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="dismissToast"
                        class="p-1 -mr-1 -mt-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition cursor-pointer"
                        title="Tutup Notifikasi"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Progress Bar Indicator -->
                <div class="h-1 w-full bg-slate-100/80">
                    <div
                        class="h-full transition-all duration-75"
                        :style="{ width: `${progress}%` }"
                        :class="{
                            'bg-emerald-500': toastType === 'success',
                            'bg-rose-500': toastType === 'error',
                            'bg-amber-500': toastType === 'warning',
                            'bg-sky-500': toastType === 'info',
                        }"
                    />
                </div>
            </div>
        </transition>

        <!-- Floating Approval Alert (Bottom-Right, distinct from CRUD toast) -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-8 translate-x-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 translate-x-0 opacity-100 scale-100"
            leave-active-class="transform ease-in duration-200 transition"
            leave-from-class="translate-y-0 translate-x-0 opacity-100 scale-100"
            leave-to-class="translate-y-8 translate-x-4 opacity-0 scale-95"
        >
            <div
                v-if="showApprovalAlert && currentApprovalAlert"
                @mouseenter="pauseApprovalAlert"
                @mouseleave="resumeApprovalAlert"
                class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[9998] max-w-md w-[calc(100%-2rem)] sm:w-[410px] bg-white shadow-2xl shadow-slate-900/12 rounded-2xl border border-slate-200/90 overflow-hidden pointer-events-auto"
                role="alert"
            >
                <div class="p-4 sm:p-5">
                    <!-- Top Bar: Badge & Time & Close -->
                    <div class="flex items-center justify-between gap-2 mb-3.5">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/70">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                            </span>
                            <span class="tracking-wide">Perlu Persetujuan</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-medium text-slate-400">
                                {{ currentApprovalAlert.time_ago || 'Baru saja' }}
                            </span>
                            <button
                                type="button"
                                @click="dismissApprovalAlert"
                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition cursor-pointer"
                                title="Tutup Notifikasi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Main Info (Icon + Text) -->
                    <div class="flex items-start gap-3.5">
                        <!-- Unified Icon Container (Clean Solid Blue) -->
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm shadow-blue-600/20 bg-blue-600 text-white">
                            <!-- Type Specific SVG Icons -->
                            <svg v-if="currentApprovalAlert.type === 'leave'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="currentApprovalAlert.type === 'loan'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <svg v-else-if="currentApprovalAlert.type === 'overtime'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="currentApprovalAlert.type === 'reimbursement'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                            </svg>
                            <svg v-else-if="currentApprovalAlert.type === 'employee'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <svg v-else-if="currentApprovalAlert.type === 'schedule'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">
                                {{ currentApprovalAlert.title }}
                            </h4>
                            <p class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">
                                {{ currentApprovalAlert.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Button (Solid Blue, Clean & Minimalist) -->
                    <div class="mt-4">
                        <a
                            :href="currentApprovalAlert.link"
                            @click="dismissApprovalAlert"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-sm hover:shadow transition-all group cursor-pointer"
                        >
                            <span>Tinjau Pengajuan</span>
                            <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Progress Bar Indicator (Solid Blue) -->
                <div class="h-1 w-full bg-slate-100">
                    <div
                        class="h-full bg-blue-600 transition-all duration-75"
                        :style="{ width: `${approvalProgress}%` }"
                    />
                </div>
            </div>
        </transition>

        <!-- Notification Audio Element (Supports custom MP3 or WAV) -->
        <audio id="approvalNotificationSound" preload="auto" class="hidden">
            <source src="/sounds/notification.mp3" type="audio/mpeg">
            <source src="/sounds/notification.wav" type="audio/wav">
        </audio>

        <!-- Sidebar Overlay (Mobile) -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden transition-opacity"
        />

        <!-- Sidebar Components (Separated by Role) -->
        <AdminSidebar
            v-if="authUser?.role === 'admin'"
            :sidebar-open="sidebarOpen"
            :desktop-sidebar-open="desktopSidebarOpen"
            :auth-user="authUser"
            :app-name="appName"
            @close="sidebarOpen = false"
        />
        <EmployeeSidebar
            v-else-if="authUser?.role === 'employee'"
            :sidebar-open="sidebarOpen"
            :desktop-sidebar-open="desktopSidebarOpen"
            :auth-user="authUser"
            :company-name="companyName"
            :app-name="appName"
            @close="sidebarOpen = false"
        />
        <OwnerSidebar
            v-else
            :sidebar-open="sidebarOpen"
            :desktop-sidebar-open="desktopSidebarOpen"
            :auth-user="authUser"
            :company-name="companyName"
            :app-name="appName"
            @close="sidebarOpen = false"
        />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-100/70 relative">
            <!-- Top Header (1:1 with owner.blade.php) -->
            <header class="h-16 flex-shrink-0 bg-white/70 backdrop-blur-md border-b border-slate-200/60 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30 shadow-xs transition-all duration-300">
                <!-- Left: Toggle & Breadcrumb -->
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden text-slate-500 hover:text-slate-700 p-1 rounded-lg cursor-pointer"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="desktopSidebarOpen = !desktopSidebarOpen"
                        class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                        title="Toggle Sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </button>

                    <div class="flex items-center gap-2 text-sm text-slate-700 font-medium">
                        <slot name="header">
                            <span>Dashboard</span>
                        </slot>
                    </div>
                </div>

                <!-- Right: Company Badge + Notifications + Profile (1:1 with Blade) -->
                <div class="flex items-center gap-3">
                    <!-- Company Name Badge -->
                    <div
                        v-if="companyName"
                        class="hidden md:flex items-center gap-2 px-3 py-1.5 border border-emerald-100 bg-emerald-50 rounded-lg"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="text-sm font-medium text-emerald-700">{{ companyName }}</span>
                    </div>

                    <!-- Notifications Dropdown -->
                    <div
                        v-if="authUser?.role === 'owner'"
                        ref="notificationDropdownRef"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="isNotificationDropdownOpen = !isNotificationDropdownOpen"
                            class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                            :class="{ 'bg-slate-100 text-slate-800': isNotificationDropdownOpen }"
                            title="Notifikasi"
                            aria-label="Notifikasi"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                v-if="unreadNotificationsCount > 0"
                                class="absolute top-1 right-1 min-w-4 h-4 px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
                            >
                                {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
                            </span>
                        </button>

                        <!-- Dropdown Menu Panel -->
                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-1"
                        >
                            <div
                                v-if="isNotificationDropdownOpen"
                                class="dropdown-menu w-80 sm:w-96 right-0 z-50 text-left origin-top-right overflow-hidden shadow-xl border border-slate-200/80 rounded-2xl bg-white"
                            >
                                <!-- Header -->
                                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-slate-900">Notifikasi</h3>
                                        <span
                                            v-if="unreadNotificationsCount > 0"
                                            class="px-2 py-0.5 text-[11px] font-semibold bg-emerald-100 text-emerald-800 rounded-full"
                                        >
                                            {{ unreadNotificationsCount }} Baru
                                        </span>
                                    </div>
                                    <button
                                        v-if="unreadNotificationsCount > 0"
                                        type="button"
                                        @click="markAllNotificationsRead"
                                        class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition cursor-pointer"
                                    >
                                        Tandai semua dibaca
                                    </button>
                                </div>

                                <!-- Notification Items List -->
                                <div class="divide-y divide-slate-100 max-h-[360px] overflow-y-auto">
                                    <template v-if="recentNotifications.length > 0">
                                        <a
                                            v-for="item in recentNotifications"
                                            :key="item.id"
                                            :href="item.link"
                                            @click="isNotificationDropdownOpen = false"
                                            :class="[
                                                'block p-3.5 transition hover:bg-slate-50/80 cursor-pointer',
                                                !item.read_at ? 'bg-emerald-50/30' : 'bg-white'
                                            ]"
                                        >
                                            <div class="flex items-start gap-3">
                                                <span
                                                    :class="[
                                                        'w-2 h-2 rounded-full mt-1.5 shrink-0',
                                                        !item.read_at ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-slate-200'
                                                    ]"
                                                ></span>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p :class="['text-xs font-semibold truncate', !item.read_at ? 'text-slate-900' : 'text-slate-700']">
                                                            {{ item.title }}
                                                        </p>
                                                        <span class="text-[10px] text-slate-400 shrink-0">
                                                            {{ item.time_ago || '' }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-relaxed">
                                                        {{ item.message }}
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                    <div v-else class="py-8 px-4 text-center">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-medium text-slate-600">Tidak ada notifikasi baru</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Semua pemberitahuan sistem telah dibaca.</p>
                                    </div>
                                </div>

                                <!-- Dropdown Footer -->
                                <div class="p-2.5 border-t border-slate-100 bg-slate-50/70 text-center">
                                    <Link
                                        :href="route('owner.notifications.index')"
                                        @click="isNotificationDropdownOpen = false"
                                        class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 rounded-xl transition"
                                    >
                                        <span>Lihat semua notifikasi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div ref="userDropdownRef" class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer select-none"
                        >
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-semibold overflow-hidden shadow-xs"
                                :class="authUser?.avatar ? 'bg-white border border-slate-200' : (authUser?.role === 'admin' ? 'bg-gradient-to-tr from-orange-600 to-amber-500 text-white' : 'bg-emerald-600 text-white')"
                            >
                                <img
                                    v-if="authUser?.avatar"
                                    :src="'/storage/' + authUser.avatar"
                                    :alt="authUser.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ authUser?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-slate-900 leading-tight">{{ authUser?.name || 'Company User' }}</p>
                                <p class="text-xs text-slate-500 capitalize leading-tight mt-0.5">{{ authUser?.role || 'HRD' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu Panel (Exact Blade classes & width w-72) -->
                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-1"
                        >
                            <div
                                v-if="isUserDropdownOpen"
                                class="dropdown-menu w-72 right-0 z-50 text-left origin-top-right"
                            >
                                <!-- User Info Header -->
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <p class="text-sm font-semibold text-slate-900">{{ authUser?.name || 'Company User' }}</p>
                                    <p class="text-xs text-slate-500">{{ authUser?.email || '' }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            v-if="companyName"
                                            class="inline-flex items-center px-2 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-medium rounded"
                                        >
                                            {{ companyName }}
                                        </span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded capitalize"
                                            :class="authUser?.role === 'admin' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ authUser?.role === 'admin' ? 'Super Admin' : (authUser?.role || 'Owner') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Menu Links -->
                                <div class="py-1">
                                    <template v-if="authUser?.role === 'owner'">
                                        <Link
                                            :href="route('owner.profile.index')"
                                            @click="isUserDropdownOpen = false"
                                            class="dropdown-item"
                                            :class="{ 'active': route().current('owner.profile.index') }"
                                        >
                                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Profil Saya</span>
                                        </Link>
                                        <Link
                                            :href="route('owner.profile.edit')"
                                            @click="isUserDropdownOpen = false"
                                            class="dropdown-item"
                                            :class="{ 'active': route().current('owner.profile.edit') }"
                                        >
                                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit Profil</span>
                                        </Link>
                                        <Link
                                            :href="route('owner.profile.password')"
                                            @click="isUserDropdownOpen = false"
                                            class="dropdown-item"
                                            :class="{ 'active': route().current('owner.profile.password*') }"
                                        >
                                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span>Ganti Kata Sandi</span>
                                        </Link>
                                    </template>
                                    <Link
                                        v-else-if="authUser?.role === 'employee'"
                                        :href="route('employee.profile.index')"
                                        @click="isUserDropdownOpen = false"
                                        class="dropdown-item"
                                        :class="{ 'active': route().current('employee.profile.index') }"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Profil Saya</span>
                                    </Link>
                                    <Link
                                        v-if="authUser?.role === 'employee'"
                                        :href="route('employee.profile.edit')"
                                        @click="isUserDropdownOpen = false"
                                        class="dropdown-item"
                                        :class="{ 'active': route().current('employee.profile.edit') }"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit Profil</span>
                                    </Link>
                                    <Link
                                        v-if="authUser?.role === 'employee'"
                                        :href="route('employee.profile.password')"
                                        @click="isUserDropdownOpen = false"
                                        class="dropdown-item"
                                        :class="{ 'active': route().current('employee.profile.password*') }"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <span>Ganti Kata Sandi</span>
                                    </Link>
                                </div>

                                <div class="dropdown-divider"></div>

                                <div class="py-1">
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="dropdown-item dropdown-item-danger w-full text-left cursor-pointer"
                                    >
                                        <svg class="w-5 h-5 text-slate-400 group-hover:text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-scroll p-4 sm:p-6 lg:p-8 space-y-6">
                <slot />
            </main>
        </div>
    </div>
</template>
