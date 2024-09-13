<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class=" w-full top-0 left-4 py-4  z-40 -mx-10 -my-4" :class="project ? [checkIfScrolledToCalendarRef] : isFullscreen ? 'fixed' : 'fixed ml-10'">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                :project="project"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
                @update-multi-edit="toggleMultiEdit"
            />
        </div>
        <div :class="computedFilteredEvents.length > 0 ? 'mt-20' : ''">
            <div v-if="computedFilteredEvents.length > 0" class="flex justify-center">
                <div class="flex errorText items-center cursor-pointer my-2" @click="showEventsWithoutRoomComponent = true">
                    <IconAlertTriangle class="h-6 mr-2"/>
                    {{
                        computedFilteredEvents.length === 1 ?
                            $t('{0} Event without room!', [computedFilteredEvents.length]) :
                            $t('{0} Events without room!', [computedFilteredEvents.length])
                    }}
                </div>
            </div>

            <!--<div class="w-full overflow-y-scroll hidden" >
                <div class="mb-1 ml-4 max-w-7xl">
                    <div class="flex">
                        <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter" @removeFilter="removeFilter"/>
                    </div>
                </div>
            </div>-->
        </div>
        <div v-if="!dateValue[0] && !dateValue[1]" class="mt-24 ml-4 text-error text-sm">
            {{ $t('The selected project has no dates') }}
        </div>
        <div v-else class="-mx-5 mt-4">
            <div :class="project ? 'bg-lightBackgroundGray/50 rounded-t-lg' : 'bg-white px-5'">
                <AsyncCalendarHeader :rooms="rooms" :filtered-events-length="computedFilteredEvents.length"/>
                <div class="w-fit events-by-days-container" :class="[!project ? computedFilteredEvents.length > 0 || activeFilters.length > 0 ? 'pt-1' : 'pt-8' : '', isFullscreen ? 'mt-6': '', computedFilteredEvents.length > 0 ? '-mt-1' : 'mt-4' ]" ref="calendarToCalculate">
                    <div v-for="day in days"
                         :key="day.full_day"
                         :style="{ height: zoom_factor * 115 + 'px' }"
                         class="flex gap-0.5 day-container"
                         :class="day.is_weekend ? 'bg-userBg/70' : ''"
                         :data-day="day.full_day">
                        <SingleDayInCalendar :isFullscreen="isFullscreen" :day="day" />
                        <div v-for="room in computedCalendarData.value"
                             :key="room.id"
                             :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: zoom_factor * 115 + 'px' }"
                             :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                             class="group/container border-b-2 border-gray-400 border-dashed">
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
                                        :first_project_tab_id="first_project_tab_id"
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

    <div class="fixed bottom-0 w-full h-32 bg-artwork-navigation-background/30 z-40 pointer-events-none" v-if="multiEdit">
        <div class="flex items-center justify-center h-full gap-4">
            <div>
                <FormButton :disabled="computedCheckedEventsForMultiEditCount === 0"
                            @click="showMultiEditModal = true"
                            :text="computedCheckedEventsForMultiEditCount + ' Termin(e) verschieben'"
                            class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div>
                <FormButton
                    class="transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="showMultiDuplicateModal = true"
                    :disabled="computedCheckedEventsForMultiEditCount === 0"
                    :text="computedCheckedEventsForMultiEditCount + ' ' + $t('Duplicate events')"/>
            </div>
            <div>
                <FormButton
                    class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="openDeleteSelectedEventsModal = true"
                    :disabled="computedCheckedEventsForMultiEditCount === 0"
                    :text="computedCheckedEventsForMultiEditCount + ' ' + $t('Delete events')"/>
            </div>



            <div>
                <FormButton
                    class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="cancelMultiEditDuplicateSelection"
                    :disabled="computedCheckedEventsForMultiEditCount === 0"
                    :text="$t('Cancel selection')"/>
            </div>
        </div>
    </div>

    <EventComponent
        v-if="showEventComponent"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :calendarProjectPeriod="usePage().props.user.calendar_settings.use_project_time_period"
        :project="project"
        :event="eventToEdit"
        :wantedRoomId="wantedRoom"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :requires-axios-requests="true"
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

    <MultiDuplicateModal v-if="showMultiDuplicateModal"
                    :checked-events="editEvents"
                    :rooms="rooms"
                    @closed="closeMultiDuplicateModal"/>

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
import {router, usePage} from "@inertiajs/vue3";
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
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import MultiDuplicateModal from "@/Layouts/Components/MultiDuplicateModal.vue";

const filterOptions = inject('filterOptions');
const personalFilters = inject('personalFilters');
const user_filters = inject('user_filters');

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
        projectNameUsedForProjectTimePeriod: {
            type: String,
            required: false,
            default: ''
        }
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
    first_project_tab_id = inject('first_project_tab_id'),
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
    showMultiDuplicateModal = ref(false),
    checkIfScrolledToCalendarRef = ref('!-ml-3'),
    handleMultiEditEventCheckboxChange = (eventId, considerOnMultiEdit, eventRoomId, eventStart, eventEnd) => {
        if (considerOnMultiEdit) {
            editEvents.value.push(eventId);

            for (const [day, rooms] of Object.entries(calendarDataRef.value)) {
                for (const [roomId, events] of Object.entries(rooms)) {
                    events.events.forEach((event) => {
                        if (eventId === event.id) {
                            event.considerOnMultiEdit = true;
                        }
                    });
                }
            }

            editEventsRoomIds.value.push(eventRoomId);
            editEventsRoomsDesiredDays.value = getDaysOfEvent(
                formatEventDateByDayJs(eventStart),
                formatEventDateByDayJs(eventEnd)
            ).concat(editEventsRoomsDesiredDays.value);

            return;
        }

        for (const [day, rooms] of Object.entries(calendarDataRef.value)) {
            for (const [roomId, events] of Object.entries(rooms)) {
                events.events.forEach((event) => {
                    if (eventId === event.id) {
                        event.considerOnMultiEdit = false;
                    }
                });
            }
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
    cancelMultiEditDuplicateSelection = () => {
        editEvents.value = [];
        editEventsRoomIds.value = [];
        editEventsRoomsDesiredDays.value = [];
        calendarDataRef.value.forEach(
            (calendarData) => {
                Object.values(calendarData)
                    .forEach(
                        (roomEvents) => {
                            roomEvents.events.forEach((roomEvent) => roomEvent.considerOnMultiEdit = false);
                        }
                    );
            }
        )
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
    closeMultiDuplicateModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        showMultiDuplicateModal.value = false;
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


const dateValue = inject('dateValue');


const activeFilters = computed(() => {
    let activeFiltersArray = []
    filterOptions.rooms.forEach((room) => {
        if (user_filters.rooms?.includes(room.id)) {
            activeFiltersArray.push(room)
        }
    })

    filterOptions.areas.forEach((area) => {
        if (user_filters.areas?.includes(area.id)) {
            activeFiltersArray.push(area)
        }
    })

    filterOptions.eventTypes.forEach((eventType) => {
        if (user_filters.event_types?.includes(eventType.id)) {
            activeFiltersArray.push(eventType)
        }
    })

    filterOptions.roomCategories.forEach((category) => {
        if (user_filters.room_categories?.includes(category.id)) {
            activeFiltersArray.push(category)
        }
    })

    filterOptions.roomAttributes.forEach((attribute) => {
        if (user_filters.room_attributes?.includes(attribute.id)) {
            activeFiltersArray.push(attribute)
        }
    })

    if (user_filters.is_loud) {
        activeFiltersArray.push({name: "Laute Termine", value: 'isLoud', user_filter_key: 'is_loud'})
    }

    if (user_filters.is_not_loud) {
        activeFiltersArray.push({name: "Ohne laute Termine", value: 'isNotLoud', user_filter_key: 'is_not_loud'})
    }

    if (user_filters.adjoining_no_audience) {
        activeFiltersArray.push({
            name: "Ohne Nebenveranstaltung mit Publikum",
            value: 'adjoiningNoAudience',
            user_filter_key: 'adjoining_no_audience'
        })
    }

    if (user_filters.adjoining_not_loud) {
        activeFiltersArray.push({
            name: "Ohne laute Nebenveranstaltung",
            value: 'adjoiningNotLoud',
            user_filter_key: 'adjoining_not_loud'
        })
    }

    if (user_filters.has_audience) {
        activeFiltersArray.push({name: "Mit Publikum", value: 'hasAudience', user_filter_key: 'has_audience'})
    }

    if (user_filters.has_no_audience) {
        activeFiltersArray.push({name: "Ohne Publikum", value: 'hasNoAudience', user_filter_key: 'has_no_audience'})
    }

    if (user_filters.show_adjoining_rooms) {
        activeFiltersArray.push({
            name: "Nebenräume anzeigen",
            value: 'showAdjoiningRooms',
            user_filter_key: 'show_adjoining_rooms'
        })
    }

    return activeFiltersArray
})

const removeFilter = (filter) => {
    if (filter.value === 'isLoud') {
        updateFilterValue('is_loud', false);
    }

    if (filter.value === 'isNotLoud') {
        updateFilterValue('is_not_loud', false)
    }

    if (filter.value === 'adjoiningNoAudience') {
        updateFilterValue('adjoining_no_audience', false)
    }

    if (filter.value === 'adjoiningNotLoud') {
        updateFilterValue('adjoining_not_loud', false)
    }

    if (filter.value === 'hasAudience') {
        updateFilterValue('has_audience', false)
    }

    if (filter.value === 'hasNoAudience') {
        updateFilterValue('has_no_audience', false)
    }

    if (filter.value === 'showAdjoiningRooms') {
        updateFilterValue('show_adjoining_rooms', false)
    }

    if (filter.value === 'rooms') {
        user_filters.rooms.splice(user_filters.rooms.indexOf(filter.id), 1);
        updateFilterValue('rooms', user_filters.rooms.length > 0 ? user_filters.rooms : null)
    }

    if (filter.value === 'room_categories') {
        user_filters.room_categories.splice(user_filters.room_categories.indexOf(filter.id), 1);
        updateFilterValue('room_categories', user_filters.room_categories.length > 0 ? user_filters.room_categories : null)
    }

    if (filter.value === 'areas') {
        user_filters.areas.splice(user_filters.areas.indexOf(filter.id), 1);
        updateFilterValue('areas', user_filters.areas.length > 0 ? user_filters.areas : null)
    }

    if (filter.value === 'event_types') {
        user_filters.event_types.splice(user_filters.event_types.indexOf(filter.id), 1);
        updateFilterValue('event_types', user_filters.event_types.length > 0 ? user_filters.event_types : null)
    }

    if (filter.value === 'room_attributes') {
        user_filters.room_attributes.splice(user_filters.room_attributes.indexOf(filter.id), 1);
        updateFilterValue('room_attributes', user_filters.room_attributes.length > 0 ? user_filters.room_attributes : null)
    }
}

const updateFilterValue = (key, value) => {
    router.patch(route('user.calendar.filter.single.value.update', {user: usePage().props.user.id}), {
        key: key,
        value: value
    }, {
        preserveScroll: true,
        preserveState: false
    });
}

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

    // add a watch or something to check if the user is scrolled down to CalendarRef if yes add to checkIfScrolledToCalendarRef = fixed

    window.addEventListener('scroll', () => {
        if (document.getElementById('myCalendar').getBoundingClientRect().top < 0) {
            checkIfScrolledToCalendarRef.value = 'fixed !-ml-2';
        } else {
            checkIfScrolledToCalendarRef.value = '';
        }
    });

});


</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




