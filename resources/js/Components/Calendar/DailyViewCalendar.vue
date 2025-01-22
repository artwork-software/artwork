<template>
    <div class="mt-[4.5rem]">
        <div class="flex items-center sticky  gap-0.5 h-16 bg-artwork-navigation-background z-30 first-line:divide-none top-[64px] rounded-lg mb-3">
            <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
            <div v-for="room in $page.props.rooms" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px' }" class="flex items-center  h-full truncate">
                <Link  class="flex font-semibold xsLighter items-center px-8" :href="route('rooms.show', { room: room.id })">
                    {{ room.name }}
                </Link>
            </div>
        </div>
        <div v-for="day in days">
            <div class="flex items-center sticky  gap-0.5 h-16 bg-gray-200 z-20 first-line:divide-none top-[128px]">
                <div :style="{minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px'}"></div>
                <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px' }" class="flex items-center  h-full truncate">
                    <div class="flex xsDark items-center px-8 mt-1.5">
                        {{ day.day_string}}, {{ day.full_day}}
                    </div>
                </div>
            </div>
            <div v-for="hour in day.hours_of_day" class=" border-b border-dashed">
                <div class="w-fit events-by-days-container rounded-lg" ref="calendarToCalculate">
                    <div :key="day.full_day"
                         :style="{ height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px' }"
                         class="flex gap-0.5 day-container"
                         :data-day="day.full_day"
                         :data-day-to-jump="day.without_format">
                        <SingleDayInCalendar :hour="hour"/>
                        <div v-for="room in $page.props.calendar"
                             :key="room.id"
                             class="relative">
                            <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px' }"
                                 :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                                 class="group/container " :id="'scroll_container-' + day.without_format">
                                <div v-for="(event, index) in room[day.full_day].events">
                                    <div class="py-0.5 absolute w-full rounded-lg" :key="event.id" :id="'event_scroll-' + index + '-day-' + day.without_format" v-if="event.start_hour === hour" :style="{
                                        height: event.allDay ? '100%' : event.event_length_in_hours * 115 + 'px',
                                        minHeight: event.allDay ? '100%' : event.event_length_in_hours * 115 + 'px',
                                        marginTop: event.minutes_form_start_hour_to_start * 1.91 + 'px',
                                    }">
                                        <SingleEventInCalendar
                                            :event="event"
                                            :project="project"
                                            :event-statuses="eventStatuses"
                                            :rooms="rooms"
                                            :project-name-used-for-project-time-period="projectNameUsedForProjectTimePeriod"
                                            :first-project-shift-tab-id="firstProjectShiftTabId"
                                            :multi-edit="multiEdit"
                                            width=""
                                            :is-height-full="true"
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

</template>

<script setup>

import CalendarHeader from "@/Components/Calendar/Elements/CalendarHeader.vue";
import {defineAsyncComponent, ref} from "vue";
import {Link, usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import SingleEventInCalendar from "@/Components/Calendar/Elements/SingleEventInCalendar.vue";
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
    }
})


const AsyncSingleEventInCalendar = defineAsyncComponent(
    {
        loader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue')
    }
);
</script>

<style scoped>

</style>