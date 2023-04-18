<template>
    <div class="w-full flex flex-wrap">
        <CalendarFunctionBar :project="project" @open-event-component="openEditEventModal" :dateValue="dateValue" @change-at-a-glance="changeAtAGlance"
                             :at-a-glance="atAGlance"></CalendarFunctionBar>

        <!-- Calendar -->
        <div class="flex">
            <div v-if="eventsAtAGlance" class="first:pl-16" v-for="roomEvents in eventsAtAGlance">
                <div class="w-52 py-3 border-r-4 border-secondaryHover bg-userBg">
                    <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                        {{roomEvents[0].roomName}}
                    </div>

                </div>
                <div class="py-0.5 pr-1" v-for="event in roomEvents">
                    <SingleProjectCalendarEvent :event="event"/>
                </div>
            </div>
            <div v-else>
                <div class="pl-6 pb-12 mt-10 xsDark">
                    Keine Termine für dieses Projekt im gewählten Zeitraum
                </div>
            </div>

        </div>
    </div>

    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="selectedEvent"
        :wantedRoomId="wantedRoom"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
        :roomCollisions="roomCollisions"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="this.eventsWithoutRoom"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
    />

</template>

<script>

import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import SingleProjectCalendarEvent from "@/Layouts/Components/SingleProjectCalendarEvent.vue";
import {Inertia} from "@inertiajs/inertia";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";



export default {
    name: "IndividualCalendarAtGlanceComponent",
    components: {
        CalendarFunctionBar,
        SingleProjectCalendarEvent,
        EventComponent,
        EventsWithoutRoomComponent,

    },
    data() {
      return {
          showEventsWithoutRoomComponent: false,
          eventsWithoutRoom: [],
          project: null,
          selectedEvent: null,
          createEventComponentIsVisible: false,
          wantedRoom: null,
          roomCollisions: [],
          isFullscreen: false,
          zoomFactor: 1
      }
    },
    props: ['eventsAtAGlance','atAGlance','dateValue','eventTypes','rooms','project'],
    emits:['changeAtAGlance'],
    methods: {
        changeAtAGlance(){
            this.$emit('changeAtAGlance')
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
        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            Inertia.reload();
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
    }
}
</script>

<style scoped>

</style>
