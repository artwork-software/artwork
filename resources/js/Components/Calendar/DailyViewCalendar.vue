<template>
    <div class="w-max -ml-3">
        <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-40 top-[71px] rounded-lg">
            <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
            <div v-for="room in calendarData" :key="room.roomId ?? room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                <SingleRoomInHeader :room="room" is-light />
            </div>
        </div>
        <div v-for="day in days">
            <div class="flex items-center sticky gap-0.5 h-16 bg-gray-100 z-30 first-line:divide-none top-34 rounded-r-lg">
                <div class="flex xxsDark items-center px-8 mt-1.5" :style="{marginLeft: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}">
                    {{ daysWithoutEventsToDisplayHiddenHours.includes(day.fullDay)
                    ? $t('Hours between {start} - {end} are hidden', {
                        start: usePage().props.calendarHours[0],
                        end: usePage().props.calendarHours[usePage().props.calendarHours.length - 1]
                    })
                    : '' }}
                </div>
                <div class="flex items-center h-full truncate">
                    <div class="flex xsDark items-center mt-1.5">
                        {{ day.dayString}}, {{ day.fullDay}}
                    </div>
                    <HolidayToolTip v-if="day?.holidays?.length > 0" class="mt-2">
                        <div class="space-y-1 divide-dashed divide-gray-500 divide-y">
                            <div v-for="holiday in day.holidays" class="pt-1">
                                <div :style="{ color: holiday.color}">
                                    <div>{{ holiday.name }}</div>
                                    <div v-if="holiday.subdivisions.length > 0">
                                        {{ holiday.subdivisions.map((person) => person).join(', ') }}
                                    </div>
                                    <div v-else>
                                        {{ $t('Germany-wide') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </HolidayToolTip>
                </div>
                <div class="ml-10 flex items-center h-full truncate">
                    <div class="flex items-center h-full gap-x-2" v-if="(usePage().props.daily_view_calendar_settings ?? usePage().props.auth.user.calendar_settings)?.display_project_groups" v-for="group in getAllProjectGroupsInAllRoomsAndEventsByDay(day)" :key="group.id">
                        <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectTabId })" class=" text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1 border" :style="{ backgroundColor: group.color + '22' ?? '#ccc', color: group.color, borderColor: group.color }">
                            <component :is="group.icon" class="size-4" aria-hidden="true"/>
                            <span>{{ group.name }}</span>
                        </Link>
                    </div>
                </div>
            </div>
            <template v-for="(hour, hourIndex) in day.hoursOfDay" :key="hourIndex">
                <div v-if="shouldShowHour(hour, calendarData, day)" class="border-b border-gray-300 border-dashed" :style="{ position: 'relative', zIndex: 25 - hourIndex }">
                    <div class="w-fit events-by-days-container rounded-lg" ref="calendarToCalculate">
                        <div :key="day.fullDay"
                             :style="{ height: zoom_factor * 115 + 'px' }"
                             class="flex gap-0.5 day-container"
                             :data-day="day.fullDay"
                             :data-day-to-jump="day.withoutFormat">

                            <SingleDayInCalendar :hour="hour" />

                            <div v-for="room in calendarData" :key="room.id" class="">
                                <div :style="{
                                    minWidth: zoom_factor * 212 + 'px',
                                    maxWidth: zoom_factor * 212 + 'px',
                                    height: zoom_factor * 115 + 'px',
                                    minHeight: zoom_factor * 115 + 'px',
                                    overflow: 'visible',
                                    }"
                                     class="group/container"
                                     :id="'scroll_container-' + day.withoutFormat">
                                    <!-- Container für die Events -->
                                    <div :class="{'relative grid': hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour)}" :style="hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour) ? { gridTemplateColumns: 'repeat(auto-fit, minmax(' + zoom_factor * 100 + 'px, 1fr))' } : {}">
                                        <div v-for="(event, index) in (room.content[day.fullDay]?.events || [])" :key="event.id">
                                            <div v-if="event && shouldRenderEvent(event, day, hour)"
                                                 class="rounded-lg relative z-10"
                                                 :id="'event_scroll-' + index + '-day-' + day.withoutFormat"
                                                 :style="getEventStyle(event, day, hour, zoom_factor)"
                                                 @click="onEventClick(event, $event)">
                                                <SingleEventInCalendar
                                                    :event="event"
                                                    :project="project"
                                                    :rooms="rooms"
                                                    :first-project-shift-tab-id="firstProjectShiftTabId"
                                                    :first_project_tab_id="firstProjectTabId"
                                                    :multi-edit="multiEdit"
                                                    :width="zoom_factor * 212"
                                                    :is-height-full="true"
                                                    @edit-event="showEditEventModel"
                                                    @edit-sub-event="openAddSubEventModal"
                                                    @open-add-sub-event-modal="openAddSubEventModal"
                                                    @open-confirm-modal="openDeleteEventModal"
                                                    @show-decline-event-modal="openDeclineEventModal"
                                                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

</template>

<script setup>
import {ref} from "vue";
import {Link, usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import SingleEventInCalendar from "@/Components/Calendar/Elements/SingleEventInCalendar.vue";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import {usePermission} from "@/Composeables/Permission.js";
const { can, canAny, hasAdminRole } = usePermission(usePage().props)


const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
const props = defineProps({
    rooms: {
        type: Object,
        required: true,
    },
    days: {
        type: Array,
        required: true,
    },
    calendarData: {
        type: Object,
        required: true,
    },
    project: {
        type: Object,
        default: null,
        required: false,
    },
    eventsWithoutRoom: {
        type: Object,
        required: false,
    },
    projectNameUsedForProjectTimePeriod: {
        type: String,
        required: false,
        default: ''
    },
    firstProjectShiftTabId: {
        type: [String, Number],
        required: false,
        default: null
    },
    eventStatuses: {
        type: Object,
        required: false,
        default: null
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    firstProjectTabId: {
        type: [String, Number],
        required: false,
        default: null
    },
});

const emits = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal',
    'changedMultiEditCheckbox'
]);

const daysWithoutEventsToDisplayHiddenHours = ref([]);

const timeToMinutes = (t) => {
    if (typeof t === 'number') return t * 60;
    if (!t) return 0;
    const parts = String(t).split(':');
    const h = parseInt(parts[0], 10) || 0;
    const m = parseInt(parts[1], 10) || 0;
    return h * 60 + m;
};

// Parse "HH:mm" string to total minutes (0..1439)
const parseHHMM = (str) => {
    if (!str || typeof str !== 'string' || !str.includes(':')) return 0;
    const [hh, mm = '0'] = str.split(':');
    return (parseInt(hh, 10) || 0) * 60 + (parseInt(mm, 10) || 0);
};

const MIN_EVENT_HEIGHT_PX = 25;

const extractEndMinutes = (event, pos, startMin) => {
    // Priority 1: formattedDates.endTime (always correct local time from backend)
    const endTimeStr = event?.formattedDates?.endTime;
    if (endTimeStr && typeof endTimeStr === 'string' && endTimeStr.includes(':')) {
        return parseHHMM(endTimeStr);
    }

    // Priority 2: parse event.end
    const endStr = event?.end ? String(event.end) : null;
    if (!endStr) return null;
    const d = new Date(endStr);
    if (!isNaN(d.getTime())) {
        return d.getHours() * 60 + d.getMinutes();
    }
    const timePart = endStr.split(' ')[1] || endStr.split('T')[1];
    if (timePart && timePart.includes(':')) {
        const [eh, em] = timePart.split(':');
        return (parseInt(eh, 10) || 0) * 60 + (parseInt(em, 10) || 0);
    }

    // Priority 3: eventLengthInHours fallback (only for single-day)
    if (pos === 'single') {
        const durationMinutes = Math.max(0, Math.round((parseFloat(event?.eventLengthInHours) || 0) * 60));
        if (durationMinutes > 0 && Number.isFinite(startMin)) {
            return Math.min(24 * 60, startMin + durationMinutes);
        }
    }

    return null;
};

const clampMinute = (n) => Math.max(0, Math.min(1439, Number.isFinite(n) ? n : 0));

const eventStartMinutesLocal = (event) => {
    const h = parseInt(event?.startHour, 10) || 0;
    const m = parseInt(event?.minutesFormStartHourToStart, 10) || 0;
    return clampMinute(h * 60 + m);
};

const eventEndMinutesLocal = (event) => {
    // PRIORITY 1: formattedDates.endTime (always correct local time from backend)
    const endTimeStr = event?.formattedDates?.endTime;
    if (endTimeStr && typeof endTimeStr === 'string' && endTimeStr.includes(':')) {
        return clampMinute(parseHHMM(endTimeStr));
    }

    // PRIORITY 2: Explizite Felder
    if (event?.endHour != null) {
        const h = parseInt(event.endHour, 10) || 0;
        const m = parseInt(event?.minutesFormEndHourToEnd, 10) || 0;
        return clampMinute(h * 60 + m);
    }

    // PRIORITY 3: Endzeit aus event.end
    const endStr = event?.end ? String(event.end) : null;
    if (!endStr) return null;

    const d = new Date(endStr);
    if (!isNaN(d.getTime())) {
        return clampMinute(d.getHours() * 60 + d.getMinutes());
    }

    const timePart = endStr.split(' ')[1] || endStr.split('T')[1] || '';
    if (timePart && timePart.includes(':')) {
        const [eh, em = '0'] = timePart.split(':');
        const hh = parseInt(eh, 10); const mm = parseInt(em, 10) || 0;
        if (Number.isFinite(hh)) return clampMinute(hh * 60 + mm);
    }

    return null;
};


const dayPosOf = (event, fullDay) => {
    const days = Array.isArray(event?.daysOfEvent) ? event.daysOfEvent : [];
    const idx = days.indexOf(fullDay);
    if (idx === -1) return 'none';
    if (days.length === 1) return 'single';
    if (idx === 0) return 'start';
    if (idx === days.length - 1) return 'end';
    return 'middle';
};

// Set aller versteckten Stunden-Nummern (0-23) aus calendarHours.
// Letztes Element ist die exklusive Grenze (NICHT versteckt), alle anderen sind versteckt.
// Funktioniert korrekt bei Wrap-Around (z.B. [21,22,23,0,1,...,14]).
const getHiddenHourNumbers = (calendarHours) => {
    if (!Array.isArray(calendarHours) || calendarHours.length <= 1) return new Set();
    const set = new Set();
    for (let i = 0; i < calendarHours.length - 1; i++) {
        const h = calendarHours[i];
        const hourNum = typeof h === 'number' ? h : (parseInt(String(h), 10) || 0);
        set.add(hourNum);
    }
    return set;
};

const eventIntervalOnThisDay = (ev, day) => {
    // Liefert das belegte Intervall [fromMin, toMin) in Minuten (0..1440) für GENAU diesen Tag
    // oder null, wenn an diesem Tag kein Intervall dargestellt werden sollte.
    const pos = dayPosOf(ev, day.fullDay);

    // All-day events should NOT trigger unhiding of hidden hours
    if (ev?.allDay) return null;

    const startMin = eventStartMinutesLocal(ev); // 0..1439
    const endMin   = eventEndMinutesLocal(ev);   // 0..1439 oder null bei fehlendem End

    switch (pos) {
        case 'middle':
            return [0, 1440];

        case 'start': {
            // Start-Tag eines mehrtägigen Events: von Start bis 24:00
            return [startMin, 1440];
        }

        case 'end': {
            // End-Tag eines mehrtägigen Events: 00:00 bis Endzeit
            const to = Number.isFinite(endMin) ? Math.max(0, Math.min(1440, endMin)) : 0;
            return [0, to];
        }

        case 'single': {
            // Single-Day: [start, end); robust gegen fehlendes endMin
            if (Number.isFinite(endMin)) {
                if (endMin === startMin) return null;      // Null-Länge → ignorieren
                if (endMin > startMin)   return [startMin, endMin];
                // Falls doch „über Mitternacht“ geliefert wäre (sollte bei single nicht passieren),
                // nehmen wir konservativ nur den sichtbaren Teil am Tag: [startMin, 1440)
                return [startMin, 1440];
            }
            // Kein End → konservativ ab Start bis Tagesende
            return [startMin, 1440];
        }

        default:
            return null;
    }
};

// Prüft ob irgendein nicht-ganztägiges Event auf diesem Tag in eine versteckte Stunde fällt
const hasAnyOccupancyInHidden = (day, calendarData, hiddenHourSet) => {
    for (const room of calendarData) {
        const events = room.content[day.fullDay]?.events || [];
        for (const ev of events) {
            const interval = eventIntervalOnThisDay(ev, day);
            if (!interval) continue;
            const [from, to] = interval;
            for (const h of hiddenHourSet) {
                const hStart = h * 60;
                const hEnd = (h + 1) * 60;
                if (from < hEnd && hStart < to) return true;
            }
        }
    }
    return false;
};

// Sollen Hidden Hours an diesem Tag "aufgeklappt" werden?
const shouldUnhideHiddenHoursForDay = (day, calendarData, calendarHours) => {
    const hiddenSet = getHiddenHourNumbers(calendarHours);
    if (hiddenSet.size === 0) return false;
    return hasAnyOccupancyInHidden(day, calendarData, hiddenSet);
};




const shouldShowHour = (hour, calendarData, day) => {
    const calendarHours = usePage().props.calendarHours;

    // Keine Hidden-Konfig -> alles zeigen
    if (!Array.isArray(calendarHours) || calendarHours.length <= 1) return true;

    const hiddenSet = getHiddenHourNumbers(calendarHours);
    if (hiddenSet.size === 0) return true;

    const unhide = shouldUnhideHiddenHoursForDay(day, calendarData, calendarHours);

    // hour -> Stundennummer (0-23)
    const toHourNumber = (h) => {
        if (typeof h === 'number') return h;
        const s = String(h);
        const hh = s.includes(':') ? s.split(':')[0] : s;
        return parseInt(hh, 10) || 0;
    };
    const hourNum = toHourNumber(hour);

    if (unhide) {
        // Tag aus der "hidden"-Merkliste entfernen, alles zeigen
        const idx = daysWithoutEventsToDisplayHiddenHours.value.indexOf(day.fullDay);
        if (idx !== -1) daysWithoutEventsToDisplayHiddenHours.value.splice(idx, 1);
        return true;
    }

    // Hidden anwenden
    if (!daysWithoutEventsToDisplayHiddenHours.value.includes(day.fullDay)) {
        daysWithoutEventsToDisplayHiddenHours.value.push(day.fullDay);
    }
    return !hiddenSet.has(hourNum);
};



const getAllProjectGroupsInAllRoomsAndEventsByDay = (day) => {
    let projectGroups = new Map();

    for (const room of props.calendarData) {
        for (const event of room.content[day.fullDay]?.events || []) {
            if (event?.project?.isGroup) {
                if (!projectGroups.has(event.project.id)) {
                    projectGroups.set(event.project.id, event.project);
                }
            } else if (event?.project?.isInGroup && Array.isArray(event.project.group)) {
                // Falls das Projekt in einer Gruppe ist, die Gruppen speichern
                event.project.group.forEach(group => {
                    if (!projectGroups.has(group.id)) {
                        projectGroups.set(group.id, group);
                    }
                });
            }
        }
    }

    return Array.from(projectGroups.values());
};

const checkIfUserIsAdminOrInGroup = (group) => {
    if (hasAdminRole()) {
        return false;
    }

    return !group.userIds.includes(usePage().props.auth.user.id);
}



const hasOverlappingEvents = (events, day, hour) => {
    if (!events || events.length < 2) return false;

    const overlappingEvents = events.filter(event =>
        shouldRenderEvent(event, day, hour)
    );

    return overlappingEvents.length > 1;
};

// Find the first visible hour for a given day (respects hidden hours)
const getFirstVisibleHour = (day) => {
    const calendarHours = usePage().props.calendarHours;
    if (!Array.isArray(calendarHours) || calendarHours.length <= 1) return 0;

    // If hidden hours are unhidden for this day, first visible is 0
    if (!daysWithoutEventsToDisplayHiddenHours.value.includes(day.fullDay)) return 0;

    const hiddenSet = getHiddenHourNumbers(calendarHours);
    for (let h = 0; h < 24; h++) {
        if (!hiddenSet.has(h)) return h;
    }
    return 0;
};

// Count visible hours for a given day
const getVisibleHourCount = (day) => {
    const calendarHours = usePage().props.calendarHours;
    if (!Array.isArray(calendarHours) || calendarHours.length <= 1) return 24;

    // If hidden hours are unhidden for this day, all 24 visible
    if (!daysWithoutEventsToDisplayHiddenHours.value.includes(day.fullDay)) return 24;

    const hiddenSet = getHiddenHourNumbers(calendarHours);
    return Math.max(1, 24 - hiddenSet.size);
};

const shouldRenderEvent = (event, day, hour) => {
    const isStartDay = day.fullDay === event.daysOfEvent[0];
    const isEndDay = day.fullDay === event.daysOfEvent[event.daysOfEvent.length - 1];
    const isMiddleDay =
        day.fullDay !== event.daysOfEvent[0] &&
        day.fullDay !== event.daysOfEvent[event.daysOfEvent.length - 1];

    // Normalize hour values to numeric hours (0-23) for robust comparison
    const toHourNumber = (h) => {
        if (typeof h === 'number') return h;
        const s = String(h);
        const hh = s.includes(':') ? s.split(':')[0] : s;
        return parseInt(hh, 10) || 0;
    };

    const hourNum = toHourNumber(hour);
    const eventStartHourNum = parseInt(event.startHour, 10) || 0;

    if (event.allDay) {
        // Render at the first visible hour (respects hidden hours)
        return hourNum === getFirstVisibleHour(day);
    }
    if (isStartDay) {
        return hourNum === eventStartHourNum;
    }
    if (isMiddleDay) {
        return hourNum === 0;
    }
    if (isEndDay) {
        return hourNum === 0;
    }

    return false;
};

// Helfer: Uhrzeit (HH oder HH:MM) zu Minuten
const toClockMinutes = (h, m = null) => {
    if (h == null) return 0;
    if (typeof h === 'number' && m === null) return h * 60;
    if (typeof h === 'number' && m !== null) return h * 60 + (parseInt(m, 10) || 0);

    const s = String(h);
    if (s.includes(':')) {
        const [hh, mm='0'] = s.split(':');
        return (parseInt(hh, 10) || 0) * 60 + (parseInt(mm, 10) || 0);
    }
    return (parseInt(s, 10) || 0) * 60;
};

// Helfer: extrahiert lokale Uhrzeit-Minuten aus ISO/Datum-String
const minutesFromDateString = (dateStr) => {
    if (!dateStr) return 0;
    const d = new Date(String(dateStr));
    if (!isNaN(d.getTime())) return d.getHours() * 60 + d.getMinutes();

    // Fallback auf "THH:MM" / " HH:MM"
    const timePart = String(dateStr).split(' ')[1] || String(dateStr).split('T')[1];
    if (timePart && timePart.includes(':')) {
        const [hh, mm='0'] = timePart.split(':');
        return (parseInt(hh, 10) || 0) * 60 + (parseInt(mm, 10) || 0);
    }
    return 0;
};

const getEventStyle = (event, day, hour, zoom_factor) => {
    const perHourPx = zoom_factor * 115;
    const perMinutePx = perHourPx / 60;
    const BORDER_PX = 1; // matches border-b on each hour row div

    // Parse start/end clock minutes from formattedDates (most reliable)
    const startTimeStr = event?.formattedDates?.startTime; // "HH:mm"
    const endTimeStr = event?.formattedDates?.endTime;     // "HH:mm"

    const startClockMin = startTimeStr ? parseHHMM(startTimeStr)
        : (toClockMinutes(event?.startHour) + (parseInt(event?.minutesFormStartHourToStart, 10) || 0));
    const endClockMin = endTimeStr ? parseHHMM(endTimeStr)
        : minutesFromDateString(event?.end);

    const pos = dayPosOf(event, day.fullDay);
    const isMultiDay = (event?.daysOfEvent?.length ?? 1) > 1;

    // All-Day: span visible hours including inter-hour borders
    if (event.allDay) {
        const visibleHours = getVisibleHourCount(day);
        const heightPx = visibleHours * (perHourPx + BORDER_PX);
        return { height: `${heightPx}px`, minHeight: `${heightPx}px`, marginTop: '0px', opacity: 1 };
    }

    let heightMinutes = 0;
    let marginTopPx = 0;
    let opacity = isMultiDay ? 0.6 : 1;
    let dayStartMin = 0;
    let dayEndMin = 0;

    if (isMultiDay) {
        switch (pos) {
            case 'start':
                heightMinutes = Math.max(0, 1440 - startClockMin);
                marginTopPx = -(parseInt(event?.minutesFormStartHourToStart, 10) || 0) * perMinutePx;
                dayStartMin = startClockMin;
                dayEndMin = 1440;
                break;
            case 'middle':
                heightMinutes = 1440;
                dayStartMin = 0;
                dayEndMin = 1440;
                break;
            case 'end':
                heightMinutes = Math.max(0, endClockMin);
                dayStartMin = 0;
                dayEndMin = endClockMin;
                break;
            default:
                heightMinutes = 0;
        }
    } else {
        // Single-day: compute from clock times
        if (endClockMin >= startClockMin) {
            heightMinutes = endClockMin - startClockMin;
        } else {
            // Midnight wrap (shouldn't happen for single-day, but be safe)
            heightMinutes = 1440 - (startClockMin - endClockMin);
        }

        // Fallback to eventLengthInHours if clock times yielded 0
        if (heightMinutes <= 0) {
            const lenHours = parseFloat(event?.eventLengthInHours);
            if (!isNaN(lenHours) && lenHours > 0) {
                heightMinutes = Math.round(lenHours * 60);
            }
        }

        marginTopPx = -(parseInt(event?.minutesFormStartHourToStart, 10) || 0) * perMinutePx;
        dayStartMin = startClockMin;
        dayEndMin = endClockMin >= startClockMin ? endClockMin : startClockMin + heightMinutes;
    }

    // Add border pixels: each hour-row border (1px) the event crosses
    const bordersCrossed = Math.max(0, Math.floor(dayEndMin / 60) - Math.floor(dayStartMin / 60));
    const borderPx = bordersCrossed * BORDER_PX;

    const maxHeightPx = 24 * (perHourPx + BORDER_PX);
    const heightPx = Math.max(MIN_EVENT_HEIGHT_PX, Math.min(heightMinutes * perMinutePx + borderPx, maxHeightPx));

    return {
        height: `${heightPx}px`,
        minHeight: `${heightPx}px`,
        marginTop: `${marginTopPx}px`,
        opacity,
    };
};

const showEditEventModel = (event) => {
    emits('editEvent', event)
};

const openAddSubEventModal = (desiredEvent, mode, mainEvent) => {
    emits('openAddSubEventModal', desiredEvent, mode, mainEvent)
};

const openDeleteEventModal = (event, type) => {
    emits('openConfirmModal', event, type)
};

const openDeclineEventModal = (event) => {
    emits('showDeclineEventModal', event)
};

const handleMultiEditEventCheckboxChange = (eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd) => {
    emits('changedMultiEditCheckbox', eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd)
};

const onEventClick = (evt, e) => {
    if (!props.multiEdit) return;
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const nextState = !(evt?.considerOnMultiEdit === true);
    handleMultiEditEventCheckboxChange(evt.id, nextState, (evt?.room_id ?? evt?.roomId ?? null), evt?.start, evt?.end);
};
</script>
