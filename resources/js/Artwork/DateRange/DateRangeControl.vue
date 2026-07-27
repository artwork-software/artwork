<template>
    <div v-if="!project" ref="rootEl" class="inline-flex items-center" @keydown="onRootKeydown">
        <div class="inline-flex items-stretch bg-white border border-zinc-200/80 rounded-xl shadow-sm overflow-hidden">
            <button v-if="showNavigation"
                    type="button"
                    class="w-7 flex items-center justify-center text-artwork-context-dark hover:bg-zinc-50 transition duration-200 border-r border-zinc-100"
                    :title="$t('Time range back')"
                    @click="shiftRange(-1)">
                <IconChevronLeft class="h-4 w-4" stroke-width="2"/>
            </button>
            <div ref="triggerEl" class="flex items-center gap-x-1 px-1.5 py-1 text-sm">
                <button type="button"
                        class="p-1 rounded-lg hover:bg-zinc-100 transition duration-150 shrink-0"
                        :title="$t('Select period')"
                        aria-haspopup="dialog"
                        :aria-expanded="open"
                        @click="togglePopover()">
                    <IconCalendar class="h-4 w-4 text-artwork-buttons-create" stroke-width="1.8"/>
                </button>
                <span class="text-xs text-secondary shrink-0">{{ weekdayShort(appliedStart) }}</span>
                <input v-model="inlineStart"
                       type="date"
                       class="drc-date-input w-[5.9rem] border-0 bg-transparent p-0 text-sm font-semibold tabular-nums cursor-text focus:ring-0 focus:outline-none rounded"
                       :aria-label="$t('Start date')"
                       @focusout="onInlineFocusOut"
                       @keydown.enter.prevent="$event.target.blur()"/>
                <span class="text-secondary shrink-0">–</span>
                <span class="text-xs text-secondary shrink-0">{{ weekdayShort(appliedEnd) }}</span>
                <input v-model="inlineEnd"
                       type="date"
                       class="drc-date-input w-[5.9rem] border-0 bg-transparent p-0 text-sm font-semibold tabular-nums cursor-text focus:ring-0 focus:outline-none rounded"
                       :aria-label="$t('End date')"
                       @focusout="onInlineFocusOut"
                       @keydown.enter.prevent="$event.target.blur()"/>
                <button type="button"
                        class="flex items-center gap-x-1 px-1 py-1.5 rounded-lg hover:bg-zinc-100 transition duration-150 shrink-0"
                        :title="$t('Select period')"
                        aria-haspopup="dialog"
                        :aria-expanded="open"
                        @click="togglePopover()">
                    <span class="text-xs text-secondary whitespace-nowrap tabular-nums">{{ rangeMeta }}</span>
                    <IconChevronDown class="h-3.5 w-3.5 text-secondary transition-transform duration-150"
                                     :class="open ? 'rotate-180' : ''" stroke-width="2.2"/>
                </button>
            </div>
            <button v-if="showNavigation"
                    type="button"
                    class="w-7 flex items-center justify-center text-artwork-context-dark hover:bg-zinc-50 transition duration-200 border-l border-zinc-100"
                    :title="$t('Time range forward')"
                    @click="shiftRange(1)">
                <IconChevronRight class="h-4 w-4" stroke-width="2"/>
            </button>
        </div>
        <button v-if="showToday"
                type="button"
                class="ml-1.5 px-2.5 py-2 rounded-xl border border-zinc-200/80 bg-white shadow-sm text-sm font-medium transition duration-200"
                :class="todayInRange ? 'text-secondary cursor-default opacity-60' : 'text-artwork-buttons-create hover:bg-artwork-buttons-create/5'"
                :disabled="todayInRange"
                @click="jumpToToday">
            {{ $t('Today') }}
        </button>

        <Teleport to="body">
            <div v-if="open"
                 ref="popEl"
                 class="fixed z-[100] w-[740px] bg-white border border-zinc-200 rounded-2xl shadow-xl text-primary"
                 :style="popStyle"
                 role="dialog"
                 :aria-label="$t('Select period')"
                 @keydown="onPopKeydown">
                <div class="flex gap-x-1.5 px-4 pt-3.5">
                    <button v-for="chip in modeChips"
                            :key="chip.key"
                            type="button"
                            class="px-3 py-1 rounded-full text-xs border transition duration-150"
                            :class="snapMode === chip.key
                                ? 'bg-artwork-buttons-create/10 border-artwork-buttons-create/40 text-artwork-buttons-create font-semibold'
                                : 'border-zinc-200 text-artwork-context-dark hover:bg-zinc-50'"
                            @click="setSnapMode(chip.key)">
                        {{ chip.label }}
                    </button>
                </div>
                <div class="flex px-4 pt-3.5 gap-x-4">
                    <div class="w-36 shrink-0 flex flex-col gap-y-0.5 pr-3.5 border-r border-zinc-100">
                        <button v-for="preset in presets"
                                :key="preset.label"
                                type="button"
                                class="text-left px-2.5 py-1.5 rounded-lg text-[13px] transition duration-100 whitespace-nowrap"
                                :class="isActivePreset(preset)
                                    ? 'bg-artwork-buttons-create/10 text-artwork-buttons-create font-semibold'
                                    : 'text-zinc-700 hover:bg-zinc-100'"
                                @click="applyPreset(preset)">
                            {{ preset.label }}
                        </button>
                    </div>
                    <div class="flex-1 flex gap-x-4">
                        <div v-for="offset in [0, 1]" :key="offset" class="flex-1">
                            <div class="flex items-center justify-between mb-1.5">
                                <button v-if="offset === 0"
                                        type="button"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-artwork-context-dark hover:bg-zinc-100"
                                        :title="$t('Time range back')"
                                        @click="viewMonth = addMonths(viewMonth, -1)">
                                    <IconChevronLeft class="h-3.5 w-3.5" stroke-width="2"/>
                                </button>
                                <span v-else class="w-7"></span>
                                <span class="text-[13px] font-semibold">{{ monthTitle(addMonths(viewMonth, offset)) }}</span>
                                <button v-if="offset === 1"
                                        type="button"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-artwork-context-dark hover:bg-zinc-100"
                                        :title="$t('Time range forward')"
                                        @click="viewMonth = addMonths(viewMonth, 1)">
                                    <IconChevronRight class="h-3.5 w-3.5" stroke-width="2"/>
                                </button>
                                <span v-else class="w-7"></span>
                            </div>
                            <table class="w-full border-collapse select-none">
                                <thead>
                                <tr>
                                    <th class="w-6 pr-1.5 text-right text-[10px] font-semibold text-zinc-300">{{ weekAbbrev }}</th>
                                    <th v-for="wd in weekdayHeads" :key="wd" class="py-0.5 text-[10.5px] font-semibold text-secondary uppercase tracking-wide">
                                        {{ wd }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="week in monthGrid(addMonths(viewMonth, offset))" :key="week.iso">
                                    <td class="pr-1.5 text-right">
                                        <button type="button"
                                                class="text-[10px] text-zinc-300 hover:text-artwork-buttons-create tabular-nums"
                                                :title="$t('Calendar week') + ' ' + week.kw"
                                                @click="selectWeek(week.monday)">
                                            {{ week.kw }}
                                        </button>
                                    </td>
                                    <td v-for="cell in week.days" :key="cell.iso" class="p-0 text-center">
                                        <button type="button"
                                                class="relative w-8 h-7 text-xs tabular-nums transition-colors duration-75"
                                                :class="dayClasses(cell, addMonths(viewMonth, offset))"
                                                @click="clickDay(cell.date)"
                                                @mouseover="onDayHover(cell.date)">
                                            {{ cell.date.getDate() }}
                                            <span v-if="cell.isToday"
                                                  class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full"
                                                  :class="isEndpoint(cell.date) ? 'bg-white' : 'bg-artwork-buttons-create'"></span>
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-x-2.5 px-4 py-3.5 mt-3 border-t border-zinc-100">
                    <input v-model="inputStart"
                           type="text"
                           class="w-28 px-2.5 py-1.5 text-[13px] border rounded-lg tabular-nums focus:ring-artwork-buttons-hover"
                           :class="inputStartValid ? 'border-zinc-200' : 'border-artwork-messages-error'"
                           :placeholder="datePlaceholder"
                           :aria-label="$t('Start date')"
                           @change="applyInputs"
                           @keydown.enter.prevent="applyInputs(); applyDraft()"/>
                    <span class="text-secondary">–</span>
                    <input v-model="inputEnd"
                           type="text"
                           class="w-28 px-2.5 py-1.5 text-[13px] border rounded-lg tabular-nums focus:ring-artwork-buttons-hover"
                           :class="inputEndValid ? 'border-zinc-200' : 'border-artwork-messages-error'"
                           :placeholder="datePlaceholder"
                           :aria-label="$t('End date')"
                           @change="applyInputs"
                           @keydown.enter.prevent="applyInputs(); applyDraft()"/>
                    <span class="text-xs text-secondary ml-1 tabular-nums">{{ draftDayCount }}</span>
                    <span class="flex-1"></span>
                    <button type="button" class="px-3.5 py-2 rounded-lg text-[13px] text-artwork-context-dark hover:bg-zinc-100" @click="closePopover">
                        {{ $t('Cancel') }}
                    </button>
                    <button type="button"
                            class="px-4 py-2 rounded-lg text-[13px] font-semibold bg-artwork-buttons-create text-white hover:bg-artwork-buttons-hover transition duration-150"
                            @click="applyDraft">
                        {{ $t('Apply') }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
    <div v-else class="font-medium text-gray-900">
        {{ $t('Project period') }}: {{ formatDisplay(appliedStart) }} - {{ formatDisplay(appliedEnd) }}
    </div>
</template>

<script setup>
import {ref, computed, watch, onMounted, onUnmounted, nextTick} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import {IconCalendar, IconChevronDown, IconChevronLeft, IconChevronRight} from '@tabler/icons-vue';
import {useTranslation} from '@/Composeables/Translation.js';
import {DATE_RANGE_MODES} from '@/Artwork/DateRange/dateRangeModes.js';

const $t = useTranslation();

const props = defineProps({
    dateValueArray: {
        type: Array,
        required: true,
    },
    mode: {
        type: String,
        required: true,
        validator: (value) => value in DATE_RANGE_MODES,
    },
    /** Zusätzliche Request-Parameter der jeweiligen Seite (z. B. isPlanning, isDailyView) */
    extraParams: {
        type: Object,
        default: () => ({}),
    },
    project: {
        type: [Object, Boolean],
        default: false,
    },
    showNavigation: {
        type: Boolean,
        default: true,
    },
    showToday: {
        type: Boolean,
        default: true,
    },
    /** Optionaler Ersatz für das Standard-Verhalten des Heute-Buttons (z. B. Daily-View-Wechsel) */
    onTodayOverride: {
        type: Function,
        default: null,
    },
});

// ---------- Datums-Helfer (Strings 'YYYY-MM-DD' <-> Date auf 12:00 lokal, TZ-sicher) ----------
const DAY_MS = 86400000;
const pad = (n) => String(n).padStart(2, '0');
const toIso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const fromIso = (s) => {
    if (s instanceof Date) {
        return atNoon(s);
    }
    if (typeof s === 'string' && /^\d{4}-\d{2}-\d{2}/.test(s)) {
        return new Date(`${s.slice(0, 10)}T12:00:00`);
    }
    return atNoon(new Date(s));
};
const atNoon = (d) => {
    const x = new Date(d);
    x.setHours(12, 0, 0, 0);
    return x;
};
const today = () => atNoon(new Date());
const addDays = (d, n) => atNoon(new Date(d.getTime() + n * DAY_MS));
const addMonths = (d, n) => {
    const lastOfTarget = new Date(d.getFullYear(), d.getMonth() + n + 1, 0).getDate();
    return atNoon(new Date(d.getFullYear(), d.getMonth() + n, Math.min(d.getDate(), lastOfTarget)));
};
const sameDay = (a, b) => a && b && toIso(a) === toIso(b);
const daysBetween = (a, b) => Math.round((b - a) / DAY_MS);
const mondayOf = (d) => addDays(d, -((d.getDay() + 6) % 7));
const monthStart = (d) => atNoon(new Date(d.getFullYear(), d.getMonth(), 1));
const monthEnd = (d) => atNoon(new Date(d.getFullYear(), d.getMonth() + 1, 0));
const isoWeek = (d) => {
    const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    const day = t.getUTCDay() || 7;
    t.setUTCDate(t.getUTCDate() + 4 - day);
    const yearStart = new Date(Date.UTC(t.getUTCFullYear(), 0, 1));
    return Math.ceil(((t - yearStart) / DAY_MS + 1) / 7);
};
const isFullMonth = (a, b) => sameDay(a, monthStart(a)) && sameDay(b, monthEnd(a))
    && a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear();

// ---------- Locale-abhängige Formatierung ----------
const locale = usePage().props.auth?.user?.language || 'de';
const weekAbbrev = locale.startsWith('de') ? 'KW' : 'CW';
const datePlaceholder = locale.startsWith('de') ? 'TT.MM.JJJJ' : 'DD.MM.YYYY';
const weekdayShort = (d) => d.toLocaleDateString(locale, {weekday: 'short'});
const weekdayHeads = computed(() => {
    const monday = mondayOf(today());
    return Array.from({length: 7}, (unused, i) => weekdayShort(addDays(monday, i)).replace('.', '').slice(0, 2));
});
const monthTitle = (d) => d.toLocaleDateString(locale, {month: 'long', year: 'numeric'});
const formatDisplay = (d) => `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
const parseDisplay = (s) => {
    const m = /^\s*(\d{1,2})\.(\d{1,2})\.(\d{4})\s*$/.exec(s);
    if (!m) {
        return null;
    }
    const d = atNoon(new Date(+m[3], +m[2] - 1, +m[1]));
    return (d.getDate() === +m[1] && d.getMonth() === +m[2] - 1) ? d : null;
};

// ---------- State ----------
const appliedStart = ref(fromIso(props.dateValueArray[0] ?? today()));
const appliedEnd = ref(fromIso(props.dateValueArray[1] ?? props.dateValueArray[0] ?? today()));
const draftStart = ref(appliedStart.value);
const draftEnd = ref(appliedEnd.value);
const open = ref(false);
const viewMonth = ref(monthStart(appliedStart.value));
const snapMode = ref('free');
const pendingStart = ref(null);
const hoverDay = ref(null);
const inputStart = ref('');
const inputEnd = ref('');
const inputStartValid = ref(true);
const inputEndValid = ref(true);
const inlineStart = ref(toIso(appliedStart.value));
const inlineEnd = ref(toIso(appliedEnd.value));
const rootEl = ref(null);
const triggerEl = ref(null);
const popEl = ref(null);
const popStyle = ref({});

watch(() => props.dateValueArray, (value) => {
    if (!Array.isArray(value) || !value[0] || !value[1]) {
        return;
    }
    appliedStart.value = fromIso(value[0]);
    appliedEnd.value = fromIso(value[1]);
    syncInline();
}, {deep: true});

// ---------- Anzeige in der Pill ----------
const rangeMeta = computed(() => {
    const kwStart = isoWeek(appliedStart.value);
    const kwEnd = isoWeek(appliedEnd.value);
    const kw = kwStart === kwEnd ? `${weekAbbrev} ${kwStart}` : `${weekAbbrev} ${kwStart}–${kwEnd}`;
    const count = daysBetween(appliedStart.value, appliedEnd.value) + 1;
    return `${kw} · ${count} ${count === 1 ? $t('Day') : $t('Days')}`;
});
const todayInRange = computed(() => {
    const now = today();
    return now >= appliedStart.value && now <= appliedEnd.value;
});
const draftDayCount = computed(() => {
    const count = daysBetween(draftStart.value, draftEnd.value) + 1;
    return `${count} ${count === 1 ? $t('Day') : $t('Days')}`;
});

// ---------- Direkteingabe in der Pill ----------
function syncInline() {
    inlineStart.value = toIso(appliedStart.value);
    inlineEnd.value = toIso(appliedEnd.value);
}

function onInlineFocusOut(event) {
    // Fokuswechsel zwischen Start- und Endfeld darf noch nicht committen —
    // sonst wird eine halb editierte Range sofort submittet (und bei
    // preserveState:false remountet die Seite mitten in der Eingabe).
    const next = event?.relatedTarget;
    if (next instanceof HTMLElement && next.classList.contains('drc-date-input')) {
        return;
    }
    commitInline();
}

function commitInline() {
    const start = inlineStart.value ? fromIso(inlineStart.value) : null;
    const end = inlineEnd.value ? fromIso(inlineEnd.value) : null;
    if (!start || !end || start.getFullYear() < 1900 || end.getFullYear() < 1900) {
        syncInline();
        return;
    }
    const [lo, hi] = start <= end ? [start, end] : [end, start];
    if (sameDay(lo, appliedStart.value) && sameDay(hi, appliedEnd.value)) {
        syncInline();
        return;
    }
    submitRange(lo, hi);
}

// ---------- Routing ----------
function submitRange(start, end) {
    const config = DATE_RANGE_MODES[props.mode];
    appliedStart.value = start;
    appliedEnd.value = end;
    syncInline();

    if (config.reload) {
        router.reload({
            data: {start: toIso(start), end: toIso(end)},
            preserveState: true,
        });
        return;
    }

    router.patch(route(config.routeName, usePage().props.auth.user.id), {
        start_date: toIso(start),
        end_date: toIso(end),
        ...props.extraParams,
    }, {
        preserveScroll: config.preserveScroll ?? true,
        preserveState: config.preserveState ?? false,
    });
}

// ---------- Verschieben / Heute ----------
function shiftRange(direction) {
    const start = appliedStart.value;
    const end = appliedEnd.value;
    if (isFullMonth(start, end)) {
        const next = addMonths(start, direction);
        submitRange(monthStart(next), monthEnd(next));
        return;
    }
    const length = daysBetween(start, end) + 1;
    submitRange(addDays(start, direction * length), addDays(end, direction * length));
}

function jumpToToday() {
    if (props.onTodayOverride) {
        props.onTodayOverride();
        return;
    }
    const now = today();
    const start = appliedStart.value;
    const end = appliedEnd.value;
    const length = daysBetween(start, end) + 1;
    if (isFullMonth(start, end)) {
        submitRange(monthStart(now), monthEnd(now));
    } else if (length === 7 && start.getDay() === 1) {
        submitRange(mondayOf(now), addDays(mondayOf(now), 6));
    } else if (length === 1) {
        submitRange(now, now);
    } else {
        submitRange(now, addDays(now, length - 1));
    }
}

// ---------- Popover ----------
function togglePopover() {
    open.value ? closePopover() : openPopover();
}

async function openPopover() {
    draftStart.value = appliedStart.value;
    draftEnd.value = appliedEnd.value;
    viewMonth.value = monthStart(appliedStart.value);
    snapMode.value = detectSnapMode(appliedStart.value, appliedEnd.value);
    pendingStart.value = null;
    syncInputs();
    open.value = true;
    await nextTick();
    positionPopover();
}

function closePopover() {
    open.value = false;
    pendingStart.value = null;
    triggerEl.value?.focus();
}

function positionPopover() {
    if (!triggerEl.value || !popEl.value) {
        return;
    }
    const rect = triggerEl.value.getBoundingClientRect();
    const popWidth = popEl.value.offsetWidth || 740;
    const left = Math.max(8, Math.min(rect.left, window.innerWidth - popWidth - 8));
    const popHeight = popEl.value.offsetHeight || 380;
    const below = rect.bottom + 8;
    const top = below + popHeight > window.innerHeight - 8
        ? Math.max(8, rect.top - popHeight - 8)
        : below;
    popStyle.value = {top: `${top}px`, left: `${left}px`};
}

function applyDraft() {
    submitRange(draftStart.value, draftEnd.value);
    closePopover();
}

// ---------- Auswahl im Kalender ----------
function detectSnapMode(start, end) {
    if (sameDay(start, end)) {
        return 'day';
    }
    if (daysBetween(start, end) === 6 && start.getDay() === 1) {
        return 'week';
    }
    if (isFullMonth(start, end)) {
        return 'month';
    }
    return 'free';
}

const modeChips = computed(() => [
    {key: 'day', label: $t('Day')},
    {key: 'week', label: $t('Week')},
    {key: 'month', label: $t('Month')},
    {key: 'free', label: $t('Free selection')},
]);

function setSnapMode(key) {
    snapMode.value = key;
    if (key === 'day') {
        setDraft(draftStart.value, draftStart.value);
    } else if (key === 'week') {
        setDraft(mondayOf(draftStart.value), addDays(mondayOf(draftStart.value), 6));
    } else if (key === 'month') {
        setDraft(monthStart(draftStart.value), monthEnd(draftStart.value));
    }
}

function setDraft(a, b) {
    draftStart.value = a <= b ? a : b;
    draftEnd.value = a <= b ? b : a;
    pendingStart.value = null;
    hoverDay.value = null;
    syncInputs();
}

function syncInputs() {
    inputStart.value = formatDisplay(draftStart.value);
    inputEnd.value = formatDisplay(draftEnd.value);
    inputStartValid.value = true;
    inputEndValid.value = true;
}

function clickDay(date) {
    if (snapMode.value === 'day') {
        return setDraft(date, date);
    }
    if (snapMode.value === 'week') {
        return setDraft(mondayOf(date), addDays(mondayOf(date), 6));
    }
    if (snapMode.value === 'month') {
        return setDraft(monthStart(date), monthEnd(date));
    }
    if (!pendingStart.value) {
        pendingStart.value = date;
        hoverDay.value = date;
    } else {
        setDraft(pendingStart.value, date);
    }
}

function selectWeek(monday) {
    setDraft(monday, addDays(monday, 6));
}

function onDayHover(date) {
    if (pendingStart.value) {
        hoverDay.value = date;
    }
}

function applyPreset(preset) {
    const [a, b] = preset.range();
    setDraft(a, b);
    viewMonth.value = monthStart(a);
    snapMode.value = detectSnapMode(a, b);
}

function isActivePreset(preset) {
    const [a, b] = preset.range();
    return sameDay(a, draftStart.value) && sameDay(b, draftEnd.value);
}

const presets = computed(() => {
    const now = today();
    return [
        {label: $t('Today'), range: () => [now, now]},
        {label: $t('Current week'), range: () => [mondayOf(now), addDays(mondayOf(now), 6)]},
        {label: $t('Current month'), range: () => [monthStart(now), monthEnd(now)]},
        {label: $t('Current year'), range: () => [atNoon(new Date(now.getFullYear(), 0, 1)), atNoon(new Date(now.getFullYear(), 11, 31))]},
        {label: $t('Next 7 days'), range: () => [now, addDays(now, 6)]},
        {label: $t('Next 14 days'), range: () => [now, addDays(now, 13)]},
        {label: $t('Next 30 days'), range: () => [now, addDays(now, 29)]},
        {label: $t('Next 90 days'), range: () => [now, addDays(now, 89)]},
        {label: $t('Next 12 months'), range: () => [now, addDays(addMonths(now, 12), -1)]},
    ];
});

// ---------- Kalender-Grid ----------
function monthGrid(base) {
    const gridStart = mondayOf(monthStart(base));
    const weeks = [];
    for (let row = 0; row < 6; row++) {
        const monday = addDays(gridStart, row * 7);
        if (row > 0 && monday.getMonth() !== base.getMonth() && monday > monthEnd(base)) {
            break;
        }
        weeks.push({
            iso: toIso(monday),
            kw: isoWeek(monday),
            monday,
            days: Array.from({length: 7}, (unused, i) => {
                const date = addDays(monday, i);
                return {iso: toIso(date), date, isToday: sameDay(date, today())};
            }),
        });
    }
    return weeks;
}

const previewRange = computed(() => {
    if (pendingStart.value) {
        const other = hoverDay.value ?? pendingStart.value;
        return pendingStart.value <= other
            ? [pendingStart.value, other]
            : [other, pendingStart.value];
    }
    return [draftStart.value, draftEnd.value];
});

function isEndpoint(date) {
    const [lo, hi] = previewRange.value;
    return sameDay(date, lo) || sameDay(date, hi);
}

function dayClasses(cell, monthBase) {
    const [lo, hi] = previewRange.value;
    const inRange = cell.date >= lo && cell.date <= hi;
    if (isEndpoint(cell.date)) {
        return 'bg-artwork-buttons-create text-white font-semibold rounded-lg';
    }
    if (inRange) {
        return 'bg-artwork-buttons-create/10 text-zinc-700';
    }
    if (cell.date.getMonth() !== monthBase.getMonth()) {
        return 'text-zinc-300 hover:bg-zinc-100 rounded-lg';
    }
    return 'text-zinc-700 hover:bg-zinc-100 rounded-lg';
}

// ---------- Manuelle Eingabe ----------
function applyInputs() {
    const parsedStart = parseDisplay(inputStart.value);
    const parsedEnd = parseDisplay(inputEnd.value);
    inputStartValid.value = !!parsedStart;
    inputEndValid.value = !!parsedEnd;
    if (parsedStart && parsedEnd) {
        setDraft(parsedStart, parsedEnd);
        viewMonth.value = monthStart(draftStart.value);
        snapMode.value = detectSnapMode(draftStart.value, draftEnd.value);
    }
}

// ---------- Tastatur ----------
function onRootKeydown(event) {
    if (event.target.tagName === 'INPUT') {
        return;
    }
    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        shiftRange(-1);
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        shiftRange(1);
    } else if (event.key === 't' || event.key === 'T') {
        jumpToToday();
    }
}

function onPopKeydown(event) {
    if (event.key === 'Escape') {
        event.stopPropagation();
        closePopover();
    } else if (event.key === 'Enter' && event.target.tagName !== 'INPUT' && event.target.tagName !== 'BUTTON') {
        event.preventDefault();
        applyDraft();
    }
}

// ---------- Outside-Click & Repositionierung ----------
function onDocumentClick(event) {
    if (!open.value) {
        return;
    }
    if (rootEl.value?.contains(event.target) || popEl.value?.contains(event.target)) {
        return;
    }
    open.value = false;
    pendingStart.value = null;
}

function onViewportChange() {
    if (open.value) {
        positionPopover();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});
</script>

<style scoped>
/* Native Date-Inputs in der Pill: nur das Textfeld, kein Browser-Kalender-Icon */
.drc-date-input::-webkit-calendar-picker-indicator {
    display: none;
}

.drc-date-input {
    appearance: none;
    -moz-appearance: textfield;
}
</style>
