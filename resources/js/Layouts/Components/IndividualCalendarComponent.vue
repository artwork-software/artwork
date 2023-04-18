<template>
    <div class="w-full flex flex-wrap bg-secondaryHover overflow-y-auto" id="myCalendar">
        <div :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'">
            <CalendarFunctionBar :project="project" @open-event-component="openEditEventModal" @increment-zoom-factor="incrementZoomFactor" @decrement-zoom-factor="decrementZoomFactor" :zoom-factor="zoomFactor" :is-fullscreen="isFullscreen" @enterFullscreenMode="openFullscreen" :dateValue="dateValue"
                                 @change-at-a-glance="changeAtAGlance"
                                 :at-a-glance="atAGlance"></CalendarFunctionBar>
            <div class="ml-5 flex errorText items-center cursor-pointer mb-5 w-48"
                 @click="openEventsWithoutRoomComponent()"
                 v-if="eventsWithoutRoom.length > 0">

                <ExclamationIcon class="h-6  mr-2"/>
                {{ eventsWithoutRoom.length }}{{ eventsWithoutRoom.length === 1 ? ' Termin ohne Raum!' : ' Termine ohne Raum!' }}
            </div>
            <!-- Calendar -->
            <table class="w-full flex flex-wrap bg-white">
                <thead class="w-full">
                <tr class=" w-full flex bg-userBg">
                    <th class="w-16">

                    </th>
                    <th v-for="room in rooms" :style="{ width: zoomFactor * 212 + 'px'}"  class="py-3 border-r-4 border-secondaryHover">
                        <div :style="textStyle" class="flex font-semibold items-center ml-4">
                            {{ room.name }}
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody class="flex w-full pt-3 flex-wrap">
                <tr :style="{height: zoomFactor * 115 + 'px'}" class="w-full flex" v-for="day in days">
                    <th class="w-16 eventTime text-secondary text-right -mt-2 pr-1">
                        {{ day.day }}
                    </th>
                    <td :style="{ width: zoomFactor * 212 + 'px', height: zoomFactor * 115 + 'px'}" class="cell overflow-y-auto border-t-2 border-dashed" v-for="room in calendarData">
                        <div class="py-0.5 pr-2" v-for="event in room[day.day].data">
                            <SingleCalendarEvent :multiEdit="multiEdit" :zoom-factor="zoomFactor" :width="zoomFactor * 204" :event="event" :event-types="eventTypes"
                                                 @open-edit-event-modal="openEditEventModal"/>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
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
            selectedEvent: null,
            createEventComponentIsVisible: false,
            wantedRoom: null,
            roomCollisions: [],
            isFullscreen: false,
            zoomFactor: 1
        }
    },
    props: ['calendarData', 'rooms', 'days', 'atAGlance', 'eventTypes', 'dateValue','project'],
    emits: ['changeAtAGlance'],
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
    },
    methods: {
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
        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            Inertia.reload();
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
    }
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
