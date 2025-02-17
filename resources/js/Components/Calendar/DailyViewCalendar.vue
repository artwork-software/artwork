<template>
    <div class="w-max" :class="eventsWithoutRoom.length > 0 ? 'mt-[4.5rem]' : ''">
        <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg mb-3">
            <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
            <div v-for="room in $page.props.rooms" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                <SingleRoomInHeader :room="room" is-light />
            </div>
        </div>
        <div v-for="day in days">
            <div class="flex items-center sticky  gap-0.5 h-16 bg-gray-200 z-20 first-line:divide-none top-[128px]">
                <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
                <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px' }" class="flex items-center  h-full truncate">
                    <div class="flex xsDark items-center px-8 mt-1.5">
                        {{ day.dayString}}, {{ day.fullDay }}
                    </div>
                </div>
            </div>
            <div v-for="hour in day.hoursOfDay" class=" border-b border-dashed">
                <div class="w-fit events-by-days-container rounded-lg" ref="calendarToCalculate">
                    <div :key="day.fullDay"
                         :style="{ height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px' }"
                         class="flex gap-0.5 day-container"
                         :data-day="day.fullDay"
                         :data-day-to-jump="day.withoutFormat">
                        <SingleDayInCalendar :hour="hour"/>
                        <div v-for="room in calendarData"
                            :key="room.id"
                            class="">
                            <div :style="{
                                    minWidth: zoom_factor * 212 + 'px',
                                    maxWidth: zoom_factor * 212 + 'px',
                                    height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px',
                                }"
                                :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                                class="group/container"
                                :id="'scroll_container-' + day.withoutFormat">
                                <!-- Container für die Events -->
                                <div
                                    :class="{'relative grid': hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour)}"
                                    :style="hasOverlappingEvents(room.content[day.fullDay]?.events || [], day, hour)
                                    ? { gridTemplateColumns: 'repeat(auto-fit, minmax(' + zoom_factor * 100 + 'px, 1fr))' }
                                    : {}">
                                    <div v-for="(event, index) in (room.content[day.fullDay]?.events || [])">
                                        <div :key="event.id"
                                             v-if="event && shouldRenderEvent(event, day, hour)"
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

</template>

<script setup>
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import SingleEventInCalendar from "@/Components/Calendar/Elements/SingleEventInCalendar.vue";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";
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
        const startHour = parseInt(event.startHour.split(':')[0], 10);
        const startMinute = parseInt(event.startHour.split(':')[1], 10) || 0;
        const totalHours = event.eventLengthInHours > 0
            ? event.eventLengthInHours
            : Math.min(
                24 - startHour - startMinute / 60,
                (event.end ? 24 : 0)
            );
        const minusHeight = event.daysOfEvent.length > 1 ? event.minutesFormStartHourToStart * (zoom_factor * 1.91) - 10 : 0;
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
