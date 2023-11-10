<template>
    <div class=" h-full w-full flex flex-col">
        <ShiftHeader>
            <div ref="shiftPlan" id="shiftPlan" class="bg-white flex-grow"
                 :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' mt-8 max-h-[38rem]' : ' mt-24','overflow-x-scroll ']">
                <ShiftPlanFunctionBar @previousTimeRange="previousTimeRange"
                                      @next-time-range="nextTimeRange"
                                      :date-value="dateValue"
                                      :all-shifts-committed="true"
                                      :filter-options="filterOptions"
                                      :personal-filters="personalFilters"
                                      :rooms="shiftPlan"
                                      @enterFullscreenMode="openFullscreen"
                                      @openHistoryModal="openHistoryModal"
                                      :user_filters="user_filters"
                ></ShiftPlanFunctionBar>
                <table class="w-full bg-white">
                    <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                    <div>
                        <tr class="flex w-full bg-secondaryHover stickyHeader">
                            <th class="z-0" :style="{minWidth: 164 + 'px'}"></th>
                            <th v-for="day in days" :style="{minWidth: 200 + 'px'}"
                                class="z-20 h-16 py-3 border-r-4 border-secondaryHover truncate">
                                <div class="flex calendarRoomHeader font-semibold ml-4 mt-2">
                                    {{ day.day_string }} {{ day.day }}
                                </div>
                            </th>
                        </tr>
                        <tbody class="w-full pt-3">
                        <tr v-for="(room,index) in shiftPlan" class="w-full flex">
                            <th class="xsDark flex items-center -mt-2 h-28 w-40"
                                :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                <Link class="flex font-semibold items-center ml-4">
                                    {{ room[days[0].day].roomName }}
                                </Link>
                            </th>
                            <td v-for="day in days" :style="{minWidth: 200 + 'px'}"
                                class="max-h-28 overflow-y-auto cell">
                                <div v-for="event in room[day.day].events.data" class="mb-1">
                                    <SingleShiftPlanEvent :eventType="this.findEventTypeById(event.eventTypeId)"
                                                          :project="this.findProjectById(event.projectId)"
                                                          :event="event" v-if="event.shifts.length > 0"/>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </div>
                </table>
            </div>
            <div id="userOverview" :style="{ 'max-height': computedUserOverviewMaxHeight }" class="w-[102.5%]  overflow-x-scroll -ml-5">
                <div class="flex justify-center overflow-y-scroll ">
                    <div v-if="this.$can('can plan shifts') || this.hasAdminRole()" @click="showCloseUserOverview" :class="showUserOverview ? '' : 'fixed bottom-0 '"
                         class="flex h-5 w-8 justify-center items-center cursor-pointer bg-primary">
                        <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14.123" height="6.519"
                                 viewBox="0 0 14.123 6.519">
                                <g id="Gruppe_1608" data-name="Gruppe 1608"
                                   transform="translate(-275.125 870.166) rotate(-90)">
                                    <path id="Pfad_1313" data-name="Pfad 1313" d="M0,0,6.814,3.882,13.628,0"
                                          transform="translate(865.708 289) rotate(-90)" fill="none" stroke="#a7a6b1"
                                          stroke-width="1"/>
                                    <path id="Pfad_1314" data-name="Pfad 1314" d="M0,0,4.4,2.509,8.809,0"
                                          transform="translate(864.081 286.591) rotate(-90)" fill="none"
                                          stroke="#a7a6b1" stroke-width="1"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
                <div ref="userOverview" class="w-full bg-primary overflow-x-scroll min-h-[40rem]"
                     v-show="showUserOverview">
                    <table class="w-full text-white overflow-y-scroll">
                        <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                        <div>
                            <tr class="flex w-full">
                                <th class="w-56"></th>
                                <th v-for="day in days" class="flex w-[12.5rem] p-5 h-16 items-center">
                                    <div class="flex calendarRoomHeader font-semibold">
                                        {{ day.day_string }} {{ day.day }}
                                    </div>
                                </th>
                            </tr>
                            <tbody class="w-full pt-3">
                            <tr v-for="(user,index) in dropUsers" class="w-full flex">
                                <th class="stickyYAxisNoMarginLeft flex items-center text-right -mt-2 pr-1 w-56"
                                    :class="index % 2 === 0 ? '' : ''">
                                    <DragElement :item="user.element" :expected-hours="user.expectedWorkingHours"
                                                 :planned-hours="user.plannedWorkingHours" :type="user.type"/>
                                </th>
                                <td v-for="day in days">
                                    <div
                                        class="w-[12.375rem] h-12 p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer"
                                        @click="openShowUserShiftModal(user, day)">
                                        <span v-for="shift in user.element?.shifts[day.full_day]" v-if="!user.vacations?.includes(day.without_format)">
                                            {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                        </span>
                                        <span v-else class="h-full flex justify-center items-center">
                                            nicht verfügbar
                                        </span>

                                    </div>

                                </td>
                            </tr>
                            </tbody>
                        </div>
                    </table>
                </div>
            </div>
            <show-user-shifts-modal v-if="showUserShifts" @closed="showUserShifts = false" :user="userToShow"
                                    :day="dayToShow" :projects="projects" />
            <ShiftHistoryModal :history="history[0]" v-if="showHistoryModal" @closed="showHistoryModal = false"/>

        </ShiftHeader>
    </div>

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
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import ShiftHistoryModal from "@/Pages/Shifts/Components/ShiftHistoryModal.vue";
import ShowUserShiftsModal from "@/Pages/Shifts/Components/showUserShiftsModal.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";

export default {
    name: "ShiftPlan",
    mixins: [Permissions],
    components: {
        DragElement, ShowUserShiftsModal,
        ShiftHistoryModal,
        ShiftHeader,
        ShiftTabs,
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
        'history',
        'usersForShifts',
        'freelancersForShifts',
        'serviceProvidersForShifts',
        'user_filters'
    ],
    mounted() {
        // Listen for scroll events on both sections
        this.$refs.shiftPlan.addEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.addEventListener('scroll', this.syncScrollUserOverview);
    },
    computed: {
        dropUsers() {
            const users = [];
            this.usersForShifts.forEach((user) => {
                users.push({
                    element: user.user,
                    type: 0,
                    plannedWorkingHours: user.plannedWorkingHours,
                    expectedWorkingHours: user.expectedWorkingHours,
                    vacations: user.vacations,
                })
            })
            this.freelancersForShifts.forEach((freelancer) => {
                users.push({
                    element: freelancer.freelancer,
                    type: 1,
                    plannedWorkingHours: freelancer.plannedWorkingHours,
                })
            })
            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    plannedWorkingHours: service_provider.plannedWorkingHours,
                })
            })
            return users;
        },
        computedUserOverviewMaxHeight() {
            const minHeight = 33; // Minimum max height in rem
            const baseHeight = 52; // Base max height when there's 1 room

            // Calculate the adjusted max height based on the number of rooms
            // Adjust this value as needed based on your requirements
            const adjustmentPerRoom = 6.8; // Adjust this value as needed

            const totalRooms = this.rooms.length;
            let calculatedHeight = baseHeight - (adjustmentPerRoom * (totalRooms - 1));

            // Ensure the calculated height is not less than the minimum height
            calculatedHeight = Math.max(minHeight, calculatedHeight);

            // Return the max height as a string with "rem" unit
            return `${calculatedHeight}rem`;
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
            Inertia.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.user.id), {
                start_date: this.dateValue[0],
                end_date: this.dateValue[1],
            })
        },
        openHistoryModal() {
            this.showHistoryModal = true;
        },
        showCloseUserOverview() {
            this.showUserOverview = !this.showUserOverview
            //this.$emit('isOpen', this.showUserOverview)
        },
        syncScrollShiftPlan(event) {
            if (this.$refs.userOverview) {
                // Synchronize horizontal scrolling from shiftPlan to userOverview
                this.$refs.userOverview.scrollLeft = event.target.scrollLeft;
            }
        },
        syncScrollUserOverview(event) {
            if (this.$refs.shiftPlan) {
                // Synchronize horizontal scrolling from userOverview to shiftPlan
                this.$refs.shiftPlan.scrollLeft = event.target.scrollLeft;
            }
        },
        openShowUserShiftModal(user, day) {
            this.userToShow = user
            this.dayToShow = day
            this.showUserShifts = true
        }
    },
    data() {
        return {
            showUserOverview: this.$can('can plan shifts') || this.hasAdminRole(),
            isFullscreen: false,
            showHistoryModal: false,
            showUserShifts: false,
            userToShow: null,
            dayToShow: null,
        }
    },
    beforeDestroy() {
        // Remove event listeners when component is destroyed
        this.$refs.shiftPlan.removeEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.removeEventListener('scroll', this.syncScrollUserOverview);
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
    top: 0px;
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


