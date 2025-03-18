<template>
    <AppLayout>

        <transition name="fade" appear>
            <div class="pointer-events-none fixed z-50 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="showCalendarWarning.length > 0">
                <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                    <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                    <p class="text-sm/6 text-white">
                        {{ showCalendarWarning }}
                    </p>
                    <button type="button" class="-m-1.5 flex-none p-1.5">
                        <span class="sr-only">Dismiss</span>
                        <component is="IconX" class="size-5 text-white" aria-hidden="true" @click="showCalendarWarning = ''" />
                    </button>
                </div>
            </div>
        </transition>

        <div class="w-full ml-11 mt-1">
            <BaseCalendar :rooms="rooms"
                          :days="period"
                          :calendar-data="calendar"
                          :events-without-room="[]"
                          :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                          :first-project-shift-tab-id="1"
                          :event-statuses="eventStatuses"
                          :eventsWithoutRoom="eventsWithoutRoom"
            />
        </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {usePage} from "@inertiajs/vue3";
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";
import {computed, onMounted, provide, ref} from "vue";
import { IconAlertSquareRounded } from "@tabler/icons-vue";

const props = defineProps({
    period: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    months: {
        type: Object,
        required: true
    },
    calendar: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    },
    dateValue: {
        type: Array,
        required: true
    },
    personalFilters: {
        type: Object,
        required: true
    },
    filterOptions: {
        type: Object,
        required: true
    },
    user_filters: {
        type: Object,
        required: true
    },
    eventTypes: {
        type: Object,
        required: true
    },
    event_properties: {
        type: Object,
        required: true
    },
    first_project_tab_id: {
        type: Number,
        required: true
    },
    first_project_calendar_tab_id: {
        type: Number,
        required: true
    },
    eventsWithoutRoom: {
        type: Object,
        required: false,
        default: []
    },
    projectNameUsedForProjectTimePeriod: {
        type: String,
        required: false,
        default: null
    },
    areas: {
        type: Object,
        required: true
    },
    calendarWarningText: {
        type: String,
        required: false,
        default: ''
    }
})

provide('eventTypes', props.eventTypes);
provide('dateValue', props.dateValue);
provide('first_project_tab_id', props.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id);
provide('user_filters', props.user_filters);
provide('personalFilters', props.personalFilters);
provide('filterOptions', props.filterOptions);
provide('rooms', props.rooms);
provide('eventStatuses', props.eventStatuses);
provide('months', props.months);
provide('event_properties', props.event_properties);
provide('areas', props.areas);

const showCalendarWarning = ref(props.calendarWarningText)

onMounted(() => {
    setTimeout(() => {
        showCalendarWarning.value = ''
    }, 5000)
})
</script>

<style scoped>

</style>