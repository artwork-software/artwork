<template>
    <app-layout title="Event Management">
        <MonthlyCalendar calendarType="main"
                         :event_types="event_types"
                         :areas="areas"
                         :month_events="month_events"
                         :projects="projects"
                         :requested_start_time="requested_start_time"
                         :requested_end_time="requested_end_time"
                         :rooms="rooms"
                         :days_this_month="days_this_month"
                         :my-rooms="myRooms"
                         :events_without_room="events_without_room">

        </MonthlyCalendar>

    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import '@vuepic/vue-datepicker/dist/main.css'
import MonthlyCalendar from "@/Layouts/Components/MonthlyCalendar";
import {Inertia} from "@inertiajs/inertia";

export default defineComponent({
    components: {
        MonthlyCalendar,
        AppLayout
    },
    created() {
        Echo.private('events')
            .listen('OccupancyUpdated', () => {
                Inertia.reload({only: ['rooms']})
            });
    },
    props: ['event_types', 'areas', 'month_events', 'projects', 'myRooms', 'rooms', 'days_this_month', 'events_without_room', 'requested_start_time', 'requested_end_time', 'start_time_of_new_event', 'end_time_of_new_event'],
})
</script>
