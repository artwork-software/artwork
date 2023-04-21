<template>
    <div class="w-full flex flex-wrap bg-secondaryHover overflow-y-auto" id="myCalendar">
        <div class="flex justify-center w-full bg-white">
            <div class="mt-4 flex errorText items-center cursor-pointer mb-2"
                 @click="openEventsWithoutRoomComponent()"
                 v-if="eventsWithoutRoom?.length > 0">
                <ExclamationIcon class="h-6  mr-2"/>
                {{
                    eventsWithoutRoom?.length
                }}{{ eventsWithoutRoom?.length === 1 ? ' Termin ohne Raum!' : ' Termine ohne Raum!' }}
            </div>
        </div>
        <div :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'">
            <CalendarFunctionBar :project="project" @open-event-component="openEditEventModal"
                                 @increment-zoom-factor="incrementZoomFactor"
                                 @decrement-zoom-factor="decrementZoomFactor" :zoom-factor="zoomFactor"
                                 :is-fullscreen="isFullscreen" @enterFullscreenMode="openFullscreen"
                                 :dateValue="dateValue"
                                 @change-at-a-glance="changeAtAGlance"
                                 @change-multi-edit="changeMultiEdit"
                                 :at-a-glance="atAGlance"></CalendarFunctionBar>
            <!-- Calendar -->
            <table class="w-full flex flex-wrap bg-white">
                <thead class="w-full">
                <tr class=" w-full flex bg-userBg">
                    <th class="w-16">
                    </th>
                    <th v-for="room in rooms" :style="{ width: zoomFactor * 212 + 'px'}"
                        class="py-3 border-r-4 border-secondaryHover">
                        <div :style="textStyle" class="flex font-semibold items-center ml-4">
                            {{ room.name }}
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody class="flex w-full pt-3 flex-wrap">
                <tr :style="{height: zoomFactor * 115 + 'px'}" class="w-full flex"
                    :class="day.is_weekend ? 'bg-backgroundGray' : 'bg-white'" v-for="day in days">
                    <th class="w-20 eventTime text-secondary text-right -mt-2 pr-1">
                        {{ day.day_string }} {{ day.day }}
                    </th>
                    <td :style="{ width: zoomFactor * 212 + 'px', height: zoomFactor * 115 + 'px'}"
                        class="cell border-t-2 border-dashed" :class="day.is_weekend ? 'bg-backgroundGray' : 'bg-white'"
                        v-for="room in calendarData">
                        <div class="py-0.5 pr-2" v-for="event in room[day.day].data">
                            <!-- <CalendarEventTooltip :show-tooltip="event.hovered" :event="event"> -->
                            <SingleCalendarEvent class="relative" :project="project" :multiEdit="multiEdit"
                                                 :zoom-factor="zoomFactor" :width="zoomFactor * 204" :event="event"
                                                 :event-types="eventTypes"
                                                 @open-edit-event-modal="openEditEventModal"/>
                            <!-- </CalendarEventTooltip> -->
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

    <div v-show="multiEdit"
         class="fixed z-50 w-full bg-white/70 bottom-0 h-20 shadow border-t border-gray-100 flex items-center justify-center gap-4">
        <AddButton mode="modal" class="bg-primary text-white resize-none" text="Termine verschieben"
                   @click="openMultiEditModal"/>
        <AddButton mode="modal"
                   class="!border-2 !border-buttonBlue bg-transparent !text-buttonBlue hover:!text-white hover:!bg-buttonHover !hover:border-transparent resize-none"
                   text="Termine löschen"/>
    </div>

    <MultiEditModal :checked-events="editEvents" v-if="showMultiEditModal" :rooms="rooms"
                    @closed="closeMultiEditModal"/>

</template>

<script>
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import {ExclamationIcon} from "@heroicons/vue/outline";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {Inertia} from "@inertiajs/inertia";
import AddButton from "@/Layouts/Components/AddButton.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import CalendarEventTooltip from "@/Layouts/Components/CalendarEventTooltip.vue";


export default {
    name: "IndividualCalendarComponent",
    components: {
        CalendarEventTooltip,
        MultiEditModal,
        AddButton,
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
            multiEdit: false,
            editEvents: [],
            showMultiEditModal: false,
        }
    },
    props: ['calendarData', 'rooms', 'days', 'atAGlance', 'eventTypes', 'dateValue', 'project', 'eventsWithoutRoom'],
    emits: ['changeAtAGlance'],
    mounted() {
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
        changeMultiEdit(multiEdit) {
            this.multiEdit = multiEdit;
        },
        changeAtAGlance(atAGlance) {
            this.$emit('changeAtAGlance', atAGlance)
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

        openMultiEditModal() {
            this.getCheckedEvents();
            console.log(this.calendarData)

            this.showMultiEditModal = true;
        },
        getCheckedEvents() {
            const eventArray = [];
            this.days.forEach((day) => {
                this.calendarData.forEach((room) => {
                    room[day.day].data.forEach((event) => {
                        if (event.clicked) {
                            eventArray.push(event.id)
                        }
                    })
                })
            })
            this.editEvents = eventArray
        },
        closeMultiEditModal() {
            this.showMultiEditModal = false;
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
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            Inertia.reload();
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
