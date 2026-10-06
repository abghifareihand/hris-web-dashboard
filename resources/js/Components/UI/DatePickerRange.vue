<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

const props = defineProps({
    startDate: {
        type: [String, null],
        default: "",
    },
    endDate: {
        type: [String, null],
        default: "",
    },
    modelValue: {
        type: [Object, Array, null],
        default: null,
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Pilih Rentang Tanggal",
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
    showPresets: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits([
    "update:startDate",
    "update:endDate",
    "update:modelValue",
    "change",
]);

const isOpen = ref(false);
const datepickerRef = ref(null);
const openUpward = ref(false);
const openRightAligned = ref(false);

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

const PRESETS = [
    { key: "today", label: "Hari Ini" },
    { key: "last7", label: "7 Hari Terakhir" },
    { key: "last30", label: "30 Hari Terakhir" },
    { key: "thisMonth", label: "Bulan Ini" },
    { key: "lastMonth", label: "Bulan Lalu" },
    { key: "custom", label: "Kustom" },
];

const activePreset = ref("custom");

// Parse date string (YYYY-MM-DD or DD/MM/YYYY)
const parseDate = (val) => {
    if (!val || typeof val !== "string") return null;

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

const formatDateString = (date) => {
    if (!date) return "";
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

const formatDisplayDate = (val) => {
    const d = parseDate(val);
    if (!d) return "";
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
};

// Internal range selection state
const internalStart = ref("");
const internalEnd = ref("");
const hoverDate = ref("");
const isSelectingRange = ref(false);

// Snapshot before opening for cancel action
const initialStart = ref("");
const initialEnd = ref("");

// Detect which preset matches given start & end dates
const detectActivePreset = (s, e) => {
    if (!s && !e) return "custom";

    const now = new Date();
    const todayStr = formatDateString(now);

    // Today
    if (s === todayStr && e === todayStr) return "today";

    // Last 7 days
    const past7 = new Date();
    past7.setDate(now.getDate() - 6);
    if (s === formatDateString(past7) && e === todayStr) return "last7";

    // Last 30 days
    const past30 = new Date();
    past30.setDate(now.getDate() - 29);
    if (s === formatDateString(past30) && e === todayStr) return "last30";

    // This month
    const firstThisMonth = formatDateString(new Date(now.getFullYear(), now.getMonth(), 1));
    const lastThisMonth = formatDateString(new Date(now.getFullYear(), now.getMonth() + 1, 0));
    if (s === firstThisMonth && e === lastThisMonth) return "thisMonth";

    // Last month
    const firstLastMonth = formatDateString(new Date(now.getFullYear(), now.getMonth() - 1, 1));
    const lastLastMonth = formatDateString(new Date(now.getFullYear(), now.getMonth(), 0));
    if (s === firstLastMonth && e === lastLastMonth) return "lastMonth";

    return "custom";
};

// Sync from props
const syncFromProps = () => {
    let s = props.startDate;
    let e = props.endDate;

    if (props.modelValue) {
        if (Array.isArray(props.modelValue)) {
            s = props.modelValue[0] || "";
            e = props.modelValue[1] || "";
        } else if (typeof props.modelValue === "object") {
            s = props.modelValue.start || props.modelValue.startDate || "";
            e = props.modelValue.end || props.modelValue.endDate || "";
        }
    }

    internalStart.value = s || "";
    internalEnd.value = e || "";
    activePreset.value = detectActivePreset(internalStart.value, internalEnd.value);
};

watch(
    () => [props.startDate, props.endDate, props.modelValue],
    () => {
        syncFromProps();
    },
    { immediate: true }
);

const currentDate = new Date();
const viewYear = ref(currentDate.getFullYear());
const viewMonth = ref(currentDate.getMonth());

const syncView = () => {
    const d = parseDate(internalStart.value) || new Date();
    viewYear.value = d.getFullYear();
    viewMonth.value = d.getMonth();
};

// Formatted display in trigger input
const displayValue = computed(() => {
    const startStr = formatDisplayDate(internalStart.value);
    const endStr = formatDisplayDate(internalEnd.value);

    if (startStr && endStr) {
        return `${startStr} — ${endStr}`;
    }
    if (startStr && !endStr) {
        return `${startStr} — ...`;
    }
    if (!startStr && endStr) {
        return `... — ${endStr}`;
    }
    return "";
});


// Month & Year selector dropdowns
const isMonthDropdownOpen = ref(false);
const isYearDropdownOpen = ref(false);
const monthSelectorRef = ref(null);
const yearSelectorRef = ref(null);
const monthListRef = ref(null);
const yearListRef = ref(null);

const yearOptions = computed(() => {
    const start = 1940;
    const end = new Date().getFullYear() + 15;
    const list = [];
    for (let y = end; y >= start; y--) {
        list.push(y);
    }
    return list;
});

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

// Generate 42 days grid
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
        const dateStr = formatDateString(prevMonthDate);
        days.push(createDayObject(d, dateStr, false));
    }

    // Current month days
    for (let d = 1; d <= daysInCurrentMonth; d++) {
        const curDate = new Date(year, month, d);
        const dateStr = formatDateString(curDate);
        days.push(createDayObject(d, dateStr, true));
    }

    // Next month filler days (total 42 slots)
    const remaining = 42 - days.length;
    for (let d = 1; d <= remaining; d++) {
        const nextMonthDate = new Date(year, month + 1, d);
        const dateStr = formatDateString(nextMonthDate);
        days.push(createDayObject(d, dateStr, false));
    }

    return days;
});

const isToday = (dateStr) => {
    return dateStr === formatDateString(new Date());
};

const createDayObject = (dayNum, dateStr, isCurrentMonth) => {
    const s = internalStart.value;
    const e = internalEnd.value;
    const h = hoverDate.value;

    const isStart = Boolean(s && dateStr === s);
    const isEnd = Boolean(e && dateStr === e);

    let inRange = false;
    if (s && e) {
        const minDate = s < e ? s : e;
        const maxDate = s < e ? e : s;
        inRange = dateStr > minDate && dateStr < maxDate;
    }

    let inHover = false;
    if (isSelectingRange.value && s && !e && h && dateStr !== s) {
        const minH = s < h ? s : h;
        const maxH = s < h ? h : s;
        inHover = dateStr >= minH && dateStr <= maxH;
    }

    const isHoverEnd = isSelectingRange.value && Boolean(s && !e && h && dateStr === h);

    return {
        day: dayNum,
        dateString: dateStr,
        isCurrentMonth,
        isToday: isToday(dateStr),
        isStart,
        isEnd,
        inRange,
        inHover,
        isHoverEnd,
    };
};

const handleDayHover = (dayObj) => {
    if (isSelectingRange.value && internalStart.value) {
        hoverDate.value = dayObj.dateString;
    }
};

const handleDayClick = (dayObj) => {
    const clicked = dayObj.dateString;
    activePreset.value = "custom";

    if (!isSelectingRange.value) {
        internalStart.value = clicked;
        internalEnd.value = "";
        isSelectingRange.value = true;
        hoverDate.value = clicked;
    } else {
        if (clicked < internalStart.value) {
            internalEnd.value = internalStart.value;
            internalStart.value = clicked;
        } else {
            internalEnd.value = clicked;
        }
        isSelectingRange.value = false;
        hoverDate.value = "";
    }
};

// Preset selection
const selectPreset = (presetKey) => {
    activePreset.value = presetKey;
    if (presetKey === "custom") {
        isSelectingRange.value = false;
        hoverDate.value = "";
        return;
    }

    const now = new Date();
    let s = "";
    let e = "";

    if (presetKey === "today") {
        s = formatDateString(now);
        e = formatDateString(now);
    } else if (presetKey === "last7") {
        const past = new Date();
        past.setDate(now.getDate() - 6);
        s = formatDateString(past);
        e = formatDateString(now);
    } else if (presetKey === "last30") {
        const past = new Date();
        past.setDate(now.getDate() - 29);
        s = formatDateString(past);
        e = formatDateString(now);
    } else if (presetKey === "thisMonth") {
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        s = formatDateString(first);
        e = formatDateString(last);
    } else if (presetKey === "lastMonth") {
        const first = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const last = new Date(now.getFullYear(), now.getMonth(), 0);
        s = formatDateString(first);
        e = formatDateString(last);
    }

    internalStart.value = s;
    internalEnd.value = e;
    isSelectingRange.value = false;
    hoverDate.value = "";

    const d = parseDate(s);
    if (d) {
        viewYear.value = d.getFullYear();
        viewMonth.value = d.getMonth();
    }
};

const commitRange = () => {
    emit("update:startDate", internalStart.value);
    emit("update:endDate", internalEnd.value);
    emit("update:modelValue", {
        start: internalStart.value,
        end: internalEnd.value,
    });
    emit("change", {
        startDate: internalStart.value,
        endDate: internalEnd.value,
    });
};

const close = () => {
    isOpen.value = false;
    isMonthDropdownOpen.value = false;
    isYearDropdownOpen.value = false;
    isSelectingRange.value = false;
    hoverDate.value = "";
    syncFromProps();
};

const applyAndClose = () => {
    commitRange();
    isOpen.value = false;
    isMonthDropdownOpen.value = false;
    isYearDropdownOpen.value = false;
    isSelectingRange.value = false;
    hoverDate.value = "";
};

const cancelAndClose = () => {
    close();
};

const clearRange = () => {
    internalStart.value = "";
    internalEnd.value = "";
    hoverDate.value = "";
    isSelectingRange.value = false;
    activePreset.value = "custom";
    commitRange();
    isOpen.value = false;
};

const toggleOpen = () => {
    if (props.disabled) return;
    if (!isOpen.value) {
        syncFromProps();
        syncView();
        isSelectingRange.value = false;
        hoverDate.value = "";

        initialStart.value = internalStart.value;
        initialEnd.value = internalEnd.value;

        if (datepickerRef.value) {
            const rect = datepickerRef.value.getBoundingClientRect();
            openUpward.value = window.innerHeight - rect.bottom < 420 && rect.top > 420;
            openRightAligned.value = window.innerWidth - rect.left < 460;
        }
    }
    isOpen.value = !isOpen.value;
    if (!isOpen.value) {
        isMonthDropdownOpen.value = false;
        isYearDropdownOpen.value = false;
    }
};

const handleClickOutside = (e) => {
    if (!isOpen.value) return;

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

        <!-- Trigger Input Button -->
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
                :class="displayValue ? 'text-slate-900 font-medium' : 'text-slate-400 font-normal'"
            >
                {{ displayValue || placeholder }}
            </span>

            <div class="flex items-center gap-1.5 shrink-0">
                <!-- Clear Button -->
                <button
                    v-if="(internalStart || internalEnd) && !disabled"
                    type="button"
                    @click.stop="clearRange"
                    class="p-0.5 rounded text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                    title="Hapus rentang tanggal"
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

        <!-- Popover Panel with Sidebar on the Left -->
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
                class="absolute z-50 bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden w-[440px] max-w-[95vw]"
                :class="[
                    openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5',
                    openRightAligned ? 'right-0' : 'left-0',
                ]"
            >
                <!-- Main Body: Sidebar (Left) + Calendar (Right) -->
                <div class="flex flex-col sm:flex-row">
                    <!-- Left Sidebar: Preset Range Buttons -->
                    <div
                        v-if="showPresets"
                        class="sm:w-36 shrink-0 bg-slate-50/80 p-2.5 flex flex-col gap-1 border-b sm:border-b-0 sm:border-r border-slate-100"
                    >
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2 py-1 select-none">
                            Rentang Waktu
                        </div>

                        <button
                            v-for="p in PRESETS"
                            :key="p.key"
                            type="button"
                            @click="selectPreset(p.key)"
                            :class="[
                                'w-full text-left px-2.5 py-1.5 rounded-md text-xs font-medium transition-all select-none',
                                activePreset === p.key
                                    ? 'bg-emerald-600 text-white font-semibold shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60',
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>

                    <!-- Right Pane: Calendar -->
                    <div class="flex-1 p-3.5 space-y-2.5">
                        <!-- Navigation Header -->
                        <div class="flex items-center justify-between gap-1 pb-1 border-b border-slate-100">
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

                            <!-- Month & Year Selector -->
                            <div class="flex items-center gap-1.5">
                                <!-- Month Dropdown -->
                                <div ref="monthSelectorRef" class="relative inline-block w-[110px]">
                                    <button
                                        type="button"
                                        @click.stop="toggleMonthDropdown"
                                        class="w-full h-8 px-2 flex items-center justify-between text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-lg transition-colors select-none"
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
                                <div ref="yearSelectorRef" class="relative inline-block w-[78px]">
                                    <button
                                        type="button"
                                        @click.stop="toggleYearDropdown"
                                        class="w-full h-8 px-2 flex items-center justify-between text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-lg transition-colors select-none"
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
                        <div class="grid grid-cols-7 text-center">
                            <span
                                v-for="dayName in DAY_NAMES"
                                :key="dayName"
                                class="text-[11px] font-semibold text-slate-400 py-1"
                            >
                                {{ dayName }}
                            </span>
                        </div>

                        <!-- Calendar 7x6 Grid with Connected Range Highlighting -->
                        <div class="grid grid-cols-7 gap-y-1">
                            <div
                                v-for="(dayObj, index) in calendarDays"
                                :key="index"
                                class="relative py-0.5 flex items-center justify-center"
                                @mouseenter="handleDayHover(dayObj)"
                            >
                                <!-- Range Background Connector Strip -->
                                <!-- 1. Strictly in active range -->
                                <div
                                    v-if="dayObj.inRange"
                                    class="absolute inset-y-0.5 left-0 right-0 bg-emerald-50"
                                />

                                <!-- 2. Start date connecting to right -->
                                <div
                                    v-if="dayObj.isStart && internalEnd && internalStart !== internalEnd"
                                    class="absolute inset-y-0.5 left-1/2 right-0 bg-emerald-50"
                                />

                                <!-- 3. End date connecting to left -->
                                <div
                                    v-if="dayObj.isEnd && internalStart && internalStart !== internalEnd"
                                    class="absolute inset-y-0.5 left-0 right-1/2 bg-emerald-50"
                                />

                                <!-- 4. Hover range preview connector -->
                                <div
                                    v-if="dayObj.inHover && !dayObj.inRange"
                                    class="absolute inset-y-0.5 bg-emerald-50/70"
                                    :class="[
                                        dayObj.isStart ? 'left-1/2 right-0' : '',
                                        dayObj.isHoverEnd ? 'left-0 right-1/2' : '',
                                        !dayObj.isStart && !dayObj.isHoverEnd ? 'left-0 right-0' : '',
                                    ]"
                                />

                                <!-- Day Button Cell -->
                                <button
                                    type="button"
                                    @click="handleDayClick(dayObj)"
                                    :class="[
                                        'relative z-10 h-8 w-8 text-xs flex items-center justify-center transition-all select-none',
                                        dayObj.isStart || dayObj.isEnd
                                            ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold shadow-xs rounded-lg'
                                            : dayObj.isHoverEnd
                                              ? 'bg-emerald-500 text-white font-bold shadow-xs rounded-lg'
                                              : dayObj.inRange || dayObj.inHover
                                                ? 'text-emerald-900 font-semibold rounded-none'
                                                : dayObj.isToday
                                                  ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-300 rounded-lg'
                                                  : dayObj.isCurrentMonth
                                                    ? 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium rounded-lg'
                                                    : 'text-slate-300 hover:bg-slate-50 hover:text-slate-500 rounded-lg',
                                    ]"
                                >
                                    {{ dayObj.day }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-3.5 py-2.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div>
                        <button
                            v-if="internalStart || internalEnd"
                            type="button"
                            @click="clearRange"
                            class="text-rose-600 hover:text-rose-700 text-[11px] font-medium px-2 py-1 rounded hover:bg-rose-50 transition"
                        >
                            Reset
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="cancelAndClose"
                            class="text-slate-500 hover:text-slate-700 text-[11px] font-medium px-2 py-1 rounded hover:bg-slate-200/60 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="applyAndClose"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg shadow-xs transition"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.dropdown-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.dropdown-scroll::-webkit-scrollbar {
    display: none;
    width: 0px;
    height: 0px;
    background: transparent;
}
</style>
