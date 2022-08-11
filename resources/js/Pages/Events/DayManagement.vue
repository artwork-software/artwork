<template>
    <app-layout title="Event Management">
        <DailyCalendar calendarType="main" :my-rooms="myRooms" :hours_of_day="hours_of_day" :rooms="rooms" :projects="projects" :event_types="event_types" :areas="areas" :shown_day_formatted="shown_day_formatted" :shown_day_local="shown_day_local" :events_without_room="events_without_room"/>
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DailyCalendar from "@/Layouts/Components/DailyCalendar";

export default defineComponent({
    components: {
        DailyCalendar,
        AppLayout,
    },
    props: ['hours_of_day', 'myRooms', 'rooms', 'projects', 'event_types', 'areas', 'shown_day_formatted', 'shown_day_local', 'requested_wanted_day', 'start_time_of_new_event', 'end_time_of_new_event', 'events_without_room'],
})
</script>
