<template>
    <app-layout>
        <div id="shiftPlan" class="bg-white w-[98%]" :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? 'h-[50vh] overflow-x-scroll mt-8' : ' mt-24']">
            <ShiftPlanFunctionBar @previousTimeRange="previousTimeRange"
                                  @next-time-range="nextTimeRange"
                                  :date-value="dateValue"
                                  :all-shifts-committed="true"
                                  :filter-options="filterOptions"
                                  :personal-filters="personalFilters"
                                  :rooms="shiftPlan"
                                  @enterFullscreenMode="openFullscreen"></ShiftPlanFunctionBar>
            <table class="w-full bg-white relative">
                <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                <div>
                    <tr class="flex w-full bg-secondaryHover stickyHeader">
                        <th :style="{minWidth: 164 + 'px'}"></th>
                        <th v-for="day in days" :style="{minWidth: 200 + 'px'}" class="h-16 py-3 border-r-4 border-secondaryHover truncate">
                            <div class="flex calendarRoomHeader font-semibold ml-4 mt-2">
                                {{ day.day_string }} {{ day.day }}
                            </div>
                        </th>
                    </tr>
                    <tbody class="w-full pt-3">
                    <tr v-for="(room,index) in shiftPlan" class="w-full flex">
                        <th class="xsDark flex items-center -mt-2 h-28 w-40"
                            :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxis']">
                            <Link class="flex font-semibold items-center ml-4">
                                {{ room[days[0].day].roomName }}
                            </Link>
                        </th>
                        <td v-for="day in days" :style="{minWidth: 200 + 'px'}" class="max-h-28 overflow-y-auto cell">
                            <div v-for="event in room[day.day].events.data" class="mb-1">
                                <SingleShiftPlanEvent :eventType="this.findEventTypeById(event.eventTypeId)"
                                                      :project="this.findProjectById(event.projectId)" :event="event"/>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </div>
            </table>
        </div>
        <ShiftPlanUserOverview :users="dropUsers" @isOpen="showUserOverviewBar" :days="days"/>


    </app-layout>

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
import ShiftPlanUserOverview from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanUserOverview.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "ShiftPlan",
    mixins: [Permissions],
    components: {
        ShiftPlanUserOverview,
        Link, CalendarFunctionBar, SingleCalendarEvent, ExclamationIcon, EventComponent,
        SingleShiftPlanEvent,
        CheckIcon,
        AppLayout,
        ShiftPlanFunctionBar
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
        'eventTypes',
        'users', 'freelancers', 'serviceProviders'
    ],
    mounted() {
        console.log("shiftplan:")
        console.log(this.shiftPlan)
    },
    computed: {
        dropUsers(){
            const users = [];
            this.users.forEach((user) => {
                users.push({
                    element: user,
                    type: 0
                })
            })
            this.freelancers?.forEach((freelancer) => {
                users.push({
                    element: freelancer,
                    type: 1
                })
            })
            this.serviceProviders?.forEach((provider) => {
                users.push({
                    element: provider,
                    type: 2
                })
            })
            return users;
        },
    },
    methods: {
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
        showUserOverviewBar(isOpen) {
            this.showUserOverview = isOpen;
        },
        openFullscreen() {
            let elem = document.getElementById('shiftPlan');
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
                this.isFullscreen = true;
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        },
        previousTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[1] = this.getPreviousDay(this.dateValue[0]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() - dayDifference);
            this.dateValue[0] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        nextTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[0] = this.getNextDay(this.dateValue[1]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() + dayDifference + 1);
            this.dateValue[1] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        getNextDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getPreviousDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        updateTimes() {
            Inertia.reload({
                data: {
                    startDate: this.dateValue[0],
                    endDate: this.dateValue[1],
                }
            })
        },
    },
    data() {
        return {
            showUserOverview: false,
            isFullscreen: false,
        }
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
.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 60px;
    z-index: 23;
}
.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 60px;
    z-index: 22;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}

</style>


