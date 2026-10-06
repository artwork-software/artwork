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
                         class="w-52 py-3 border-r-4   z-40">
                        <div class="flex text-[13px]/[18px] font-semibold items-center ml-4">
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
                <div class="pl-6 pb-12 mt-10 text-sm/5 font-semibold text-text">
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
         class="-ml-7 -mb-2 absolute z-50 w-full bg-white/70 bottom-0 h-20 shadow border-t border-border-subtle flex items-center justify-center gap-4">
        <FormButton :text="$t('Move events')"
                    :disabled="checkedEventIds.length === 0"
                    @click="openMultiEditModal"/>
        <FormButton @click="openDeleteSelectedEventsModal = true"
                    :disabled="checkedEventIds.length === 0"
                    class="!border-2 !border-accent-600 bg-transparent !text-accent-600 hover:!text-white hover:!bg-accent-700 !hover:border-transparent resize-none"
                    :text="$t('Delete events')"/>
    </div>

    <MultiEditModal :checked-events="checkedEventIds" v-if="showMultiEditModal" :rooms="rooms"
                    @closed="closeMultiEditModal"/>

    <ConfirmDeleteModal
        v-if="openDeleteSelectedEventsModal"
        @closed="openDeleteSelectedEventsModal = false"
        @delete="deleteSelectedEvents"
        :title="$t('Delete assignments')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"/>
</template>

<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, ref, watch} from "vue";
import axios from "axios";
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
    // Haken an allen Terminen verwerfen, auch an gerade nicht gerenderten
    clearEventSelection = () => {
        (eventsAtAGlanceRef.value ?? []).forEach((roomData) => {
            Object.values(getDaysFromRoomData(roomData)).forEach((dayData) => {
                (dayData?.events ?? []).forEach((event) => {
                    event.clicked = false;
                });
            });
        });
    },
    changeMultiEdit = (multiEditEnabled) => {
        multiEdit.value = multiEditEnabled;
        if (!multiEditEnabled) {
            clearEventSelection();
        }
    },
    // Termine nach Mehrfachbearbeitung neu laden (CalendarTab holt die Daten, sonst Seite neu laden)
    reloadCalendarTabData = inject('reloadCalendarTabData', null),
    reloadEvents = () => {
        if (typeof reloadCalendarTabData === 'function') {
            reloadCalendarTabData();
            return;
        }
        router.reload();
    },
    removeEventsLocally = (eventIds) => {
        const removed = new Set(eventIds);
        (eventsAtAGlanceRef.value ?? []).forEach((roomData) => {
            Object.values(getDaysFromRoomData(roomData)).forEach((dayData) => {
                if (Array.isArray(dayData?.events)) {
                    dayData.events = dayData.events.filter((event) => !removed.has(event.id));
                }
            });
        });
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
    // reloadEvents statt router.reload(): im Projekt-Kalendertab hält CalendarTab die Daten lokal,
    // ein Inertia-Reload erneuert sie nicht – die Ansicht blieb nach dem Bearbeiten veraltet
    onEventComponentClose = (bool) => {
        createEventComponentIsVisible.value = false;

        if (bool) {
            reloadEvents();
        }
    },
    onEventsWithoutRoomComponentClose = () => {
        showEventsWithoutRoomComponent.value = false;
        reloadEvents();
    },
    openMultiEditModal = () => {
        if (checkedEventIds.value.length === 0) {
            return;
        }
        showMultiEditModal.value = true;
    },
    deleteSelectedEvents = () => {
        // Nie eine leere Auswahl senden
        if (checkedEventIds.value.length === 0) {
            openDeleteSelectedEventsModal.value = false;
            return;
        }
        // JSON-Endpunkt (liefert bool, keine Inertia-Antwort) → axios wie in BaseCalendar
        const eventIds = [...checkedEventIds.value];
        axios.post(route('multi-edit.delete'), { events: eventIds })
            .then(() => removeEventsLocally(eventIds))
            .catch(() => {}) // Meldung zeigt der globale axios-Interceptor
            .finally(() => {
                openDeleteSelectedEventsModal.value = false;
                clearEventSelection();
            });
    },
    closeMultiEditModal = (moved) => {
        showMultiEditModal.value = false;
        if (moved) {
            // Termine stehen jetzt woanders: Auswahl aufheben (sonst verschiebt ein zweiter Klick doppelt)
            clearEventSelection();
            reloadEvents();
        }
    };

// eventsAtAGlanceRef ist eine Arbeitskopie (Haken, lokales Entfernen) – neue Daten übernehmen
watch(() => props.eventsAtAGlance, (eventsAtAGlance) => {
    eventsAtAGlanceRef.value = JSON.parse(JSON.stringify(eventsAtAGlance ?? []));
    nextTick(observeEventContainers);
});

/**
 * Im Multi-Edit-Modus setzt SingleCalendarEvent per Checkbox event.clicked; ausgewählt ist,
 * was dort angehakt ist (wie getCheckedEvents in IndividualCalendarComponent).
 */
const checkedEventIds = computed(() => {
    const ids = new Set();
    (eventsAtAGlanceRef.value ?? []).forEach((roomData) => {
        Object.values(getDaysFromRoomData(roomData)).forEach((dayData) => {
            (dayData?.events ?? []).forEach((event) => {
                if (event?.clicked) {
                    ids.add(event.id);
                }
            });
        });
    });
    return [...ids];
});

const isSearchingForProject = ref(false);
const toggleSearchingForProject = (isShowingResults) => {
    isSearchingForProject.value = isShowingResults;
};

// Termine werden erst gerendert, wenn ihr Container beobachtet wurde – nach neuen Daten
// (Nachladen nach Verschieben) auch die neuen Container beobachten
let eventContainerObserver = null;

function observeEventContainers() {
    if (!eventContainerObserver) {
        eventContainerObserver = new IntersectionObserver(
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
        );
    }

    document.querySelectorAll('.at-a-glance-event-container').forEach((container) => {
        eventContainerObserver.observe(container);
    });
}

onMounted(() => {
    observeEventContainers();
});

onBeforeUnmount(() => {
    eventContainerObserver?.disconnect();
});
</script>
