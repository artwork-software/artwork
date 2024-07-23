<template>
    <app-layout :title="$t('Calendar')">
        <div>
            <div v-if="calendarType && calendarType === 'daily'">
                <div class="mr-4">
                    <CalendarComponent
                        :selected-date="selectedDate"
                        :dateValue="dateValue"
                        :eventTypes=eventTypes
                        initial-view="day"
                        :rooms="rooms"
                        :events="events"
                        :events-without-room="eventsWithoutRoom"
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :user_filters="user_filters"
                        :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    />
                </div>
            </div>
            <div v-else>
                <IndividualCalendarAtGlanceComponent
                    v-if="atAGlance"
                    :dateValue="dateValue"
                    :project="null"
                    :atAGlance="atAGlance"
                    :eventTypes=eventTypes
                    :rooms="rooms"
                    :eventsAtAGlance="eventsAtAGlance"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    :first_project_tab_id="first_project_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                >
                </IndividualCalendarAtGlanceComponent>
                <BaseCalendar
                    v-else
                    :rooms="rooms"
                    :days="days"
                    :calendar-data="calendar"
                    :events-without-room="eventsWithoutRoom"
                />
            </div>
        </div>
    </app-layout>


</template>
<script setup>

import {provide, ref} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {usePage} from "@inertiajs/vue3";
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";


const props = defineProps({
    eventTypes: Object,
    calendarType: String,
    selectedDate: String,
    dateValue: Array,
    calendar: Object,
    rooms: Object,
    events: Object,
    days: Array,
    eventsAtAGlance: Array,
    eventsWithoutRoom: Array,
    filterOptions: Object,
    personalFilters: Object,
    user_filters: Object,
    first_project_tab_id: Number,
    first_project_calendar_tab_id: Number
})

provide('eventTypes', props.eventTypes);
provide('dateValue', props.dateValue);
provide('first_project_tab_id', props.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id);
provide('user_filters', props.user_filters);
provide('personalFilters', props.personalFilters);
provide('filterOptions', props.filterOptions);

const atAGlance = ref(usePage().props.user.at_a_glance ?? false);

</script>
