<template>
    <app-layout :title="$t('Calendar')">
        <div class="w-full ml-11 mt-1">
            <div v-if="!calendarType || calendarType !== 'daily'">
                <BaseCalendar v-if="!atAGlance"
                              :rooms="rooms"
                              :days="days"
                              :calendar-data="calendar"
                              :events-without-room="eventsWithoutRoom"
                              :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"/>
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
                                                     :first_project_calendar_tab_id="first_project_calendar_tab_id"/>
            </div>
            <div v-else>
                <div class="mr-4">
                    <CalendarComponent initial-view="day"
                                       :selected-date="selectedDate"
                                       :dateValue="dateValue"
                                       :eventTypes=eventTypes
                                       :rooms="rooms"
                                       :events="events"
                                       :events-without-room="eventsWithoutRoom"
                                       :filter-options="filterOptions"
                                       :personal-filters="personalFilters"
                                       :user_filters="user_filters"
                                       :first_project_calendar_tab_id="first_project_calendar_tab_id"/>
                </div>
            </div>

        </div>
    </app-layout>
</template>
<script setup>
import {provide, ref} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
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
        projectNameUsedForProjectTimePeriod: String
    }),
    atAGlance = ref(usePage().props.user.at_a_glance ?? false);

provide('eventTypes', props.eventTypes);
provide('dateValue', props.dateValue);
provide('first_project_tab_id', props.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id);
provide('user_filters', props.user_filters);
provide('personalFilters', props.personalFilters);
provide('filterOptions', props.filterOptions);
provide('rooms', props.rooms);
provide('areas', props.areas);
</script>
