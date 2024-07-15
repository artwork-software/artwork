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
                        :events="events.events"
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
                    @change-at-a-glance="changeAtAGlance"
                    :first_project_tab_id="first_project_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                >
                </IndividualCalendarAtGlanceComponent>

                <BaseCalendar
                    v-else
                    :rooms="rooms"
                    :days="days"
                    :calendar-data="calendar"
                />

                <!--
                <IndividualCalendarAtGlanceComponent
                    v-if="atAGlance"
                    :dateValue="dateValue"
                    :project="null"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :rooms="rooms"
                    :eventsAtAGlance="eventsAtAGlance"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    @change-at-a-glance="changeAtAGlance"
                    :first_project_tab_id="this.first_project_tab_id"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                >
                </IndividualCalendarAtGlanceComponent>

                <IndividualCalendarComponent
                    v-else
                    :events-without-room="eventsWithoutRoom"
                    :dateValue="dateValue"
                    :project="null"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :calendarData="calendar"
                    :rooms="rooms"
                    :days="days"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    @change-at-a-glance="changeAtAGlance"
                    :first_project_tab_id="this.first_project_tab_id"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                />

                -->
            </div>
        </div>
    </app-layout>


</template>
<script setup>

import {defineComponent, ref, provide} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {usePage, router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
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

provide('dateValue', props.dateValue);


const atAGlance = ref(usePage().props.urlParameters.atAGlance ? usePage().props.urlParameters.atAGlance : false);

const changeAtAGlance = () => {
    atAGlance.value = !atAGlance.value;
    router.reload({
        data: {
            atAGlance: atAGlance.value,
        }
    })
}
</script>
