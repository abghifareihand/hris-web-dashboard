<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

const props = defineProps({
    modelValue: {
        type: [String, null],
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "DD/MM/YYYY",
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
        default: "",
    },
    min: {
        type: String,
        default: "",
    },
    max: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue", "change"]);

const isOpen = ref(false);
const datepickerRef = ref(null);

const MONTH_NAMES = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
];

const DAY_NAMES = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

// Parse initial date from modelValue (YYYY-MM-DD or DD/MM/YYYY)
const parseDate = (val) => {
    if (!val || typeof val !== "string") return null;

    // Format YYYY-MM-DD
    if (val.includes("-")) {
        const parts = val.split("-");
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
                return new Date(year, month, day);
            }
        }
    }

    // Format DD/MM/YYYY
    if (val.includes("/")) {
        const parts = val.split("/");
        if (parts.length === 3) {
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const year = parseInt(parts[2], 10);
            if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
                return new Date(year, month, day);
            }
        }
    }

    return null;
};

const currentDate = new Date();
const viewYear = ref(currentDate.getFullYear());
const viewMonth = ref(currentDate.getMonth());

// Sync view when opened or modelValue changes
watch(
    () => props.modelValue,
    (newVal) => {
        const d = parseDate(newVal);
        if (d) {
            viewYear.value = d.getFullYear();
            viewMonth.value = d.getMonth();
        }
    },
    { immediate: true },
);

// Formatted display in trigger button (DD/MM/YYYY e.g. 01/12/2026)
const displayValue = computed(() => {
    const d = parseDate(props.modelValue);
    if (!d) return "";
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
});

// Month options formatted for our custom Dropdown.vue
const monthOptions = computed(() => {
    return MONTH_NAMES.map((name, idx) => ({
        value: idx,
        label: name,
    }));
});

// Year range for the selector (1940 to currentYear + 15)
const yearOptions = computed(() => {
    const start = 1940;
    const end = new Date().getFullYear() + 15;
    const list = [];
    for (let y = end; y >= start; y--) {
        list.push(y);
    }
    return list;
});

// Generate 42 days grid for calendar
const calendarDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value;

    const firstDayIndex = new Date(year, month, 1).getDay();
    const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const days = [];

    // Previous month filler days
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        const d = daysInPrevMonth - i;
        const prevMonthDate = new Date(year, month - 1, d);
        days.push({
            day: d,
            dateString: formatDateString(prevMonthDate),
            isCurrentMonth: false,
            isToday: isToday(prevMonthDate),
            isSelected: isSelected(prevMonthDate),
        });
    }

    // Current month days
    for (let d = 1; d <= daysInCurrentMonth; d++) {
        const curDate = new Date(year, month, d);
        days.push({
            day: d,
            dateString: formatDateString(curDate),
            isCurrentMonth: true,
            isToday: isToday(curDate),
            isSelected: isSelected(curDate),
        });
    }

    // Next month filler days (total 42 slots for steady 6 rows)
    const remaining = 42 - days.length;
    for (let d = 1; d <= remaining; d++) {
        const nextMonthDate = new Date(year, month + 1, d);
        days.push({
            day: d,
            dateString: formatDateString(nextMonthDate),
            isCurrentMonth: false,
            isToday: isToday(nextMonthDate),
            isSelected: isSelected(nextMonthDate),
        });
    }

    return days;
});

const formatDateString = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

const isToday = (date) => {
    const today = new Date();
    return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
    );
};

const isSelected = (date) => {
    return formatDateString(date) === props.modelValue;
};

const isMonthDropdownOpen = ref(false);
const isYearDropdownOpen = ref(false);
const monthSelectorRef = ref(null);
const yearSelectorRef = ref(null);
const monthListRef = ref(null);
const yearListRef = ref(null);

const scrollToActive = (container) => {
    if (!container) return;
    const activeEl = container.querySelector(".is-active");
    if (activeEl) {
        container.scrollTop =
            activeEl.offsetTop -
            container.clientHeight / 2 +
            activeEl.clientHeight / 2;
    }
};

const toggleMonthDropdown = async () => {
    isMonthDropdownOpen.value = !isMonthDropdownOpen.value;
    isYearDropdownOpen.value = false;
    if (isMonthDropdownOpen.value) {
        await nextTick();
        scrollToActive(monthListRef.value);
    }
};

const toggleYearDropdown = async () => {
    isYearDropdownOpen.value = !isYearDropdownOpen.value;
    isMonthDropdownOpen.value = false;
    if (isYearDropdownOpen.value) {
        await nextTick();
        scrollToActive(yearListRef.value);
    }
};

const selectMonth = (idx) => {
    viewMonth.value = idx;
    isMonthDropdownOpen.value = false;
};

const selectYear = (y) => {
    viewYear.value = y;
    isYearDropdownOpen.value = false;
};

const prevMonth = () => {
    isMonthDropdownOpen.value = false;
    isYearDropdownOpen.value = false;
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value--;
    } else {
        viewMonth.value--;
    }
};

const nextMonth = () => {
    isMonthDropdownOpen.value = false;
    isYearDropdownOpen.value = false;
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value++;
    } else {
        viewMonth.value++;
    }
};

const selectDay = (dayObj) => {
    emit("update:modelValue", dayObj.dateString);
    emit("change", dayObj.dateString);
    close();
};

const setToday = () => {
    const today = new Date();
    const formatted = formatDateString(today);
    viewYear.value = today.getFullYear();
    viewMonth.value = today.getMonth();
    emit("update:modelValue", formatted);
    emit("change", formatted);
    close();
};

const clearDate = () => {
    emit("update:modelValue", "");
    emit("change", "");
};

const toggleOpen = () => {
    if (props.disabled) return;
    if (!isOpen.value) {
        const d = parseDate(props.modelValue);
        if (d) {
            viewYear.value = d.getFullYear();
            viewMonth.value = d.getMonth();
        }
    }
    isOpen.value = !isOpen.value;
    if (!isOpen.value) {
        isMonthDropdownOpen.value = false;
        isYearDropdownOpen.value = false;
    }
};

const close = () => {
    isOpen.value = false;
    isMonthDropdownOpen.value = false;
    isYearDropdownOpen.value = false;
};

const handleClickOutside = (e) => {
    if (datepickerRef.value && !datepickerRef.value.contains(e.target)) {
        close();
    } else {
        if (monthSelectorRef.value && !monthSelectorRef.value.contains(e.target)) {
            isMonthDropdownOpen.value = false;
        }
        if (yearSelectorRef.value && !yearSelectorRef.value.contains(e.target)) {
            isYearDropdownOpen.value = false;
        }
    }
};

const handleKeyDown = (e) => {
    if (e.key === "Escape") {
        if (isMonthDropdownOpen.value || isYearDropdownOpen.value) {
            isMonthDropdownOpen.value = false;
            isYearDropdownOpen.value = false;
        } else if (isOpen.value) {
            close();
        }
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    document.addEventListener("keydown", handleKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    document.removeEventListener("keydown", handleKeyDown);
});
</script>

<template>
    <div
        ref="datepickerRef"
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

        <!-- Trigger Button -->
        <div
            @click="toggleOpen"
            :class="[
                'w-full flex items-center justify-between gap-2 bg-white px-3 h-10 min-h-[40px] text-[13px] rounded-lg border transition-all select-none',
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
            <span
                class="truncate text-[13px] leading-normal"
                :class="displayValue ? 'text-slate-800 font-normal' : 'text-slate-400 font-normal'"
            >
                {{ displayValue || placeholder }}
            </span>

            <div class="flex items-center gap-1.5 shrink-0">
                <!-- Clear Button -->
                <button
                    v-if="modelValue && !disabled"
                    type="button"
                    @click.stop="clearDate"
                    class="p-0.5 rounded text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                    title="Hapus tanggal"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Chevron Icon -->
                <svg
                    class="w-4 h-4 text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="form-error mt-1">
            {{ error }}
        </p>

        <!-- Calendar Dropdown Popover -->
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
                class="absolute left-0 top-full mt-1.5 z-50 w-[300px] bg-white rounded-2xl border border-slate-200 shadow-xl p-3.5 space-y-3"
            >
                <!-- Navigation Header -->
                <div class="flex items-center justify-between gap-1 pb-1.5 border-b border-slate-100">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition shrink-0"
                        title="Bulan sebelumnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Quick Month & Year Selectors with FIXED width & NO green ring -->
                    <div class="flex items-center gap-1.5">
                        <!-- Month Dropdown -->
                        <div ref="monthSelectorRef" class="relative inline-block w-[115px]">
                            <button
                                type="button"
                                @click.stop="toggleMonthDropdown"
                                class="w-full h-8 px-2.5 flex items-center justify-between text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-lg transition-colors select-none"
                            >
                                <span class="truncate">{{ MONTH_NAMES[viewMonth] }}</span>
                                <svg
                                    class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 shrink-0 ml-1"
                                    :class="{ 'rotate-180': isMonthDropdownOpen }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Month Menu Panel -->
                            <Transition
                                enter-active-class="transition duration-100 ease-out"
                                enter-from-class="transform opacity-0 scale-95 -translate-y-1"
                                enter-to-class="transform opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition duration-75 ease-in"
                                leave-from-class="transform opacity-100 scale-100 translate-y-0"
                                leave-to-class="transform opacity-0 scale-95 -translate-y-1"
                            >
                                <div
                                    v-if="isMonthDropdownOpen"
                                    ref="monthListRef"
                                    class="absolute left-0 top-full mt-1.5 w-36 rounded-xl bg-white shadow-xl border border-slate-200/80 py-1 max-h-48 overflow-y-auto focus:outline-none z-60 dropdown-scroll"
                                >
                                    <button
                                        v-for="(name, idx) in MONTH_NAMES"
                                        :key="idx"
                                        type="button"
                                        @click.stop="selectMonth(idx)"
                                        :class="[
                                            'w-full flex items-center px-3 py-1.5 text-xs transition-colors text-left cursor-pointer select-none',
                                            viewMonth === idx
                                                ? 'is-active bg-emerald-50 text-emerald-700 font-semibold'
                                                : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-normal',
                                        ]"
                                    >
                                        {{ name }}
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Year Dropdown -->
                        <div ref="yearSelectorRef" class="relative inline-block w-[80px]">
                            <button
                                type="button"
                                @click.stop="toggleYearDropdown"
                                class="w-full h-8 px-2.5 flex items-center justify-between text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-lg transition-colors select-none"
                            >
                                <span class="truncate">{{ viewYear }}</span>
                                <svg
                                    class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 shrink-0 ml-1"
                                    :class="{ 'rotate-180': isYearDropdownOpen }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Year Menu Panel -->
                            <Transition
                                enter-active-class="transition duration-100 ease-out"
                                enter-from-class="transform opacity-0 scale-95 -translate-y-1"
                                enter-to-class="transform opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition duration-75 ease-in"
                                leave-from-class="transform opacity-100 scale-100 translate-y-0"
                                leave-to-class="transform opacity-0 scale-95 -translate-y-1"
                            >
                                <div
                                    v-if="isYearDropdownOpen"
                                    ref="yearListRef"
                                    class="absolute right-0 top-full mt-1.5 w-24 rounded-xl bg-white shadow-xl border border-slate-200/80 py-1 max-h-48 overflow-y-auto focus:outline-none z-60 dropdown-scroll"
                                >
                                    <button
                                        v-for="y in yearOptions"
                                        :key="y"
                                        type="button"
                                        @click.stop="selectYear(y)"
                                        :class="[
                                            'w-full flex items-center px-3 py-1.5 text-xs transition-colors text-left cursor-pointer select-none',
                                            viewYear === y
                                                ? 'is-active bg-emerald-50 text-emerald-700 font-semibold'
                                                : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 font-normal',
                                        ]"
                                    >
                                        {{ y }}
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition shrink-0"
                        title="Bulan berikutnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Day Names Header -->
                <div class="grid grid-cols-7 gap-1 text-center">
                    <span
                        v-for="dayName in DAY_NAMES"
                        :key="dayName"
                        class="text-[11px] font-semibold text-slate-400 py-1"
                    >
                        {{ dayName }}
                    </span>
                </div>

                <!-- Days 7x6 Grid -->
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="(dayObj, index) in calendarDays"
                        :key="index"
                        type="button"
                        @click="selectDay(dayObj)"
                        :class="[
                            'h-8 w-8 mx-auto text-xs flex items-center justify-center rounded-lg transition-all',
                            dayObj.isSelected
                                ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold shadow-xs'
                                : dayObj.isToday
                                  ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-300'
                                  : dayObj.isCurrentMonth
                                    ? 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'
                                    : 'text-slate-300 hover:bg-slate-50 hover:text-slate-500',
                        ]"
                    >
                        {{ dayObj.day }}
                    </button>
                </div>

                <!-- Footer Quick Actions -->
                <div class="pt-2 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="setToday"
                        class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline px-1 py-0.5"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        @click="close"
                        class="text-slate-400 hover:text-slate-600 px-1 py-0.5"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Completely hide scrollbars in DatePicker dropdowns while keeping scrolling fully functional */
.dropdown-scroll {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.dropdown-scroll::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge, Opera */
    width: 0px;
    height: 0px;
    background: transparent;
}
</style>
