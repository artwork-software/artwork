<template>
    <ArtworkBaseModal
        @close="closeModal(false)"
        v-if="!editingEvent"
        :title="$t('Events without room')"
        :description="$t('These room booking requests have been rejected by the room admin. Cancel the appointments or move them to another room.')"
    >
        <div>
            <div class="flex justify-end mb-4" v-if="computedEventsWithoutRoom.length > 0">
                <button
                    @click="deleteAllEventsWithoutRoom"
                    class="flex rounded-2xl items-center gap-2 px-3 py-2 text-sm text-danger hover:text-danger hover:bg-danger-surface cursor-pointer"
                >
                    <IconTrash class="h-5 w-5" />
                    {{ $t('Delete all') }}
                </button>
            </div>
            <div class="flex my-4" v-for="event in computedEventsWithoutRoom" :key="event.id">
                <SingleEventInEventsWithoutRoom
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :event="event"
                    :event-types="eventTypes"
                    :rooms="rooms"
                    :isAdmin="isAdmin"
                    :event-statuses="eventStatuses"
                    @edit-event="onEditEvent"
                />
            </div>
        </div>
    </ArtworkBaseModal>

    <EventComponent
        v-if="editingEvent"
        :event="editingEvent"
        :event-types="eventTypes"
        :rooms="rooms"
        :is-admin="isAdmin"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :event-statuses="eventStatuses"
        :declined-room-id="editingEvent.declinedRoomId"
        :requires-axios-requests="true"
        @closed="onEventComponentClosed"
    />
</template>

<script setup>
import {computed, inject, provide, ref} from "vue";
import {router} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import SingleEventInEventsWithoutRoom from "@/Layouts/Components/SingleEventInEventsWithoutRoom.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {IconTrash} from "@tabler/icons-vue";
import axios from "axios";

const props = defineProps([
    'showHints',
    'eventTypes',
    'rooms',
    'isAdmin',
    'eventsWithoutRoom',
    'removeNotificationOnAction',
    'first_project_calendar_tab_id',
    'eventStatuses',
]);

const emit = defineEmits(['closed', 'desiresReload']);

const eventProperties = inject('event_properties');
provide('event_properties', eventProperties);

const editingEvent = ref(null);
const firstCall = ref(true);

const computedEventsWithoutRoom = computed(() => {
    props.eventsWithoutRoom.forEach((event) => {
        const dateStart = new Date(event.start);
        event.startDate = getDateOfDate(dateStart);
        event.startTime = getTimeOfDate(dateStart);

        const dateEnd = new Date(event.end);
        event.endDate = getDateOfDate(dateEnd);
        event.endTime = getTimeOfDate(dateEnd);

        if (firstCall.value) {
            event.creatingProject = false;
        }
    });
    firstCall.value = false;
    return props.eventsWithoutRoom;
});

function getTimeOfDate(date) {
    return ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2);
}

function getDateOfDate(date) {
    return date.getFullYear() + "-" +
        (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
        date.getDate().toString().padStart(2, '0');
}

function onEditEvent(event) {
    editingEvent.value = event;
}

function onEventComponentClosed(closedOnPurpose) {
    if (closedOnPurpose && editingEvent.value) {
        // Remove the event from the local list since it now has a room
        const idx = props.eventsWithoutRoom.findIndex(e => e.id === editingEvent.value.id);
        if (idx !== -1) {
            props.eventsWithoutRoom.splice(idx, 1);
        }
        if (props.removeNotificationOnAction) {
            editingEvent.value = null;
            closeModal(true);
            return;
        }
    }
    editingEvent.value = null;
}

function closeModal(bool) {
    emit('closed', bool);
}

function deleteAllEventsWithoutRoom() {
    const eventIds = computedEventsWithoutRoom.value.map(event => event.id);
    if (eventIds.length === 0) return;

    axios.delete('/events/bulk/delete', {data: {eventIds: eventIds}})
        .then(() => {
            props.eventsWithoutRoom.splice(0, props.eventsWithoutRoom.length);
            closeModal(true);
        })
        .catch(error => {
            console.error('Error deleting events:', error);
        });
}
</script>
