<template>

    <div class="w-max" :class="[eventsWithoutRoom.length > 0 ? 'mt-[6rem]' : 'mt-[4.2rem]']">
        <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg">
            <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
            <div v-for="room in $page.props.rooms" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                <SingleRoomInHeader :room="room" is-light />
            </div>
        </div>
        <div v-for="day in days">
            <div class="flex items-center sticky gap-0.5 h-16 bg-gray-200 z-20 first-line:divide-none top-[128px]">
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
                    <div class="flex items-center h-full gap-x-2" v-if="usePage().props.user.calendar_settings.display_project_groups" v-for="group in getAllProjectGroupsInAllRoomsAndEventsByDay(day)" :key="group.id">
                        <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectTabId })" class=" text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1 border" :style="{ backgroundColor: group.color + '22' ?? '#ccc', color: group.color, borderColor: group.color }">
                            <component :is="group.icon" class="size-4" aria-hidden="true"/>
                            <span>{{ group.name }}</span>
                        </Link>
                    </div>
                </div>
            </div>
            <div v-for="hour in day.hoursOfDay">
                <div v-if="shouldShowHour(hour, calendarData, day)" class="border-b border-dashed">
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


const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
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

const shouldShowHour = (hour, calendarData, day) => {
    const calendarHours = usePage().props.calendarHours;

    // Prüft, ob an diesem Tag überhaupt Events vorhanden sind
    const hasEventsToday = calendarData.some(room =>
        (room.content[day.fullDay]?.events || []).length > 0
    );

    // Wenn keine Events vorhanden sind, blende NUR die calendarHours aus
    if (!hasEventsToday) {
        // Tag zu daysWithoutEventsToDisplayHiddenHours hinzufügen, wenn noch nicht vorhanden
        if (!daysWithoutEventsToDisplayHiddenHours.value.includes(day.fullDay)) {
            daysWithoutEventsToDisplayHiddenHours.value.push(day.fullDay);
        }

        return !calendarHours.includes(hour);
    }

    // Wenn Events vorhanden sind, entferne den Tag aus dem Array (falls vorhanden)
    const index = daysWithoutEventsToDisplayHiddenHours.value.indexOf(day.fullDay);
    if (index !== -1) {
        daysWithoutEventsToDisplayHiddenHours.value.splice(index, 1);
    }

    // Wenn Events vorhanden sind, zeige alle Stunden (auch aus calendarHours)
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

    return !group.userIds.includes(usePage().props.user.id);
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

    if(event.startHour === parseInt(hour, 10) && event.formattedDates.start === day.fullDay) {
        return true;
    }

    if (event.allDay) {
        return hour === '00:00';
    }
    if (isStartDay) {
        return hour === event.startHour;
    }
    if (isMiddleDay) {
        return hour === '00:00';
    }
    if (isEndDay) {
        return hour === '00:00';
    }

    return false;
};

const getEventStyle = (event, day, hour, zoom_factor) => {
    const isStartDay = day.fullDay === event.daysOfEvent[0];
    const isEndDay = day.fullDay === event.daysOfEvent[event.daysOfEvent.length - 1];
    const isMiddleDay =
        day.fullDay !== event.daysOfEvent[0] &&
        day.fullDay !== event.daysOfEvent[event.daysOfEvent.length - 1];

    let height = '';
    let marginTop = '0px';
    let opacity = 1;
    if (event.allDay) {
        height = (24 * (zoom_factor * 115)) + 25 + 'px';
    } else if (isStartDay) {
        const startHour = parseInt(event.startHour, 10);
        const startMinute = parseInt(event.startHour, 10) || 0;
        const totalHours = event.eventLengthInHours > 0
            ? event.eventLengthInHours
            : Math.min(
                24 - startHour - startMinute / 60,
                (event.end ? 24 : 0)
            );
        const minusHeight = event.daysOfEvent.length > 1 ? event.minutesFormStartHourToStart * (zoom_factor * 1.91) - 40 : 0;
        height = ((totalHours * (zoom_factor * 115)) - minusHeight) + 'px';
        marginTop = event.minutesFormStartHourToStart * (zoom_factor * 1.91) + 'px';

    } else if (isMiddleDay) {
        height = (24 * (zoom_factor * 115)) + 25 + 'px';
    } else if (isEndDay) {
        const eventEnd = event?.end ? String(event.end) : null;
        if (eventEnd) {
            const timePart = eventEnd.split(' ')[1] || eventEnd.split('T')[1];
            if (timePart && timePart.includes(':')) {
                const endTime = timePart.split(':');
                const endHour = parseInt(endTime[0], 10) || 0;
                const endMinute = parseInt(endTime[1], 10) || 0;
                const endTimeInHours = endHour + endMinute / 60;
                height = (endTimeInHours * zoom_factor * 115) + 'px';
            } else {
                height = (event.eventLengthInHours * zoom_factor * 115) + 'px';
            }
        } else {
            height = (event.eventLengthInHours * zoom_factor * 115) + 'px';
        }
    } else {
        height = event.eventLengthInHours * (zoom_factor * 115) + 'px';
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