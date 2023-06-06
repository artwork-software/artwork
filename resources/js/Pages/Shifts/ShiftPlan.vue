<template>
    <app-layout>
        <div>
            <table class="w-full bg-white relative mt-24">
                <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                <div>
                    <tr class="flex w-full bg-secondaryHover">
                        <th class="w-40"></th>
                        <th v-for="day in days" class="flex w-64 h-16 items-center">
                            <div class="flex calendarRoomHeader font-semibold">
                                {{ day.day_string }} {{ day.day }}
                            </div>
                        </th>
                    </tr>
                    <tbody class="w-full pt-3">
                    <tr v-for="(room,index) in shiftPlan" class="w-full flex">
                        <th class="xsDark flex items-center text-right -mt-2 pr-1 h-28 w-40" :class="index % 2 === 0 ? 'bg-backgroundGray' : ''">
                            <Link class="flex font-semibold items-center ml-4">
                                {{room[days[0].day].roomName}}
                            </Link>
                        </th>
                        <td v-for="day in days">
                            <div v-for="event in room[day.day].events.data" class="w-64 pr-2">
                                <SingleShiftPlanEvent :eventType="this.findEventTypeById(event.eventTypeId)" :project="this.findProjectById(event.projectId)" :event="event" />
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </div>
            </table>
        </div>
    </app-layout>

    <div class="w-full flex flex-wrap bg-secondaryHover"></div>
</template>
<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import Permissions from "@/mixins/Permissions.vue";
import {CheckIcon, ExclamationIcon} from "@heroicons/vue/outline";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import {Link} from "@inertiajs/inertia-vue3";

export default {
    name: "ShiftPlan",
    mixins: [Permissions],
    components: {
        Link, CalendarFunctionBar, SingleCalendarEvent, ExclamationIcon, EventComponent,
        SingleShiftPlanEvent,
        CheckIcon,
        AppLayout
    },
    props: [
        'events',
        'projects',
        'rooms',
        'shiftPlan',
        'days',
        'filterOptions',
        'dateValue',
        'personalFilters',
        'selectedDate',
        'eventTypes'
    ],
    methods: {
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
    },
    data() {
        return {}
    },
}

</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell {
    overflow: overlay;
}

::-webkit-scrollbar {
    width: 16px;
}

::-webkit-scrollbar-track {
    background-color: transparent;
}

::-webkit-scrollbar-thumb {
    background-color: #A7A6B170;
    border-radius: 16px;
    border: 6px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #a8bbbf;
}

</style>


