<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="sticky top-0 z-40 w-full -mx-5 -my-4">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                :project="project"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
                @update-multi-edit="toggleMultiEdit"
            />
            <div v-if="computedFilteredEvents.length > 0" class="flex justify-center w-full bg-gray-50">
                <div class="flex errorText items-center cursor-pointer my-2"
                     @click="showEventsWithoutRoomComponent = true">
                    <IconAlertTriangle class="h-6 mr-2"/>
                    {{
                        computedFilteredEvents.length === 1 ?
                            $t('{0} Event without room!', [computedFilteredEvents.length]) :
                            $t('{0} Events without room!', [computedFilteredEvents.length])
                    }}
                </div>
            </div>
        </div>
        <div class="-mx-5 mt-4">
            <div :class="project ? 'bg-lightBackgroundGray/50 rounded-t-lg' : 'bg-white px-5'">
                <AsyncCalendarHeader :rooms="rooms" :filtered-events-length="computedFilteredEvents.length"/>
                <div class="divide-y divide-gray-200 divide-dashed events-by-days-container" ref="calendarToCalculate">
                    <div v-for="day in days"
                         :key="day.full_day"
                         :style="{ height: zoom_factor * 115 + 'px' }"
                         class="flex gap-0.5 day-container"
                         :class="day.is_weekend ? 'bg-userBg/30' : ''"
                         :data-day="day.full_day">
                        <SingleDayInCalendar :day="day"/>
                        <div v-for="room in computedCalendarData.value"
                             :key="room.id"
                             :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: zoom_factor * 115 + 'px' }"
                             :class="[day.is_weekend ? '' : '', zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                             class="group/container">
                            <div v-if="composedCurrentDaysInViewRef.has(day.full_day)" v-for="event in room[day.full_day].events">
                                <div class="py-0.5" :key="event.id">
                                    <AsyncSingleEventInCalendar
                                        :event="event"
                                        :multi-edit="multiEdit"
                                        :font-size="textStyle.fontSize"
                                        :line-height="textStyle.lineHeight"
                                        :rooms="rooms"
                                        :has-admin-role="hasAdminRole()"
                                        :width="zoom_factor * 204"
                                        @edit-event="showEditEventModel"
                                        @edit-sub-event="openAddSubEventModal"
                                        @open-add-sub-event-modal="openAddSubEventModal"
                                        @open-confirm-modal="openDeleteEventModal"
                                        @show-decline-event-modal="openDeclineEventModal"
                                        @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="fixed bottom-0 w-full h-28 bg-artwork-navigation-background/30 z-40 pointer-events-none"
         v-if="multiEdit">
        <div class="flex items-center justify-center h-full gap-4">
            <div>
                <FormButton :disabled="computedCheckedEventsForMultiEditCount === 0"
                            @click="showMultiEditModal = true"
                            :text="computedCheckedEventsForMultiEditCount + ' Termin(e) verschieben'"
                            class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div>
                <FormButton
                    class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="openDeleteSelectedEventsModal = true"
                    :disabled="computedCheckedEventsForMultiEditCount === 0"
                    :text="computedCheckedEventsForMultiEditCount + ' ' + $t('Delete events')"/>
            </div>
        </div>
    </div>
    <EventComponent
        v-if="showEventComponent"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="eventToEdit"
        :wantedRoomId="wantedRoom"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        @closed="eventComponentClosed"
    />
    <ConfirmDeleteModal
        v-if="deleteComponentVisible"
        :title="deleteTitle"
        :description="deleteDescription"
        @closed="deleteComponentVisible = false"
        @delete="deleteEvent"/>
    <DeclineEventModal
        v-if="showDeclineEventModal"
        :request-to-decline="declineEvent"
        :event-types="eventTypes"
        @declined="eventDeclined"
        @closed="showDeclineEventModal = false"/>
    <MultiEditModal v-if="showMultiEditModal"
                    :checked-events="editEvents"
                    :rooms="rooms"
                    @closed="closeMultiEditModal"/>
    <ConfirmDeleteModal
        v-if="openDeleteSelectedEventsModal"
        :title="$t('Delete assignments')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
        @closed="closeDeleteSelectedEventsModal"
        @delete="deleteSelectedEvents"/>

    <AddSubEventModal
        v-if="showAddSubEventModal"
        :event="eventToEdit"
        :event-types="eventTypes"
        :sub-event-to-edit="subEventToEdit"
        @close="closeAddSubEventModal"/>
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="showEventsWithoutRoomComponent = false"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="computedFilteredEvents"
        :isAdmin="hasAdminRole()"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        @desires-reload="handleReload"
    />
    <div v-if="showReceivesNewDataOverlay"
         class="bg-opacity-50 bg-black text-white w-full h-full absolute inset-0 z-50 justify-center text-center">
        <div class="flex flex-col w-full h-full justify-center">
            {{ $t('Your view is being processed, please wait a moment.') }}
        </div>
    </div>
</template>

<script setup>
import {computed, defineAsyncComponent, inject, onMounted, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import {usePermission} from "@/Composeables/Permission.js";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {IconAlertTriangle} from "@tabler/icons-vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import AddSubEventModal from "@/Layouts/Components/AddSubEventModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {useEvent} from "@/Composeables/Event.js";
import {useDaysAndEventsIntersectionObserver} from "@/Composeables/IntersectionObserver.js";

const props = defineProps({
        rooms: {
            type: Object,
            required: true,
        },
        days: {
            type: Array,
            required: true,
        },
        calendarData: {
            type: Object,
            required: true,
        },
        project: {
            type: Object,
            default: null,
            required: false,
        },
        eventsWithoutRoom: {
            type: Object,
            required: false,
        },
    }),
    $t = useTranslation(),
    {getDaysOfEvent, formatEventDateByDayJs, useCalendarReload} = useEvent(),
    {
        showReceivesNewDataOverlay,
        hasReceivedNewCalendarData,
        hasReceivedNewEventsWithoutRoomData,
        receivedRoomData,
        receivedEventsWithoutRoom,
        handleReload
    } = useCalendarReload(props.project ? props.project.id : 0),
    {
        composedCurrentDaysInViewRef,
        composedStartDaysAndEventsIntersectionObserving
    } = useDaysAndEventsIntersectionObserver(),
    {hasAdminRole} = usePermission(usePage().props),
    AsyncFunctionBarCalendar = defineAsyncComponent(
        {
            loader: () => import('@/Components/FunctionBars/FunctionBarCalendar.vue')
        }
    ),
    AsyncCalendarHeader = defineAsyncComponent(
        {
            loader: () => import('@/Components/Calendar/Elements/CalendarHeader.vue')
        }
    ),
    AsyncSingleEventInCalendar = defineAsyncComponent(
        {
            loader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue')
        }
    ),
    textStyle = computed(() => {
        const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
        const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
        return {
            fontSize,
            lineHeight,
        };
    }),
    computedCalendarData = computed(() => {
        if (!hasReceivedNewCalendarData.value) {
            return calendarDataRef;
        }

        for (const [day, rooms] of Object.entries(receivedRoomData.value)) {
            for (const [roomId, events] of Object.entries(rooms)) {
                calendarDataRef.value.forEach(
                    (roomWithEvents) => {
                        if (roomWithEvents[day]?.roomId === Number(roomId)) {
                            roomWithEvents[day].events = JSON.parse(JSON.stringify(events));
                        }
                    }
                );
            }
        }

        hasReceivedNewCalendarData.value = false;
        receivedRoomData.value = [];

        if (showReceivesNewDataOverlay.value) {
            showReceivesNewDataOverlay.value = false;
        }

        return calendarDataRef;
    }),
    computedFilteredEvents = computed(() => {
        let getComputedEventsWithoutRoom = () => {
            return eventsWithoutRoomRef.value.filter((event) => {
                let createdBy = event.created_by;
                let projectLeaders = event.projectLeaders;

                if (projectLeaders && projectLeaders.length > 0) {
                    if (createdBy.id === usePage().props.user.id || projectLeaders.some((leader) => leader.id === usePage().props.user.id)) {
                        return true;
                    }
                } else if (createdBy.id === usePage().props.user.id) {
                    return true;
                }

                return false;
            });
        }

        if (!hasReceivedNewEventsWithoutRoomData.value) {
            return getComputedEventsWithoutRoom();
        }

        eventsWithoutRoomRef.value = receivedEventsWithoutRoom.value;

        receivedEventsWithoutRoom.value = [];
        hasReceivedNewEventsWithoutRoomData.value = false;

        return getComputedEventsWithoutRoom();
    }),
    computedCheckedEventsForMultiEditCount = computed(() => {
        return editEvents.value.length;
    }),
    calendarDataRef = ref(JSON.parse(JSON.stringify(props.calendarData))),
    eventsWithoutRoomRef = ref(JSON.parse(JSON.stringify(props.eventsWithoutRoom ?? []))),
    first_project_calendar_tab_id = inject('first_project_calendar_tab_id'),
    eventTypes = inject('eventTypes'),
    multiEdit = ref(false),
    isFullscreen = ref(false),
    zoom_factor = ref(usePage().props.user.zoom_factor ?? 1),
    showMultiEditModal = ref(false),
    editEvents = ref([]),
    editEventsRoomIds = ref([]),
    editEventsRoomsDesiredDays = ref([]),
    openDeleteSelectedEventsModal = ref(false),
    showEventsWithoutRoomComponent = ref(false),
    showAddSubEventModal = ref(false),
    showDeclineEventModal = ref(false),
    showEventComponent = ref(false),
    deleteComponentVisible = ref(false),
    deleteTitle = ref(''),
    deleteDescription = ref(''),
    deleteType = ref(''),
    eventToEdit = ref(null),
    subEventToEdit = ref(null),
    declineEvent = ref(null),
    eventToDelete = ref(null),
    wantedRoom = ref(null),
    roomCollisions = ref([]),
    handleMultiEditEventCheckboxChange = (eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd) => {
        if (considerOnMultiEdit) {
            editEvents.value.push(eventId);
            editEventsRoomIds.value.push(eventRoomId);
            editEventsRoomsDesiredDays.value = getDaysOfEvent(
                formatEventDateByDayJs(eventStart),
                formatEventDateByDayJs(eventEnd)
            ).concat(editEventsRoomsDesiredDays.value);

            return;
        }

        editEvents.value = editEvents.value.filter((editEventId) => editEventId !== eventId);
        editEventsRoomIds.value = editEventsRoomIds.value.filter((editEventRoomId) => editEventRoomId !== eventRoomId);
        editEventsRoomsDesiredDays.value = editEventsRoomsDesiredDays.value.filter(
            (editEventDesiredDay) => {
                return getDaysOfEvent(
                    formatEventDateByDayJs(eventStart),
                    formatEventDateByDayJs(eventEnd)
                ).includes(editEventDesiredDay);
            }
        );
    },
    resetMultiEdit = () => {
        editEvents.value = [];
        editEventsRoomIds.value = [];
        editEventsRoomsDesiredDays.value = [];
        toggleMultiEdit(false);
    },
    toggleMultiEdit = (value) => {
        multiEdit.value = value;

        if (!value) {
            calendarDataRef.value.forEach(
                (calendarData) => {
                    Object.values(calendarData)
                        .forEach(
                            (roomEvents) => {
                                roomEvents.events.forEach((roomEvent) => roomEvent.considerOnMultiEdit = false);
                            }
                        );
                }
            );
        }
    },
    openDeclineEventModal = (event) => {
        declineEvent.value = event;
        showDeclineEventModal.value = true;
    },
    openDeleteEventModal = (event, type) => {
        if (type === 'main') {
            deleteType.value = type;
            deleteTitle.value = $t('Delete event?');
            deleteDescription.value = $t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.');
        } else {
            deleteType.value = type;
            deleteTitle.value = $t('Delete sub-event?');
            deleteDescription.value = $t('Are you sure you want to delete the selected assignments?');
        }
        eventToDelete.value = event;
        deleteComponentVisible.value = true;
    },
    openAddSubEventModal = (desiredEvent, mode, mainEvent) => {
        if (mode === 'create') {
            //only set eventToEdit as base for new sub event
            eventToEdit.value = desiredEvent;
        } else if (mode === 'edit') {
            //only set eventToEdit as base for new sub event
            eventToEdit.value = mainEvent;
            subEventToEdit.value = desiredEvent;
        }

        showAddSubEventModal.value = true;
    },
    closeAddSubEventModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        if (closedOnPurpose) {
            handleReload(
                desiredRoomIds,
                desiredDays
            );
        }

        showAddSubEventModal.value = false;
        eventToEdit.value = null;
        subEventToEdit.value = null;
    },
    showEditEventModel = (event) => {
        eventToEdit.value = event;
        showEventComponent.value = true;
    },
    openFullscreen = () => {
        let elem = document.getElementById('myCalendar');

        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) { /* Safari */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE11 */
            elem.msRequestFullscreen();
        }

        isFullscreen.value = true;
    },
    closeMultiEditModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        showMultiEditModal.value = false;
        if (closedOnPurpose) {
            if (desiredRoomIds && desiredDays) {
                handleReload(desiredRoomIds, desiredDays);
            }

            resetMultiEdit();
        }
    },
    eventComponentClosed = (closedOnPurpose, desiredRoomIdsToReload, desiredDaysToReload) => {
        if (closedOnPurpose) {
            handleReload(
                desiredRoomIdsToReload,
                desiredDaysToReload
            );
        }

        showEventComponent.value = false;
        return true;
    },
    eventDeclined = (desiredRoomIdToReload, startDate, endDate) => {
        handleReload(
            [
                desiredRoomIdToReload
            ],
            getDaysOfEvent(
                formatEventDateByDayJs(startDate),
                formatEventDateByDayJs(endDate)
            ),
            true
        );
    },
    deleteEvent = () => {
        const onSuccess = () => {
            handleReload(
                [
                    eventToDelete.value.roomId
                ],
                getDaysOfEvent(
                    formatEventDateByDayJs(eventToDelete.value.start),
                    formatEventDateByDayJs(eventToDelete.value.end)
                )
            );
        }
        if (deleteType.value === 'main') {
            axios.delete(route('events.delete', eventToDelete.value)).finally(() => onSuccess());
        }
        if (deleteType.value === 'sub') {
            axios.delete(route('subEvent.delete', eventToDelete.value)).finally(() => onSuccess());
        }
        deleteComponentVisible.value = false;
    },
    closeDeleteSelectedEventsModal = (closedOnPurpose) => {
        openDeleteSelectedEventsModal.value = false;

        if (closedOnPurpose) {
            resetMultiEdit();
        }
    },
    deleteSelectedEvents = () => {
        axios.post(
            route('multi-edit.delete'),
            {
                events: editEvents.value
            }
        ).finally(
            () => {
                handleReload(
                    Array.from(new Set(editEventsRoomIds.value)),
                    Array.from(new Set(editEventsRoomsDesiredDays.value))
                );

                openDeleteSelectedEventsModal.value = false;
                resetMultiEdit();
            }
        );
    };

onMounted(() => {
    window.addEventListener(
        "fullscreenchange",
        () => {
            if (!document.fullscreenElement) {
                isFullscreen.value = false;
            }
        }
    );

    composedStartDaysAndEventsIntersectionObserving();
});
</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




