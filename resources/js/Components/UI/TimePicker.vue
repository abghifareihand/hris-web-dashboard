<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, null],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '-- : --',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    defaultTime: {
        type: String,
        default: '08:00',
    },
});

const emit = defineEmits(['update:modelValue', 'change', 'clear']);

const isOpen = ref(false);
const timepickerRef = ref(null);
const hoursContainerRef = ref(null);
const minutesContainerRef = ref(null);
const openUpward = ref(false);

// Internal working state
const tempHour = ref('08');
const tempMinute = ref('00');

// Generate Hours (00 - 23)
const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));

// Generate Minutes (00 - 59)
const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

// Period text badge (Pagi, Siang, Sore, Malam)
const timePeriodLabel = computed(() => {
    const h = parseInt(tempHour.value, 10);
    if (isNaN(h)) return '';
    if (h >= 4 && h < 11) return 'Pagi';
    if (h >= 11 && h < 15) return 'Siang';
    if (h >= 15 && h < 19) return 'Sore';
    return 'Malam';
});

const parseValue = (val) => {
    if (!val || typeof val !== 'string') {
        const fallback = props.defaultTime || '08:00';
        const [h, m] = fallback.split(':');
        return {
            h: h ? h.padStart(2, '0') : '08',
            m: m ? m.padStart(2, '0') : '00',
        };
    }
    const parts = val.trim().split(':');
    const h = parts[0] ? parts[0].padStart(2, '0') : '08';
    const m = parts[1] ? parts[1].padStart(2, '0') : '00';
    return { h, m };
};

const syncFromModel = () => {
    if (props.modelValue) {
        const { h, m } = parseValue(props.modelValue);
        tempHour.value = h;
        tempMinute.value = m;
    } else if (props.defaultTime) {
        const { h, m } = parseValue(props.defaultTime);
        tempHour.value = h;
        tempMinute.value = m;
    }
};

watch(() => props.modelValue, syncFromModel, { immediate: true });

const scrollToActive = () => {
    nextTick(() => {
        if (hoursContainerRef.value) {
            const activeHourEl = hoursContainerRef.value.querySelector('.is-active-hour');
            if (activeHourEl) {
                activeHourEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
        if (minutesContainerRef.value) {
            const activeMinEl = minutesContainerRef.value.querySelector('.is-active-minute');
            if (activeMinEl) {
                activeMinEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
    });
};

const toggleOpen = () => {
    if (props.disabled) return;
    if (!isOpen.value) {
        syncFromModel();
        if (timepickerRef.value) {
            const rect = timepickerRef.value.getBoundingClientRect();
            openUpward.value = window.innerHeight - rect.bottom < 320 && rect.top > 320;
        }
        isOpen.value = true;
        scrollToActive();
    } else {
        isOpen.value = false;
    }
};

const close = () => {
    isOpen.value = false;
};

const selectHour = (h) => {
    tempHour.value = h;
    applyTime();
};

const selectMinute = (m) => {
    tempMinute.value = m;
    applyTime();
};

const applyTime = () => {
    const formatted = `${tempHour.value}:${tempMinute.value}`;
    emit('update:modelValue', formatted);
    emit('change', formatted);
};

const confirmAndClose = () => {
    applyTime();
    close();
};

const clearTime = () => {
    emit('update:modelValue', '');
    emit('change', '');
    emit('clear');
    close();
};

const handleClickOutside = (e) => {
    if (timepickerRef.value && !timepickerRef.value.contains(e.target)) {
        close();
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div
        ref="timepickerRef"
        :class="['w-full relative text-left', isOpen ? 'z-30' : '']"
    >
        <!-- Label -->
        <label
            v-if="label"
            class="form-label"
            :class="{ 'form-label-required': required }"
        >
            {{ label }}
        </label>

        <!-- Trigger Input Box -->
        <div
            @click="toggleOpen"
            :class="[
                'w-full flex items-center justify-between gap-2 bg-white px-3.5 h-10 min-h-[40px] text-[13px] rounded-xl border transition-all select-none group',
                disabled
                    ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                    : 'cursor-pointer',
                isOpen
                    ? 'border-emerald-500 shadow-[0_0_0_3px_rgba(16,185,129,0.15)] bg-white'
                    : error
                      ? 'border-rose-400 shadow-[0_0_0_3px_rgba(244,63,94,0.15)]'
                      : 'border-slate-300 hover:border-slate-400',
            ]"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <!-- Clock Icon -->
                <svg
                    class="w-4 h-4 shrink-0 transition-colors"
                    :class="isOpen ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>

                <span
                    class="truncate font-medium text-[13px] tracking-wide"
                    :class="modelValue ? 'text-slate-900 font-semibold' : 'text-slate-400'"
                >
                    {{ modelValue || placeholder }}
                </span>

                <span
                    v-if="modelValue"
                    class="text-[11px] px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-medium border border-emerald-100"
                >
                    {{ timePeriodLabel }}
                </span>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <!-- Clear Button -->
                <button
                    v-if="modelValue && !disabled"
                    type="button"
                    @click.stop="clearTime"
                    class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                    title="Hapus jam"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Chevron Icon -->
                <svg
                    class="w-4 h-4 text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180 text-emerald-600': isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="form-error mt-1 text-xs text-rose-500">
            {{ error }}
        </p>

        <!-- Dropdown Popover (Dual Column List: Jam 00-23 & Menit 00-59) -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 z-50 w-full sm:w-[280px] bg-white rounded-2xl border border-slate-200 shadow-2xl p-3.5 space-y-3 select-none"
                :class="openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
            >
                <!-- Header: Display Current Selection -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="px-3 py-1 bg-slate-100 rounded-lg text-slate-800 text-sm font-bold tracking-wider">
                            {{ tempHour }} : {{ tempMinute }}
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ timePeriodLabel }}
                        </span>
                    </div>

                    <span class="text-[11px] font-medium text-slate-400">Pilih Waktu</span>
                </div>

                <!-- Dual Columns: Jam & Menit -->
                <div class="grid grid-cols-2 gap-2">
                    <!-- Column 1: Jam -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center py-1 mb-1.5 bg-slate-50 rounded-lg border border-slate-100">
                            Jam
                        </div>
                        <div
                            ref="hoursContainerRef"
                            class="h-48 overflow-y-auto pr-1 space-y-1 scrollbar-thin scrollbar-thumb-slate-200"
                        >
                            <button
                                v-for="h in hours"
                                :key="h"
                                type="button"
                                @click="selectHour(h)"
                                :class="[
                                    'w-full py-1.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-center block',
                                    tempHour === h
                                        ? 'is-active-hour bg-emerald-600 text-white font-bold shadow-xs'
                                        : 'text-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                {{ h }}
                            </button>
                        </div>
                    </div>

                    <!-- Column 2: Menit (00 - 59) -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center py-1 mb-1.5 bg-slate-50 rounded-lg border border-slate-100">
                            Menit
                        </div>
                        <div
                            ref="minutesContainerRef"
                            class="h-48 overflow-y-auto pr-1 space-y-1 scrollbar-thin scrollbar-thumb-slate-200"
                        >
                            <button
                                v-for="m in minutes"
                                :key="m"
                                type="button"
                                @click="selectMinute(m)"
                                :class="[
                                    'w-full py-1.5 text-xs font-semibold rounded-lg transition-colors cursor-pointer text-center block',
                                    tempMinute === m
                                        ? 'is-active-minute bg-emerald-600 text-white font-bold shadow-xs'
                                        : 'text-slate-700 hover:bg-slate-100'
                                ]"
                            >
                                {{ m }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer: Actions -->
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <button
                        type="button"
                        @click="close"
                        class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition cursor-pointer"
                    >
                        Tutup
                    </button>
                    <button
                        type="button"
                        @click="confirmAndClose"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition cursor-pointer"
                    >
                        Pilih Jam
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
