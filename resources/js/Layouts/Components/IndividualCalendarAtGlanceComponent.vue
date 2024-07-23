<template>
    <div class="-mt-4">
        <FunctionBarCalendar
            :multi-edit="multiEdit"
            :rooms="rooms"
            :project="project"
            @wants-to-add-new-event="openEditEventModal"
            @update-multi-edit="changeMultiEdit"/>
    </div>
    <div class="w-full flex">
        <!-- Calendar -->
        <div class="flex pl-14">
            <template v-if="eventsAtAGlance">
                <div v-for="room in computedRooms">
                    <div class="w-52 py-3 border-r-4 border-secondaryHover bg-userBg">
                        <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                            {{ room.name }}
                        </div>
                    </div>
                    <div v-for="day in eventsAtAGlanceRef">
                        <template v-for="event in day.events">
                            <div v-if="event.roomId === room.id">
                                <div class="at-a-glance-event-container py-0.5 pr-1 min-h-[45px]"
                                     :data-event-id="event.id">
                                    <SingleCalendarEvent
                                        v-if="this.currentEventsInView.has(String(event.id))"
                                        :atAGlance="true"
                                        :multiEdit="multiEdit"
                                        :project="project ? project : false"
                                        :zoom-factor="1"
                                        :width="204"
                                        :event="event"
                                        :event-types="eventTypes"
                                        @open-edit-event-modal="openEditEventModal"
                                        :first_project_tab_id="this.first_project_tab_id"
                                    />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <div v-else>
                <div class="pl-6 pb-12 mt-10 xsDark">
                    {{$t('No events for this project')}}
                </div>
            </div>
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
        :isAdmin="this.hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="this.eventsWithoutRoom"
        :isAdmin="this.hasAdminRole()"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />

    <div v-show="multiEdit"
         class="-ml-7 -mb-2 absolute z-50 w-full bg-white/70 bottom-0 h-20 shadow border-t border-gray-100 flex items-center justify-center gap-4">
        <FormButton :text="$t('Move events')"
                   @click="openMultiEditModal"/>
        <FormButton @click="openDeleteSelectedEventsModal = true"
                   class="!border-2 !border-artwork-buttons-create bg-transparent !text-artwork-buttons-create hover:!text-white hover:!bg-artwork-buttons-hover !hover:border-transparent resize-none"
                   :text="$t('Delete events')"/>
    </div>

    <MultiEditModal :checked-events="editEvents" v-if="showMultiEditModal" :rooms="rooms"
                    @closed="closeMultiEditModal"/>

    <ConfirmDeleteModal
        v-if="openDeleteSelectedEventsModal"
        @closed="openDeleteSelectedEventsModal = false"
        @delete="deleteSelectedEvents"
        :title="$t('Delete assignments')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"/>
</template>

<script>

import {router} from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {ref} from "vue";
import FunctionBarCalendar from "@/Components/FunctionBars/FunctionBarCalendar.vue";

export default {
    name: "IndividualCalendarAtGlanceComponent",
    mixins: [Permissions],
    components: {
        FunctionBarCalendar,
        FormButton,
        ConfirmDeleteModal,
        MultiEditModal,
        SingleCalendarEvent,
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
          zoomFactor: 1,
          multiEdit: false,
          editEvents: [],
          showMultiEditModal: false,
          openDeleteSelectedEventsModal: false,
          allEvents: this.eventsAtAGlance,
          currentEventsInView: new Set(),
          eventsAtAGlanceRef: ref(JSON.parse(JSON.stringify(this.eventsAtAGlance))),
      }
    },
    props: [
        'eventsAtAGlance',
        'atAGlance',
        'dateValue',
        'eventTypes',
        'rooms',
        'project',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    mounted() {
        const observer = new IntersectionObserver(
                (observables) => {
                    observables.forEach((atAGlanceEventContainerObserver) => {
                        let eventId = atAGlanceEventContainerObserver.target.getAttribute('data-event-id');

                        if (atAGlanceEventContainerObserver.isIntersecting) {
                            this.currentEventsInView.add(eventId);
                        } else {
                            this.currentEventsInView.delete(eventId);
                        }
                    });

                }
            ),
            eventContainers = document.querySelectorAll('.at-a-glance-event-container');

        eventContainers.forEach((container) => {
            observer.observe(container);
        });
    },
    computed: {
        computedRooms() {
            let computedRooms = [];

            console.debug('computed rooms');

            this.rooms.forEach((room) => {
                let hasEvents = Object.values(this.eventsAtAGlanceRef).some((day) => {
                    return day.events.some((event) => {
                        return event.roomId === room.id;
                    });
                });

                if (hasEvents) {
                    computedRooms.push(room);
                }
            });

            return computedRooms;
        }
    },
    methods: {
        changeMultiEdit(multiEdit) {
            this.multiEdit = multiEdit;
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
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            router.reload();
        },
        openMultiEditModal() {
            this.getCheckedEvents();

            this.showMultiEditModal = true;
        },
        deleteSelectedEvents() {
            this.getCheckedEvents();
            router.post(route('multi-edit.delete'), {
                events: this.editEvents
            }, {
                onSuccess: () => {
                    this.openDeleteSelectedEventsModal = false
                }
            })
        },
        getCheckedEvents() {
            this.editEvents = [];
            const eventArray = [];

            this.rooms.forEach((room) => {
                this.eventsAtAGlance[room.id]?.forEach((events) => {
                    if (events.clicked) {
                        if (!eventArray.includes(events.id)) {
                            eventArray.push(events.id)
                        }
                    }
                })
            })
            this.editEvents = eventArray
        },
        closeMultiEditModal() {
            this.showMultiEditModal = false;
        },
    }
}
</script>

<style scoped>

</style>
