<template>
    <!-- Wurzel-Element trägt die Daten-Attribute für die Observer in BaseCalendar
         (Monatsfokus + Zeilen-Sichtbarkeit). Monatsbalken + Tageszeile bilden
         zusammen EINE Einheit, damit die Zeilen-Komponente als Ganzes nur dann
         re-rendert, wenn sich ihre eigenen Daten ändern. -->
    <div ref="rootEl" :data-month="monthKey" :data-row-day="day.withoutFormat">
        <!-- Monatsgrenze: schwarzer Balken über die ganze Zeile mit Monat + Jahr;
             auch für den allerersten Tag des Zeitraums, sonst fehlt dem obersten
             Block bei Start mitten im Monat die Orientierung -->
        <div
            v-if="(day.isFirstDayOfMonth || isFirstRow) && !day.isExtraRow"
            class="month-separator flex items-center h-7 w-full bg-surface-inverse"
        >
            <span class="month-separator-label text-xs font-semibold text-white whitespace-nowrap px-3">
                {{ formatMonthLabel(day.withoutFormat) }}
            </span>
        </div>
        <div
            class="flex gap-0.5 day-container border-t"
            :class="settings.high_contrast ? 'border-gray-500' : 'border-gray-300'"
            :style="[dayRowStyle, dayRowBackgroundStyle]"
            :data-day="day.fullDay"
            :data-day-to-jump="day.withoutFormat"
        >
            <SingleDayInCalendar v-if="!day.isExtraRow" :isFullscreen="isFullscreen" :day="day" :sticky-top="dayStickyTop" />

            <!-- Tagesbemerkungen: sticky Spalte direkt neben dem Datum (Inline-Editor in der Zelle) -->
            <DayRemarkCell
                v-if="dayRemarksColumnVisible && !day.isExtraRow"
                :day="day"
                :editable="dayRemarksCanEdit"
                :is-fullscreen="isFullscreen"
                :sticky-top="dayStickyTop"
            />

            <!-- Räume -->
            <template v-if="!day.isExtraRow">
                <template v-for="(room, roomIdx) in calendarRooms" :key="room.roomId ?? room.id ?? roomIdx">
                    <!-- Eine Cell (Tag × Raum) -->
                    <section
                        :style="[cellStyle, cellSeparatorStyle]"
                        :id="`scroll_container-${day.withoutFormat}`"
                        :data-room-id="room.roomId ?? room.id"
                        :class="[
                            'group/container relative',
                            multiEdit ? 'cursor-pointer' : '',
                        ]"
                        @click="emit('toggle-cell', day, room)"
                    >
                        <!-- Multi-Edit: Zellen-Auswahl-Overlay (Klick auf freie Fläche;
                             Termin-Klicks stoppen die Propagation und landen hier nicht).
                             Nur in Zeilen im Renderfenster — sonst mountet der Toggle
                             ~7.000 Overlays auf einmal (mehrsekündige Blockade). -->
                        <div
                            v-if="multiEdit && visible"
                            class="absolute inset-0 z-30 pointer-events-none"
                            :class="isCellSelected(room)
                                ? 'border-2 border-dashed border-accent-600 bg-accent-600/10'
                                : 'group-hover/container:border group-hover/container:border-dashed group-hover/container:border-accent-500/70'"
                        >
                            <div
                                v-if="isCellSelected(room)"
                                class="absolute top-0.5 right-0.5 rounded-full bg-accent-600 text-white p-0.5"
                            >
                                <component :is="IconCirclePlus" class="size-3.5" stroke-width="2" />
                            </div>
                        </div>
                        <!-- INNERER WRAPPER: hält Scrollbereich + Floating-Buttons -->
                        <div :class="['relative w-full', settings.expand_days ? '' : 'h-full']">
                            <!-- SCROLLBAR NUR WENN SINNVOLL -->
                            <div
                                :class="[
                                    'events-scroll',
                                    settings.expand_days ? '' : 'h-full',
                                    settings.expand_days ? 'overflow-visible flex flex-col' : 'overflow-auto cell'
                                ]"
                                :style="cellStyle"
                            >
                                <!-- Inhalte nur, wenn die Zeile im Renderfenster liegt (visible-Prop
                                     aus BaseCalendar, mit Hysterese gegen Remount-Flackern) -->
                                <template v-if="visible">
                                    <template v-for="(item, idx) in cellItems[roomIdx]" :key="`${item.type}-${item.data.id}`">
                                        <div
                                            v-if="item.type === 'shift'"
                                            class="py-0.5"
                                            @click.stop
                                        >
                                            <ShiftInCalendarCell
                                                :shift="item.data"
                                                :day="rowDayKey"
                                                @shift-edited="emit('shift-edited', day)"
                                            />
                                        </div>
                                        <div
                                            v-else
                                            :class="[
                                                'py-0.5',
                                                (settings.expand_days && !!item.data.allDay) ? 'flex-1 min-h-0' : ''
                                            ]"
                                            :id="`event_scroll-${idx}-day-${day.withoutFormat}-room-${(room.roomId ?? room.id)}`"
                                            @click="onEventClick(item.data, $event)"
                                        >
                                            <AsyncSingleEventInCalendar
                                                :event="item.data"
                                                :multi-edit="multiEdit"
                                                :font-size="fontSize"
                                                :line-height="lineHeight"
                                                :rooms="rooms"
                                                :has-admin-role="isAdmin"
                                                :width="eventCardWidth"
                                                :first_project_tab_id="firstProjectTabId"
                                                :firstProjectShiftTabId="firstProjectShiftTabId"
                                                :verifierForEventTypIds="verifierForEventTypIds"
                                                :is-planning="isPlanning"
                                                :is-height-full="settings.expand_days && !!item.data.allDay"
                                                :cell-day="day.withoutFormat"
                                                @edit-event="e => emit('edit-event', e)"
                                                @edit-sub-event="(...args) => emit('open-add-sub-event-modal', ...args)"
                                                @open-add-sub-event-modal="(...args) => emit('open-add-sub-event-modal', ...args)"
                                                @open-confirm-modal="(e, type) => emit('open-confirm-modal', e, type)"
                                                @show-decline-event-modal="e => emit('show-decline-event-modal', e)"
                                                @accept-room-request="e => emit('accept-room-request', e)"
                                                @changed-multi-edit-checkbox="(...args) => emit('changed-multi-edit-checkbox', ...args)"
                                            />
                                        </div>
                                    </template>
                                    <!-- Platzhalter: weicher Abschluss, wenn wenig Inhalt. Nicht im
                                         Kompaktmodus: dort würde er die Einzeiler-Kachel über die
                                         flache Zeilenhöhe drücken und unnötige Scrollbalken erzeugen. -->
                                    <div v-if="cellItems[roomIdx].length <= 1 && !settings.expand_days && !isCompact" class="h-2"></div>
                                </template>
                            </div>

                            <!-- "+"-Button: OBEN RECHTS außerhalb des Scrollbereichs.
                                 Im Multi-Edit ausgeblendet — dort wählt der Zellen-Klick die Zelle aus.
                                 Nur in Zeilen im Renderfenster (sonst ~7.000 versteckte Buttons im DOM). -->
                            <button
                                v-if="!multiEdit && visible"
                                type="button"
                                class="pointer-events-auto group-hover/container:inline-flex hidden absolute bottom-1 right-3 z-20
                                 items-center justify-center cursor-pointer gap-1 rounded-md text-sm font-medium
                                 ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0
                                 transition duration-200 ease-in-out"
                                :style="addEventButtonStyle"
                                :aria-label="$t('Add event')"
                                @click.stop="emit('open-new-event', day.withoutFormat, (room.roomId ?? room.id))"
                            >
                                <component :is="IconCirclePlus" :style="addEventIconStyle" />
                            </button>

                        </div>
                    </section>
                </template>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { IconCirclePlus } from "@tabler/icons-vue";
import { useCalendarZoom } from "@/Composeables/useCalendarZoom.js";
import { useTranslation } from "@/Composeables/Translation.js";
import { formatMonthLabel } from "@/Composeables/calendarDateUtils.js";
import { dayKey, itemsInCell } from "@/Components/Calendar/calendarCellItems.js";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";

// Async-Wrapper auf Modul-Ebene: eine Definition für alle 365 Zeilen-Instanzen,
// Code-Splitting bleibt wie zuvor in BaseCalendar erhalten.
const SingleDayInCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/SingleDayInCalendar.vue") });
const DayRemarkCell = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/DayRemarkCell.vue") });
const ShiftInCalendarCell = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/ShiftInCalendarCell.vue") });
const AsyncSingleEventInCalendar = defineAsyncComponent({
    loader: () => import("@/Components/Calendar/Elements/SingleEventInCalendar.vue"),
    loadingComponent: CalendarPlaceholder,
});

const props = defineProps({
    day: { type: Object, required: true },
    isFirstRow: { type: Boolean, default: false },
    // Zeile liegt im Renderfenster → Zell-Inhalte mounten
    visible: { type: Boolean, default: false },
    // Kalender-Daten (newCalendarData aus BaseCalendar, Identität stabil)
    calendarRooms: { type: Array, required: true },
    // Raumliste der Seite (für die Termin-Kacheln)
    rooms: { type: [Object, Array], default: () => [] },
    multiEdit: { type: Boolean, default: false },
    isFullscreen: { type: Boolean, default: false },
    dayStickyTop: { type: Number, default: 0 },
    dayRemarksColumnVisible: { type: Boolean, default: false },
    dayRemarksCanEdit: { type: Boolean, default: false },
    // Map der Zellen-Auswahl im Multi-Edit (null wenn Multi-Edit aus)
    selectedCells: { type: Map, default: null },
    isAdmin: { type: Boolean, default: false },
    isPlanning: { type: Boolean, default: false },
    verifierForEventTypIds: { type: Array, default: () => [] },
    firstProjectTabId: { type: [String, Number], default: null },
    firstProjectShiftTabId: { type: [String, Number], default: null },
    // Stabile Callbacks aus BaseCalendar für die Observer-Registrierung
    registerRowEl: { type: Function, default: null },
    unregisterRowEl: { type: Function, default: null },
});

const emit = defineEmits([
    'edit-event',
    'open-add-sub-event-modal',
    'open-confirm-modal',
    'show-decline-event-modal',
    'accept-room-request',
    'changed-multi-edit-checkbox',
    'shift-edited',
    'toggle-cell',
    'open-new-event',
]);

const $t = useTranslation();
const page = usePage();
const settings = computed(() => page.props.auth.user.calendar_settings);

const {
    columnWidth,
    rowHeight,
    eventCardWidth,
    isCompact,
    fontSize,
    lineHeight,
} = useCalendarZoom();

const monthKey = computed(() => (props.day.withoutFormat || '').slice(0, 7));
const rowDayKey = computed(() => dayKey(props.day));

// ----- Styles (identisch zur früheren Inline-Logik in BaseCalendar) -----
const rowHeightPx = computed(() => `${rowHeight.value}px`);
const dayRowStyle = computed(() => ({
    height: settings.value.expand_days ? "" : rowHeightPx.value,
    minHeight: settings.value.expand_days ? rowHeightPx.value : ""
}));
const cellStyle = computed(() => ({
    minWidth: `${columnWidth.value}px`,
    maxWidth: `${columnWidth.value}px`,
    height: settings.value.expand_days ? "" : rowHeightPx.value,
    minHeight: settings.value.expand_days ? rowHeightPx.value : ""
}));
// Vertikale Trennlinie zwischen den Raumspalten
const cellSeparatorStyle = computed(() => ({
    borderLeft: `1px solid ${settings.value.high_contrast ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.18)'}`
}));

// "+"-Button: 40px Zielgröße, aber nie höher als die Zoom-Zeilenhöhe
const addEventButtonSize = computed(() => Math.min(40, Math.max(20, rowHeight.value - 8)));
const addEventButtonStyle = computed(() => ({
    width: `${addEventButtonSize.value}px`,
    height: `${addEventButtonSize.value}px`
}));
const addEventIconStyle = computed(() => {
    const size = Math.round(addEventButtonSize.value * 0.65);
    return { width: `${size}px`, height: `${size}px` };
});

// Wochenend-/Feiertags-Einfärbung der ganzen Tageszeile (inkl. Datumsspalte).
// Feiertag schlägt Wochenende; eintägige Feiertage färben deutlich, mehrtägige
// Zeiträume (Ferien) nur sehr dezent; Wochenenden im Ferienband kräftiger.
const dayTintColor = computed(() => {
    const day = props.day;
    if (!day || day.isExtraRow) return null;
    const holidays = day.holidays ?? [];
    const singleDayHoliday = holidays.find(
        (holiday) => holiday?.color && (!holiday.end_date || holiday.end_date === holiday.date)
    );
    const coloredHoliday = singleDayHoliday ?? holidays.find((holiday) => holiday?.color);
    if (coloredHoliday) {
        let alpha;
        if (singleDayHoliday) {
            alpha = settings.value.high_contrast ? '59' : '33';
        } else if (day.isWeekend) {
            alpha = settings.value.high_contrast ? '66' : '40';
        } else {
            alpha = settings.value.high_contrast ? '33' : '1A';
        }
        return `${coloredHoliday.color}${alpha}`;
    }
    if (day.isWeekend) {
        return settings.value.high_contrast ? '#dbeafe' : '#eff6ff';
    }
    return null;
});
const dayRowBackgroundStyle = computed(() =>
    dayTintColor.value ? { backgroundColor: dayTintColor.value } : {}
);

// Zell-Inhalte pro Raum: computed cached das Mischen+Sortieren — es läuft nur
// neu, wenn sich die Tages-Slots DIESER Zeile ändern (Slot-Ersatz beim
// Monats-Merge oder reaktive In-Place-Mutation durch Broadcasts), nicht bei
// jedem Scroll-Tick.
const EMPTY = [];
const cellItems = computed(() => {
    if (!props.visible || props.day.isExtraRow) {
        return props.calendarRooms.map(() => EMPTY);
    }
    return props.calendarRooms.map((room) => itemsInCell(props.day, room));
});

const isCellSelected = (room) =>
    !!props.selectedCells?.has(`${props.day.withoutFormat}:${(room.roomId ?? room.id)}`);

// Multi-Edit: Klick auf einen Termin toggelt seine Auswahl
const onEventClick = (evt, e) => {
    if (!props.multiEdit) return;
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const nextState = !(evt?.considerOnMultiEdit === true);
    emit('changed-multi-edit-checkbox', evt.id, nextState, (evt?.room_id ?? evt?.roomId ?? null));
};

// Observer-Registrierung (Monatsfokus + Zeilen-Sichtbarkeit) in BaseCalendar
const rootEl = ref(null);
onMounted(() => {
    if (rootEl.value && props.registerRowEl) props.registerRowEl(rootEl.value, props.day);
});
onBeforeUnmount(() => {
    if (rootEl.value && props.unregisterRowEl) props.unregisterRowEl(rootEl.value, props.day);
});
</script>

<style scoped>
/* Monatsname mittig zur sichtbaren Bildschirmbreite: sticky hält das Label
   beim horizontalen Scrollen durch das breite Grid in der Viewport-Mitte. */
.month-separator-label {
    position: sticky;
    left: 50vw;
    transform: translateX(-50%);
}

.cell {
    overflow: auto;
    scrollbar-color: rgba(156,163,175,0.5) transparent; /* Firefox */
    scrollbar-width: thin;
}
/* WebKit */
.cell::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
.cell::-webkit-scrollbar-thumb { background-color: rgba(156,163,175,0.5); border-radius: 3px; }
.cell::-webkit-scrollbar-thumb:hover { background-color: rgba(107,114,128,0.7); }
.cell::-webkit-scrollbar-track { background-color: transparent; }
</style>
