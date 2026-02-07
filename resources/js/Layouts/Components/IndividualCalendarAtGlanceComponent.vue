<template>
    <div class="bg-white">
        <div class="w-full z-[100]" :class="project ? '-mt-10 -ml-6' : 'sticky -ml-2 -mt-1'">
            <FunctionBarCalendar
                :multi-edit="multiEdit"
                :rooms="rooms"
                :project="project"
                :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                @searching-for-project="toggleSearchingForProject"
                @wants-to-add-new-event="openEditEventModal"
                @update-multi-edit="changeMultiEdit"/>
        </div>
        <div class="flex relative events-at-a-glance-container" :class="project ? '-ml-6' : '-ml-1 -mt-6'">
            <template v-if="eventsAtAGlanceRef && eventsAtAGlanceRef.length > 0">
                <div v-for="roomData in eventsAtAGlanceRef" :key="roomData.roomId">
                    <div :class="isSearchingForProject ? '' : 'sticky' + (isCalendarViewRoute ? ' top-[4.5rem] mt-7' : '')"
                         class="w-52 py-3 border-r-4 border-secondaryHover bg-userBg z-40">
                        <div class="flex calendarRoomHeader font-semibold items-center ml-4">
                            {{ roomData.roomName }}
                        </div>
                    </div>

                    <div v-for="(dayData, dateKey) in getDaysFromRoomData(roomData)" :key="dateKey">
                        <template v-for="event in dayData.events" :key="event.id">
                            <div class="min-h-[46px]">
                                <div class="at-a-glance-event-container py-0.5 pr-1"
                                     :data-event-id="event.id">
                                    <SingleCalendarEvent v-if="currentEventsInView.has(String(event.id))"
                                                         :atAGlance="true"
                                                         :multiEdit="multiEdit"
                                                         :project="project ? project : false"
                                                         :zoom-factor="1"
                                                         :width="204"
                                                         :event="event"
                                                         :event-types="props.eventTypes"
                                                         :first_project_tab_id="props.first_project_tab_id"
                                                         @open-edit-event-modal="openEditEventModal"/>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <div v-else>
                <div class="pl-6 pb-12 mt-10 xsDark">
                    {{ $t('No events for this project') }}
                </div>
            </div>
        </div>
    </div>
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose"
        :showHints="usePage().props.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="selectedEvent"
        :wantedRoomId="wantedRoom"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="props.first_project_calendar_tab_id"
        :event-statuses="eventStatuses"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="usePage().props.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="eventsWithoutRoom.value"
        :isAdmin="hasAdminRole()"
        :first_project_calendar_tab_id="props.first_project_calendar_tab_id"
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

<script setup>
import {onMounted, ref} from "vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import FunctionBarCalendar from "@/Components/FunctionBars/FunctionBarCalendar.vue";
import {usePermission} from "@/Composeables/Permission.js";
import {router, usePage} from "@inertiajs/vue3";
import {inject, provide} from "vue";

provide('event_properties', inject('event_properties'));

const {hasAdminRole} = usePermission(usePage().props),
    props = defineProps([
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
        'first_project_calendar_tab_id',
        'projectNameUsedForProjectTimePeriod',
        'eventStatuses',
        'isCalendarViewRoute'
    ]),
    showEventsWithoutRoomComponent = ref(false),
    eventsWithoutRoom = ref([]),
    selectedEvent = ref(null),
    createEventComponentIsVisible = ref(false),
    wantedRoom = ref(null),
    roomCollisions = ref([]),
    zoomFactor = ref(1),
    multiEdit = ref(false),
    editEvents = ref([]),
    showMultiEditModal = ref(false),
    openDeleteSelectedEventsModal = ref(false),
    currentEventsInView = ref(new Set()),
    eventsAtAGlanceRef = ref(JSON.parse(JSON.stringify(props.eventsAtAGlance))),
    // Extract day data from room object (all keys except roomId and roomName)
    getDaysFromRoomData = (roomData) => {
        const days = {};
        for (const key in roomData) {
            if (key !== 'roomId' && key !== 'roomName') {
                days[key] = roomData[key];
            }
        }
        return days;
    },
    changeMultiEdit = (multiEditEnabled) => {
        multiEdit.value = multiEditEnabled;
    },
    openEditEventModal = (event = null) => {
        wantedRoom.value = event?.roomId;

        if (event === null) {
            selectedEvent.value = null;
            createEventComponentIsVisible.value = true;
            return;
        }

        if (!event.id) {
            event = {
                start: event?.start,
                end: event?.end,
                projectId: props.project.value?.id,
                projectName: props.project.value?.name,
                roomId: event.roomId,
            }
        }

        if (event?.start && event?.end) {
            axios.post('/collision/room', {
                params: {
                    start: event?.start,
                    end: event?.end,
                }
            }).then(response => roomCollisions.value = response.data);
        }
        selectedEvent.value = event;
        createEventComponentIsVisible.value = true;
    },
    onEventComponentClose = (bool) => {
        createEventComponentIsVisible.value = false;

        if (bool) {
            router.reload();
        }
    },
    onEventsWithoutRoomComponentClose = () => {
        showEventsWithoutRoomComponent.value = false;
        router.reload();
    },
    openMultiEditModal = () => {
        showMultiEditModal.value = true;
    },
    deleteSelectedEvents = () => {
        router.post(route('multi-edit.delete'), {
            events: this.editEvents
        }, {
            onSuccess: () => {
                this.openDeleteSelectedEventsModal = false
            }
        });
    },
    closeMultiEditModal = () => {
        showMultiEditModal.value = false;
    };

const isSearchingForProject = ref(false);
const toggleSearchingForProject = (isShowingResults) => {
    isSearchingForProject.value = isShowingResults;
};

onMounted(() => {
    const observer = new IntersectionObserver(
            (observables) => {
                observables.forEach((atAGlanceEventContainerObserver) => {
                    let eventId = atAGlanceEventContainerObserver.target.getAttribute('data-event-id');

                    if (atAGlanceEventContainerObserver.isIntersecting) {
                        currentEventsInView.value.add(eventId);
                    } else {
                        currentEventsInView.value.delete(eventId);
                    }
                });
            },
            {
                root: document.getElementsByClassName('.events-at-a-glance-container')[0],
                rootMargin: '10000px'
            }
        ),
        eventContainers = document.querySelectorAll('.at-a-glance-event-container');

    eventContainers.forEach((container) => {
        observer.observe(container);
    });
});
</script>
