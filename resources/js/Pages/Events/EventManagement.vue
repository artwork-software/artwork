<template>
    <app-layout :title="$t('Calendar')">
        <div>
            <div v-if="calendarType && calendarType === 'daily'">
                <div class="mr-4">
                    <CalendarComponent
                        :selected-date="selectedDate"
                        :dateValue="dateValue"
                        :eventTypes=this.eventTypes
                        initial-view="day"
                        :rooms="rooms"
                        :events="this.events.events"
                        :events-without-room="eventsWithoutRoom"
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :user_filters="user_filters"
                        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                    />
                </div>
            </div>
            <div v-else>
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
            </div>
        </div>
    </app-layout>


</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {usePage, router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";


export default defineComponent({
    mixins: [Permissions],
    components: {
        IndividualCalendarAtGlanceComponent,
        IndividualCalendarComponent,
        CalendarComponent,
        AppLayout
    },
    props: [
        'eventTypes',
        'calendarType',
        'selectedDate',
        'dateValue',
        'calendar',
        'rooms',
        'events',
        'days',
        'eventsAtAGlance',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'events',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    methods: {
        usePage,
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
            router.reload({
                data: {
                    atAGlance: this.atAGlance,
                }
            })
        }
    },
    data() {
        return {
            atAGlance: this.$page.props.urlParameters.atAGlance ? this.$page.props.urlParameters.atAGlance : false,
        }
    },
})
</script>
