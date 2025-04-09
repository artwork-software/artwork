<template>
    <div class="mt-6">
        <div class="mt-6 ">
            <div v-if="calendarType && calendarType === 'daily'">
                <CalendarComponent
                    initial-view="day"
                    :project="project ?? headerObject.project"
                    :selected-date="selectedDate ?? loadedProjectInformation['CalendarTab'].selectedDate"
                    :dateValue="dateValue ?? loadedProjectInformation['CalendarTab'].dateValue"
                    :eventTypes="eventTypes ?? loadedProjectInformation['CalendarTab'].eventTypes"
                    :events="events.events ?? loadedProjectInformation['CalendarTab'].events.events"
                    :rooms="rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                    :events-without-room="eventsWithoutRoom ?? headerObject.eventsWithoutRoom"
                    :filter-options="filterOptions ?? loadedProjectInformation['CalendarTab'].filterOptions"
                    :personal-filters="personalFilters ?? loadedProjectInformation['CalendarTab'].personalFilters"
                    :user_filters="user_filters ?? loadedProjectInformation['CalendarTab'].user_filters"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :event-statuses="eventStatuses"/>
            </div>
            <div v-else class="pl-16">
                <BaseCalendar v-if="!atAGlance"
                              :project="project ?? headerObject.project"
                              :rooms="rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                              :days="days ?? loadedProjectInformation['CalendarTab'].days"
                              :calendar-data="calendar ?? loadedProjectInformation['CalendarTab'].calendar"
                              :event-statuses="eventStatuses"
                              :events-without-room="eventsWithoutRoom ?? loadedProjectInformation['CalendarTab'].eventsWithoutRoom"
                />
                <IndividualCalendarAtGlanceComponent v-else
                                                     :event-statuses="eventStatuses"
                                                     :atAGlance="atAGlance"
                                                     :project="project ?? headerObject.project"
                                                     :rooms="rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                                                     :dateValue="dateValue ?? loadedProjectInformation['CalendarTab'].dateValue"
                                                     :eventTypes="eventTypes ?? loadedProjectInformation['CalendarTab'].eventTypes"
                                                     :eventsAtAGlance="eventsAtAGlance ?? loadedProjectInformation['CalendarTab'].eventsAtAGlance"
                                                     :filter-options="filterOptions ?? loadedProjectInformation['CalendarTab'].filterOptions"
                                                     :personal-filters="personalFilters ?? loadedProjectInformation['CalendarTab'].personalFilters"
                                                     :user_filters="user_filters ?? loadedProjectInformation['CalendarTab'].user_filters"
                                                     :first_project_tab_id="first_project_tab_id ?? loadedProjectInformation['CalendarTab'].first_project_tab_id"
                                                     :first_project_calendar_tab_id="first_project_calendar_tab_id ?? loadedProjectInformation['CalendarTab'].first_project_calendar_tab_id"
                                                     :isCalendarViewRoute="false"/>
            </div>
        </div>
    </div>
</template>

<script setup>
import {usePage} from "@inertiajs/vue3";
import {provide, ref} from "vue";
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";

const props = defineProps([
    'project',
    'calendarType',
    'selectedDate',
    'dateValue',
    'events',
    'rooms',
    'eventsWithoutRoom',
    'filterOptions',
    'personalFilters',
    'atAGlance',
    'eventsAtAGlance',
    'calendar',
    'days',
    'eventTypes',
    'projectWriteIds',
    'projectManagerIds',
    'user_filters',
    'loadedProjectInformation',
    'headerObject',
    'first_project_tab_id',
    'first_project_calendar_tab_id',
    'eventStatuses'
]),
atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);

provide('eventTypes', props.eventTypes ?? props.loadedProjectInformation['CalendarTab'].eventTypes);
provide('dateValue', props.dateValue ?? props.loadedProjectInformation['CalendarTab'].dateValue);
provide('first_project_tab_id', props.first_project_tab_id ?? props.loadedProjectInformation['CalendarTab'].first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id ?? props.loadedProjectInformation['CalendarTab'].first_project_calendar_tab_id);
provide('user_filters', props.user_filters ?? props.loadedProjectInformation['CalendarTab'].user_filters);
provide('personalFilters', props.personalFilters ?? props.loadedProjectInformation['CalendarTab'].personalFilters);
provide('filterOptions', props.filterOptions ?? props.loadedProjectInformation['CalendarTab'].filterOptions);
provide('eventStatuses', props.eventStatuses ?? props.loadedProjectInformation['CalendarTab'].eventStatuses);
provide('event_properties', props.loadedProjectInformation['CalendarTab'].event_properties);
</script>
