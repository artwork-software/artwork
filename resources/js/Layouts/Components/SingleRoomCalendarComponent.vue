<template>
    <div class="w-full bg-secondaryHover overflow-y-auto" id="myCalendar">
        <div :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'">
            <CalendarFunctionBar :personal-filters="personalFilters" :filter-options="filterOptions" :roomMode="true" :project="project" @open-event-component="openEditEventModal" @increment-zoom-factor="incrementZoomFactor" @decrement-zoom-factor="decrementZoomFactor" :zoom-factor="zoomFactor" :is-fullscreen="isFullscreen" @enterFullscreenMode="openFullscreen" :dateValue="dateValue"
                                 @change-at-a-glance="changeAtAGlance"
                                 :at-a-glance="atAGlance" :user_filters="user_filters"
                                 @previousTimeRange="previousTimeRange"
                                 @next-time-range="nextTimeRange"
            ></CalendarFunctionBar>
            <div class="ml-5 flex errorText items-center cursor-pointer mb-5 w-48"
                 @click="openEventsWithoutRoomComponent()"
                 v-if="filteredEvents?.length > 0">

                <ExclamationIcon class="h-6  mr-2"/>
                {{ filteredEvents?.length === 1 ? $t('{0} Event without room!', [filteredEvents?.length]) : $t('{0} Events without room!', [filteredEvents?.length]) }}
            </div>
            <!-- Calendar -->
            <table class="w-full flex flex-wrap bg-white">
                <tbody class="flex w-full flex-wrap">
                    <tr :style="{height: zoomFactor * 115 + 'px'}" class="w-full flex" v-for="day in days">
                        <th class="w-20 eventTime text-secondary text-right -mt-2 pr-1">
                            {{day.day_string}} {{ day.full_day }} <span v-if="day.is_monday" class="text-[10px] font-normal ml-0.5">(KW{{ day.week_number }})</span>
                        </th>
                        <td :style="{ height: zoomFactor * 115 + 'px'}" class="cell flex-row w-full  flex overflow-y-auto border-t-2 border-dashed">
                            <div class="py-0.5 pr-2" v-for="event in calendarData[day.full_day].events">
                                <SingleCalendarEvent :zoom-factor="zoomFactor"
                                                     :width="zoomFactor * 204"
                                                     :event="event"
                                                     :event-types="eventTypes"
                                                     @open-edit-event-modal="openEditEventModal"
                                                     :first_project_tab_id="this.first_project_tab_id"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="selectedEvent"
        :wantedRoomId="wantedRoom"
        :isAdmin="hasAdminRole() || $canAny(['create, delete and update rooms'])"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
        :eventStatuses="eventStatuses"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :eventsWithoutRoom="this.filteredEvents"
        :isAdmin="hasAdminRole() || $canAny(['create, delete and update rooms'])"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />

</template>

<script>
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import {ExclamationIcon} from "@heroicons/vue/outline";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import {inject, provide} from "vue";

export default {
    name: "IndividualCalendarComponent",
    mixins: [Permissions],
    components: {
        CalendarFunctionBar,
        SingleCalendarEvent,
        IndividualCalendarFilterComponent,
        EventsWithoutRoomComponent,
        ExclamationIcon,
        EventComponent
    },
    data() {
        return {
            showEventsWithoutRoomComponent: false,
            selectedEvent: null,
            createEventComponentIsVisible: false,
            wantedRoom: null,
            roomCollisions: [],
            isFullscreen: false,
            zoomFactor: 1,
            event_properties: inject('event_properties')
        }
    },
    props: [
        'calendarData',
        'rooms',
        'days',
        'atAGlance',
        'eventTypes',
        'dateValue',
        'project',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'first_project_tab_id',
        'first_project_calendar_tab_id',
        'eventStatuses'
    ],
    emits: ['changeAtAGlance'],
    created() {
        provide('event_properties', this.event_properties);
    },
    mounted(){
        window.addEventListener('resize', this.listenToFullscreen);
    },
    computed: {
        textStyle() {
            const fontSize = `calc(${this.zoomFactor} * 0.875rem)`;
            const lineHeight = `calc(${this.zoomFactor} * 1.25rem)`;
            return {
                fontSize,
                lineHeight,
            };
        },
        filteredEvents() {
            return this.eventsWithoutRoom.filter((event) => {
                let createdBy = event.created_by;
                let projectLeaders = event.projectLeaders;

                if (createdBy.id === 1 || projectLeaders?.some((leader) => leader.id === 1)) {
                    return true;
                }
                return false;
            });
        }
    },
    methods: {
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
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
            router.patch(route('update.user.calendar.filter.dates', this.$page.props.auth.user.id), {
                start_date:  this.dateValue[0],
                end_date: this.dateValue[1],
            },{
                preserveScroll: true
            })
        },
        changeAtAGlance() {
            this.$emit('changeAtAGlance')
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
        openEditEventModal(event = null) {

            this.wantedRoom = event?.roomId;

            if (event === null) {
                this.selectedEvent = null;
                this.createEventComponentIsVisible = true;
                return;
            }

            if (!event.id) {
                event = {
                    start: event?.start,
                    end: event?.end,
                    projectId: this.project?.id,
                    projectName: this.project?.name,
                    roomId: event.roomId,
                }
            }


            if (event?.start && event?.end) {
                axios.post('/collision/room', {
                    params: {
                        start: event?.start,
                        end: event?.end,
                    }
                }).then(response => this.roomCollisions = response.data);
            }
            this.selectedEvent = event;
            this.createEventComponentIsVisible = true;

        },
        onEventComponentClose(bool) {
            this.createEventComponentIsVisible = false;
            if (bool) {
                router.reload();
            }
        },


        /* View in fullscreen */
        openFullscreen() {
            let elem = document.getElementById("myCalendar");
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
                this.isFullscreen = true;
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        },
        listenToFullscreen() {
            if (window.innerHeight === screen.height) {
                this.isFullscreen = true;
            } else {
                this.isFullscreen = false;
                this.zoomFactor = 1;
            }
        },
        incrementZoomFactor() {
            if (this.zoomFactor < 1.4) {
                this.zoomFactor = Math.round((this.zoomFactor + 0.2) * 10) / 10;
            }
        },
        decrementZoomFactor() {
            if (this.zoomFactor > 0.2) {
                this.zoomFactor = Math.round((this.zoomFactor - 0.2) * 10) / 10;
            }
        },
        openEventsWithoutRoomComponent() {
            this.showEventsWithoutRoomComponent = true;
        },
    }
}
</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell {
    overflow: overlay;
}

</style>
