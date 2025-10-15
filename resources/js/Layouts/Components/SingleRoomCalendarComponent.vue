<template>
    <div class="w-full bg-white" id="myCalendar">
        <BaseCalendar
            :rooms="filteredRooms"
            :days="days"
            :calendar-data="filteredCalendarData"
            :eventsWithoutRoom="eventsWithoutRoom"
            :eventStatuses="eventStatuses"
            :project="project"
            :projectNameUsedForProjectTimePeriod="project?.name || ''"
            :firstProjectShiftTabId="first_project_calendar_tab_id"
            :isPlanning="false"
            :verifierForEventTypIds="[]"
        />
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
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";
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
        BaseCalendar,
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
        'room',
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
        'eventStatuses',
        'months',
        'areas'
    ],
    emits: ['changeAtAGlance'],
    created() {
        // Provide all required injected values for FunctionBarCalendar and other components
        provide('event_properties', this.event_properties);
        provide('eventTypes', this.eventTypes);
        provide('rooms', this.rooms);
        provide('areas', this.areas);
        provide('dateValue', this.dateValue);
        provide('first_project_tab_id', this.first_project_tab_id);
        provide('first_project_calendar_tab_id', this.first_project_calendar_tab_id);
        provide('filterOptions', this.filterOptions);
        provide('personalFilters', this.personalFilters);
        provide('user_filters', this.user_filters);
        provide('months', this.months);
        provide('eventStatuses', this.eventStatuses);
    },
    mounted(){
        window.addEventListener('resize', this.listenToFullscreen);
    },
    computed: {
        filteredRooms() {
            // Return all rooms to show all room names in calendar
            return this.rooms;
        },
        filteredCalendarData() {
            // Return all calendar data to show events from all rooms
            return this.calendarData;
        },
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
