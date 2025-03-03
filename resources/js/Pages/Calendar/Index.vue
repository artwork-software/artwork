<template>
    <AppLayout>
        <div class="w-full ml-11 mt-1">
            <BaseCalendar :rooms="rooms"
                          :days="period"
                          :calendar-data="usePage().props.calendar"
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
import {provide} from "vue";

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
</script>

<style scoped>

</style>