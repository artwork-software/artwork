<template>
    <app-layout>
        <div class="max-w-screen-lg mt-12 flex flex-row ml-14 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex justify-between">
                    <div class="flex flex-wrap">
                        <h2 class="headline1">Raumbelegungen</h2>
                    </div>
                </div>
            </div>
        </div>
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
                />
            </div>
        </div>
    </app-layout>


</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import '@vuepic/vue-datepicker/dist/main.css'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {useForm, usePage} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";

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
        'events'
    ],
    methods: {
        usePage,
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
            Inertia.reload({
                data: {
                    atAGlance: this.atAGlance,
                },
                only: ['calendar', 'eventsAtAGlance']
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
