<template>
    <div class="w-full flex flex-wrap">
        <CalendarFunctionBar :dateValue="dateValue" @change-at-a-glance="changeAtAGlance" :at-a-glance="atAGlance"></CalendarFunctionBar>
        <div class="ml-5 flex errorText items-center cursor-pointer mb-5 w-48" @click="openEventsWithoutRoomComponent()"
             v-if="eventsWithoutRoom.length > 0">

            <ExclamationIcon class="h-6  mr-2"/>
            {{
                eventsWithoutRoom.length
            }}{{ eventsWithoutRoom.length === 1 ? ' Termin ohne Raum!' : ' Termine ohne Raum!' }}
        </div>
        <!-- Calendar -->
        <table class="w-full flex flex-wrap">
            <thead class="w-full">
            <tr class=" w-full flex bg-userBg">
                <th class="w-16">

                </th>
                <th v-for="room in rooms" class="w-52 py-3 border-r-4 border-secondaryHover">
                    <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                            {{ room.name }}
                    </div>
                </th>
            </tr>
            </thead>
            <tbody class="flex w-full pt-3 flex-wrap">
            <tr class="w-full h-36 flex" v-for="day in days">
                <th class="w-16 eventTime text-secondary text-right -mt-2 pr-1">
                    {{ day }}
                </th>
                <td class="w-52 h-36 cell overflow-y-auto border-t-2 border-dashed" v-for="room in calendarData">
                    <div class="py-0.5 pr-2" v-for="event in room[day].data">
                        <SingleCalendarEvent :event="event" :event-types="eventTypes" @open-edit-event-modal="openEditEventModal"/>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
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
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import {ExclamationIcon} from "@heroicons/vue/outline";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {Inertia} from "@inertiajs/inertia";



export default {
    name: "IndividualCalendarComponent",
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
          eventsWithoutRoom: [],
          project: null,
          selectedEvent: null,
          createEventComponentIsVisible: false,
          wantedRoom: null,
          roomCollisions: []
        }
    },
    props: ['calendarData', 'rooms', 'days','atAGlance', 'eventTypes','dateValue'],
    emits:['changeAtAGlance'],
    methods: {
        changeAtAGlance(atAGlance){
            this.$emit('changeAtAGlance', atAGlance)
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
        openEditEventModal(event){
            //console.log(event);
            this.selectedEvent = event;
            this.wantedRoom = event.roomId;

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
            this.createEventComponentIsVisible = true;

        },
        onEventComponentClose(){
            this.createEventComponentIsVisible = false;
            Inertia.reload();
        }
    }
}
</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell{
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
