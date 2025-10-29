<template>
    <div class="w-max -ml-3">
        <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[71px] rounded-lg">
            <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
            <div v-for="room in $page.props.rooms" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                <SingleRoomInHeader :room="room" is-light />
            </div>
        </div>
        <div v-for="day in days">
            <div class="flex items-center sticky gap-0.5 h-16 bg-gray-100 z-20 first-line:divide-none top-34 rounded-r-lg">
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
                    <div class="flex items-center h-full gap-x-2" v-if="usePage().props.auth.user.calendar_settings.display_project_groups" v-for="group in getAllProjectGroupsInAllRoomsAndEventsByDay(day)" :key="group.id">
                        <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectTabId })" class=" text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1 border" :style="{ backgroundColor: group.color + '22' ?? '#ccc', color: group.color, borderColor: group.color }">
                            <component :is="group.icon" class="size-4" aria-hidden="true"/>
                            <span>{{ group.name }}</span>
                        </Link>
                    </div>
                </div>
            </div>
            <div v-for="hour in day.hoursOfDay">
                <div v-if="shouldShowHour(hour, calendarData, day)" class="border-b border-gray-300 border-dashed">
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
                                    }"
                                     :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                                     class="group/container"
                                     :id="'scroll_container-' + day.withoutFormat">
                                    <!-- Container für die Events -->
                                    <div :class="{'relative grid': hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour)}" :style="hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour) ? { gridTemplateColumns: 'repeat(auto-fit, minmax(' + zoom_factor * 100 + 'px, 1fr))' } : {}">
                                        <div v-for="(event, index) in (room.content[day.fullDay]?.events || [])" :key="event.id">
                                            <div v-if="event && shouldRenderEvent(event, day, hour)"
                                                 class="py-0.5 rounded-lg relative"
                                                 :id="'event_scroll-' + index + '-day-' + day.withoutFormat"
                                                 :style="getEventStyle(event, day, hour, zoom_factor)">
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
            </div>
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

const extractEndMinutes = (event, pos, startMin) => {
    // For single-day segments prefer using duration to avoid timezone drift
    const durationMinutes = Math.max(0, Math.round((parseFloat(event?.eventLengthInHours) || 0) * 60));

    if (pos === 'single') {
        if (durationMinutes > 0 && Number.isFinite(startMin)) {
            return Math.min(24 * 60, startMin + durationMinutes);
        }
        // Fallback to parsing event.end in local timezone
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
        return null;
    }

    if (pos === 'end') {
        // End day of a multi-day event: use the actual end clock time in local TZ
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
        return null;
    }

    return null;
};

const hasEventOverlapWithHiddenHours = (day, calendarData, calendarHours) => {
    if (!Array.isArray(calendarHours) || calendarHours.length === 0) return false;

    // Hidden window defined by the first and last hidden hour, end is exclusive
    const hiddenStart = timeToMinutes(calendarHours[0]);
    const hiddenLast = calendarHours[calendarHours.length - 1];
    const hiddenEndExclusive = timeToMinutes(hiddenLast) + 60;

    const eventStartMinutes = (event) => {
        const h = parseInt(event?.startHour, 10) || 0;
        const m = parseInt(event?.minutesFormStartHourToStart, 10) || 0;
        return h * 60 + m;
    };

    const dayPos = (event, fullDay) => {
        const days = Array.isArray(event?.daysOfEvent) ? event.daysOfEvent : [];
        const idx = days.indexOf(fullDay);
        if (idx === -1) return 'none';
        if (days.length === 1) return 'single';
        if (idx === 0) return 'start';
        if (idx === days.length - 1) return 'end';
        return 'middle';
    };

    for (const room of calendarData) {
        const events = room.content[day.fullDay]?.events || [];
        for (const event of events) {
            const pos = dayPos(event, day.fullDay);
            if (pos !== 'single' && pos !== 'start') continue; // Only consider starts on this day

            // All-day events only affect the start day at 00:00
            let startMin = 0;
            if (event?.allDay) {
                startMin = 0;
            } else {
                startMin = eventStartMinutes(event);
            }

            // Unhide only if the start time is inside [hiddenStart, hiddenEndExclusive)
            if (startMin >= hiddenStart && startMin < hiddenEndExclusive) {
                return true;
            }
        }
    }
    return false;
};

const shouldShowHour = (hour, calendarData, day) => {
    const calendarHours = usePage().props.calendarHours;

    if (!Array.isArray(calendarHours) || calendarHours.length === 0) {
        return true;
    }

    const hasEventsToday = calendarData.some(room =>
        (room.content[day.fullDay]?.events || []).length > 0
    );

    const overlapsHidden = hasEventsToday && hasEventOverlapWithHiddenHours(day, calendarData, calendarHours);

    // Build normalized set for hidden hours (strings like 'HH:00')
    const hiddenSet = new Set((calendarHours || []).map(h => {
        const [hh] = String(h).split(':');
        return `${String(hh).padStart(2, '0')}:00`;
    }));
    const hourKey = (() => {
        if (typeof hour === 'string') {
            const [hh] = hour.split(':');
            return `${String(hh).padStart(2, '0')}:00`;
        }
        if (typeof hour === 'number') {
            return `${String(hour).padStart(2, '0')}:00`;
        }
        return String(hour);
    })();

    if (!hasEventsToday || !overlapsHidden) {
        if (!daysWithoutEventsToDisplayHiddenHours.value.includes(day.fullDay)) {
            daysWithoutEventsToDisplayHiddenHours.value.push(day.fullDay);
        }
        return !hiddenSet.has(hourKey);
    }

    const index = daysWithoutEventsToDisplayHiddenHours.value.indexOf(day.fullDay);
    if (index !== -1) {
        daysWithoutEventsToDisplayHiddenHours.value.splice(index, 1);
    }
    return true;
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
        return hourNum === 0;
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

const getEventStyle = (event, day, hour, zoom_factor) => {
    const isStartDay = day.fullDay === event.daysOfEvent[0];
    const isEndDay = day.fullDay === event.daysOfEvent[event.daysOfEvent.length - 1];
    const isMiddleDay =
        day.fullDay !== event.daysOfEvent[0] &&
        day.fullDay !== event.daysOfEvent[event.daysOfEvent.length - 1];

    const perHourPx = zoom_factor * 115; // exact pixels per hour (matches cell height)
    const minutesFromHour = parseInt(event?.minutesFormStartHourToStart, 10) || 0;
    const minuteOffsetPx = -(minutesFromHour / 60) * perHourPx; // positive moves event down within its hour

    let height = '';
    let marginTop = '0px';
    let opacity = 1;

    if (event.allDay) {
        height = (24 * perHourPx) + 25 + 'px';
    } else if (isStartDay) {
        const startHour = parseInt(event.startHour, 10) || 0;

        // Determine displayed height in hours for this visible segment
        let totalHours;
        const lenHours = parseFloat(event?.eventLengthInHours);
        if (!isNaN(lenHours) && lenHours > 0 && event.daysOfEvent.length === 1) {
            // Single-day event: use computed duration
            totalHours = lenHours;
        } else if (event.end && event.daysOfEvent.length === 1) {
            // Single-day but length missing: compute from end time
            const eventEnd = event.end ? String(event.end) : null;
            if (eventEnd) {
                const timePart = eventEnd.split(' ')[1] || eventEnd.split('T')[1];
                if (timePart && timePart.includes(':')) {
                    const [eh, em] = timePart.split(':');
                    const endHour = parseInt(eh, 10) || 0;
                    const endMinute = parseInt(em, 10) || 0;
                    const endTimeInHours = endHour + endMinute / 60;
                    const startTimeInHours = startHour + minutesFromHour / 60;
                    totalHours = Math.max(0.5, endTimeInHours - startTimeInHours);
                } else {
                    totalHours = 1;
                }
            } else {
                totalHours = 1;
            }
        } else {
            // Multi-day start or missing end: span until end of day
            totalHours = Math.min(24 - (startHour + minutesFromHour / 60), 24);
        }

        height = (totalHours * perHourPx) + 'px';
        marginTop = minuteOffsetPx + 'px';
    } else if (isMiddleDay) {
        height = (24 * perHourPx) + 25 + 'px';
    } else if (isEndDay) {
        const eventEnd = event?.end ? String(event.end) : null;
        if (eventEnd) {
            const timePart = eventEnd.split(' ')[1] || eventEnd.split('T')[1];
            if (timePart && timePart.includes(':')) {
                const [eh, em] = timePart.split(':');
                const endHour = parseInt(eh, 10) || 0;
                const endMinute = parseInt(em, 10) || 0;
                const endTimeInHours = endHour + endMinute / 60;
                height = (endTimeInHours * perHourPx) + 'px';
            } else {
                height = ((parseFloat(event?.eventLengthInHours) || 1) * perHourPx) + 'px';
            }
        } else {
            height = ((parseFloat(event?.eventLengthInHours) || 1) * perHourPx) + 'px';
        }
    } else {
        height = ((parseFloat(event?.eventLengthInHours) || 1) * perHourPx) + 'px';
    }

    opacity = event.daysOfEvent.length > 1 ? 0.6 : 1;

    return {
        height,
        minHeight: height,
        marginTop,
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
</script>
