<template>
    <app-layout :title="$t('Calendar')">
        <div class="w-full ml-11 mt-1">
            <BaseCalendar v-if="!atAGlance"
                          :rooms="rooms"
                          :days="days"
                          :calendar-data="calendar"
                          :events-without-room="eventsWithoutRoom"
                          :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                          :first-project-shift-tab-id="first_project_shift_tab_id"
                          :event-statuses="eventStatuses"
            />
            <IndividualCalendarAtGlanceComponent v-else
                                                 :dateValue="dateValue"
                                                 :project="null"
                                                 :atAGlance="atAGlance"
                                                 :eventTypes="eventTypes"
                                                 :rooms="rooms"
                                                 :eventsAtAGlance="eventsAtAGlance"
                                                 :filter-options="filterOptions"
                                                 :personal-filters="personalFilters"
                                                 :user_filters="user_filters"
                                                 :first_project_tab_id="first_project_tab_id"
                                                 :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                 :first-project-shift-tab-id="first_project_shift_tab_id"
                                                 :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                                                 :event-statuses="eventStatuses"
                                                 :isCalendarViewRoute="true"/>
        </div>
    </app-layout>
</template>
<script setup>
import {provide, ref} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {usePage} from "@inertiajs/vue3";
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";

const props = defineProps(
    {
        eventTypes: Object,
        calendarType: String,
        selectedDate: String,
        dateValue: Array,
        calendar: Object,
        rooms: Object,
        events: Object,
        days: Array,
        eventsAtAGlance: Object,
        eventsWithoutRoom: Array,
        filterOptions: Object,
        personalFilters: Object,
        user_filters: Object,
        first_project_tab_id: Number,
        first_project_calendar_tab_id: Number,
        areas: Object,
        projectNameUsedForProjectTimePeriod: String,
        first_project_shift_tab_id: Number,
        eventStatuses: Object,
        months: Object,
        event_properties: {
            type: Array,
            required: true
        }
    }),
    atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);

provide('eventTypes', props.eventTypes);
provide('dateValue', props.dateValue);
provide('first_project_tab_id', props.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id);
provide('user_filters', props.user_filters);
provide('personalFilters', props.personalFilters);
provide('filterOptions', props.filterOptions);
provide('rooms', props.rooms);
provide('areas', props.areas);
provide('eventStatuses', props.eventStatuses);
provide('months', props.months);
provide('event_properties', props.event_properties);
</script>
