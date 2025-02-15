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
                @jump-to-day-of-month="jumpToDayOfMonth"
            />
        </div>


        <div :class="eventsWithoutRoom.length > 0 ? 'mt-20' : ''">
            <div v-if="eventsWithoutRoom.length > 0" class="flex justify-center">
                <div class="flex errorText items-center cursor-pointer my-2" @click="showEventsWithoutRoomComponent = true">
                    <IconAlertTriangle class="h-6 mr-2"/>
                    {{
                        computedFilteredEvents.length === 1 ?
                            $t('{0} Event without room!', [eventsWithoutRoom.length]) :
                            $t('{0} Events without room!', [eventsWithoutRoom.length])
                    }}
                </div>
            </div>
        </div>

        <div>
            <div v-if="!usePage().props.user.daily_view">
                <div class="-mx-5 mt-4">
                    <div :class="project ? 'bg-lightBackgroundGray/50 rounded-t-lg' : 'bg-white px-5'">
                        <AsyncCalendarHeader :rooms="rooms" :filtered-events-length="computedFilteredEvents.length"/>
                        <div class="w-fit events-by-days-container" :class="[!project ? 'pt-8' : '', isFullscreen ? 'mt-4': '', computedFilteredEvents.length > 0 ? '-mt-7' : 'mt-4' ]" ref="calendarToCalculate">
                            <div v-for="day in days"
                                 :key="day.full_day"
                                 :style="{ height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px' }"
                                 class="flex gap-0.5 day-container"
                                 :class="day.is_weekend ? 'bg-userBg/70' : ''"
                                 :data-day="day.full_day"
                                 :data-day-to-jump="day.without_format">
                                <SingleDayInCalendar :isFullscreen="isFullscreen" :day="day" v-if="!day.is_extra_row"/>
                                <div v-for="room in newCalendarData"
                                     :key="room.id"
                                     class="relative"
                                     v-if="!day.is_extra_row">
                                    <div v-if="room.content[day.full_day]?.events.length > 1 && !usePage().props.user.calendar_settings.expand_days" class="absolute bottom-2 right-4 z-10">
                                        <component is="IconChevronDown" @click="scrollToNextEventInDay(day.without_format, room.content[day.full_day].events.length)" class="h-6 w-6 text-gray-400 text-hover cursor-pointer" stroke-width="2"/>
                                    </div>
                                    <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px' }"
                                         :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                                         class="group/container border-t border-gray-300 border-dashed" :id="'scroll_container-' + day.without_format">
                                        <div v-if="composedCurrentDaysInViewRef.has(day.full_day)" v-for="(event, index) in room.content[day.full_day].events">
                                            <div class="py-0.5" :key="event.id" :id="'event_scroll-' + index + '-day-' + day.without_format">
                                                <AsyncSingleEventInCalendar
                                                    :event="event"
                                                    :multi-edit="multiEdit"
                                                    :font-size="textStyle.fontSize"
                                                    :line-height="textStyle.lineHeight"
                                                    :rooms="rooms"
                                                    :has-admin-role="hasAdminRole()"
                                                    :width="zoom_factor * 196"
                                                    :first_project_tab_id="first_project_tab_id"
                                                    :firstProjectShiftTabId="firstProjectShiftTabId"
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
            </div>
            <div v-else>
                <DailyViewCalendar
                    :multi-edit="multiEdit"
                    :rooms="rooms"
                    :days="days"
                    :calendarData="newCalendarData"
                    :project="project"
                    :eventStatuses="eventStatuses"
                    :eventTypes="eventTypes"
                    :eventsWithoutRoom="computedFilteredEvents"
                    :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                    :firstProjectShiftTabId="firstProjectShiftTabId"
                    :first-project-tab-id="first_project_tab_id"
                    @edit-event="showEditEventModel"
                    @edit-sub-event="openAddSubEventModal"
                    @open-add-sub-event-modal="openAddSubEventModal"
                    @open-confirm-modal="openDeleteEventModal"
                    @show-decline-event-modal="openDeclineEventModal"
                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                />
            </div>
        </div>
        <div v-if="!checkIfAnyRoomHasAnEventOrShift && usePage().props.user.calendar_settings.use_project_time_period">
            <div class="mt-24 ml-4">
                <div class="border-l-4 border-red-400 bg-red-50 p-4 w-fit">
                    <div class="flex">
                        <div class="shrink-0">
                            <component is="IconExclamationCircle" class="h-5 w-5 text-red-400" stroke-width="2" aria-hidden="true" />
                        </div>
                        <div class="ml-1">
                            <p class="text-sm text-red-700 font-bold">
                                {{ $t('The selected project has no dates') }}
                            </p>
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
        :event-statuses="eventStatuses"
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
        :eventsWithoutRoom="eventsWithoutRoom"
        :isAdmin="hasAdminRole()"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
    />

</template>

<script setup>
/* Comment: below very important unused import new Date().format relies on it - do not remove otherwise ui breaks */
import VueCal from 'vue-cal';
/* Comment: above very important unused import new Date().format relies on it - do not remove otherwise ui breaks */
import {computed, defineAsyncComponent, inject, onMounted, provide, ref} from "vue";
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
import {useDaysAndEventsIntersectionObserver} from "@/Composeables/IntersectionObserver.js";
import MultiDuplicateModal from "@/Layouts/Components/MultiDuplicateModal.vue";
import DailyViewCalendar from "@/Components/Calendar/DailyViewCalendar.vue";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import {computeComponentStructure} from "vuedraggable/src/core/renderHelper.js";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";

const filterOptions = inject('filterOptions');
const personalFilters = inject('personalFilters');
const user_filters = inject('user_filters');
const event_properties = inject('event_properties');
//provide('event_properties', inject('event_properties'));

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
        },
        firstProjectShiftTabId: {
            type: [String, Number],
            required: false,
            default: null
        },
        eventStatuses: {
            type: Object,
            required: false,
            default: null
        }
    })
const $t = useTranslation()
const { composedCurrentDaysInViewRef, composedStartDaysAndEventsIntersectionObserving} = useDaysAndEventsIntersectionObserver()
const {hasAdminRole} = usePermission(usePage().props)

const AsyncFunctionBarCalendar = defineAsyncComponent(
        {
            loader: () => import('@/Components/FunctionBars/FunctionBarCalendar.vue')
        }
    )

const AsyncCalendarHeader = defineAsyncComponent(
        {
            loader: () => import('@/Components/Calendar/Elements/CalendarHeader.vue')
        }
    )
const AsyncSingleEventInCalendar = defineAsyncComponent(
        {
            loader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue')
        }
    )
const textStyle = computed(() => {
        const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
        const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
        return {
            fontSize,
            lineHeight,
        };
    })
const scrollToNextEventInDay = (day, length) => {
        let eventScroll = document.getElementById('event_scroll-' + (length - 1) + '-day-' + day);
        if (eventScroll) {
            eventScroll.scrollIntoView({
                behavior: 'smooth', // Optionale Animation für weiches Scrollen
                block: 'nearest',  // Scrolle nur so weit wie nötig
                inline: 'nearest' // Stelle sicher, dass es nicht die ganze Seite beeinflusst
            });
        }
    };
const computedFilteredEvents = computed(() => {
        let getComputedEventsWithoutRoom = () => {
            return eventsWithoutRoomRef.value.filter((event) => {
                let createdBy = event.created_by;
                let projectLeaders = event.projectLeaders;

                if (projectLeaders && projectLeaders.length > 0) {
                    if (
                        createdBy.id === usePage().props.user.id ||
                        projectLeaders.some((leader) => leader.id === usePage().props.user.id)
                    ) {
                        return true;
                    }
                } else if (createdBy.id === usePage().props.user.id) {
                    return true;
                }

                return false;
            });
        }

        return getComputedEventsWithoutRoom();
    });
const computedCheckedEventsForMultiEditCount = computed(() => {
        return editEvents.value.length;
    });
const eventsWithoutRoomRef = ref(JSON.parse(JSON.stringify(props.eventsWithoutRoom ?? [])));
const first_project_calendar_tab_id = inject('first_project_calendar_tab_id');
const first_project_tab_id = inject('first_project_tab_id');
const eventTypes = inject('eventTypes');
const multiEdit = ref(false);
const isFullscreen = ref(false);
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const showMultiEditModal = ref(false);
const editEvents = ref([]);
const editEventsRoomIds = ref([]);
const editEventsRoomsDesiredDays = ref([]);
const openDeleteSelectedEventsModal = ref(false);
const showEventsWithoutRoomComponent = ref(false);
const showAddSubEventModal = ref(false);
const showDeclineEventModal = ref(false);
const showEventComponent = ref(false);
const deleteComponentVisible = ref(false);
const deleteTitle = ref('');
const deleteDescription = ref('');
const deleteType = ref('');
const eventToEdit = ref(null);
const subEventToEdit = ref(null);
const declineEvent = ref(null);
const eventToDelete = ref(null);
const wantedRoom = ref(null);
const roomCollisions = ref([]);
const showMultiDuplicateModal = ref(false);
const checkIfScrolledToCalendarRef = ref('!-ml-3');
const newCalendarData = ref(props.calendarData);
const handleMultiEditEventCheckboxChange = (eventId, considerOnMultiEdit, eventRoomId) => {
    if (considerOnMultiEdit) {
        editEvents.value.push(eventId);

        newCalendarData.value.forEach(room => {
            Object.entries(room.content).forEach(([date, { events }]) => {
                events.forEach(event => {
                    if (event.id === eventId) {
                        event.considerOnMultiEdit = true;
                    }
                });
            });
        });

        return;
    }

    newCalendarData.value.forEach(room => {
        Object.entries(room.content).forEach(([date, { events }]) => {
            events.forEach(event => {
                if (event.id === eventId) {
                    event.considerOnMultiEdit = false;
                }
            });
        });
    });

    editEvents.value = editEvents.value.filter(editEventId => editEventId !== eventId);
    editEventsRoomIds.value = editEventsRoomIds.value.filter(editEventRoomId => editEventRoomId !== eventRoomId);
};
const resetMultiEdit = () => {
    editEvents.value = [];
    editEventsRoomIds.value = [];
    editEventsRoomsDesiredDays.value = [];
    toggleMultiEdit(false);
};

const checkIfAnyRoomHasAnEventOrShift = computed(() => {
    // Prüfen, ob irgendein Raum ein Event oder eine Schicht im aktuellen Kalender hat
    return newCalendarData.value.some(room => {
        return room.content && Object.entries(room.content).some(([date, { events }]) => {
            return events && events.length > 0;
        });
    });
});

const toggleMultiEdit = (value) => {
    multiEdit.value = value;

    if (!value) {
        newCalendarData.value.forEach(room => {
            Object.entries(room.content).forEach(([date, { events }]) => {
                events.forEach(event => {
                    event.considerOnMultiEdit = false;
                });
            });
        });
    }
};
const cancelMultiEditDuplicateSelection = () => {
    editEvents.value = [];
    editEventsRoomIds.value = [];
    editEventsRoomsDesiredDays.value = [];

    newCalendarData.value.forEach(room => {
        Object.entries(room.content).forEach(([date, { events }]) => {
            events.forEach(event => {
                event.considerOnMultiEdit = false;
            });
        });
    });
};
const openDeclineEventModal = (event) => {
        declineEvent.value = event;
        showDeclineEventModal.value = true;
    }
const openDeleteEventModal = (event, type) => {
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
    }
const openAddSubEventModal = (desiredEvent, mode, mainEvent) => {
        if (mode === 'create') {
            //only set eventToEdit as base for new sub event
            eventToEdit.value = desiredEvent;
        } else if (mode === 'edit') {
            //only set eventToEdit as base for new sub event
            eventToEdit.value = mainEvent;
            subEventToEdit.value = desiredEvent;
        }

        showAddSubEventModal.value = true;
    }
const closeAddSubEventModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        if (closedOnPurpose) {
        }

        showAddSubEventModal.value = false;
        eventToEdit.value = null;
        subEventToEdit.value = null;
    }
const showEditEventModel = (event) => {
        eventToEdit.value = event;
        showEventComponent.value = true;
    }
const openFullscreen = () => {
        let elem = document.getElementById('myCalendar');

        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) { /* Safari */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE11 */
            elem.msRequestFullscreen();
        }

        isFullscreen.value = true;
    }
const closeMultiEditModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        showMultiEditModal.value = false;
        if (closedOnPurpose) {
            resetMultiEdit();
        }
    }
const closeMultiDuplicateModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        showMultiDuplicateModal.value = false;
        if (closedOnPurpose) {
            resetMultiEdit();
        }
    }
const eventComponentClosed = (closedOnPurpose) => {
        if (closedOnPurpose) {
            let calendar_settings = usePage().props.user.calendar_settings;

            //@todo: temporary see ARTWORK-300
            if (calendar_settings.use_project_time_period) {
                router.patch(
                    route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
                    {
                        use_project_time_period: true,
                        project_id: calendar_settings.time_period_project_id,
                    },
                    {
                        preserveState: false,
                        preserveScroll: true
                    }
                );
                return;
            }
        }

        showEventComponent.value = false;
    }
const deleteEvent = () => {
        if (deleteType.value === 'main') {
            axios.delete(route('events.delete', eventToDelete.value));
        }
        if (deleteType.value === 'sub') {
            axios.delete(route('subEvent.delete', eventToDelete.value));
        }
        deleteComponentVisible.value = false;
    }
const closeDeleteSelectedEventsModal = (closedOnPurpose) => {
        openDeleteSelectedEventsModal.value = false;

        if (closedOnPurpose) {
            resetMultiEdit();
        }
    }
const deleteSelectedEvents = () => {
        axios.post(
            route('multi-edit.delete'),
            {
                events: editEvents.value
            }
        ).finally(
            () => {

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
        //null check if someone switches to at a glance calendar, dom element is removed
        //but listener is still registered
        if (document.getElementById('myCalendar')?.getBoundingClientRect().top < 0) {
            checkIfScrolledToCalendarRef.value = 'fixed !-ml-2';
        } else {
            checkIfScrolledToCalendarRef.value = '';
        }
    });
});


const jumpToDayOfMonth = (day) => {
    const dayElement = document.querySelector(`.day-container[data-day-to-jump="${day}"]`);
    if (dayElement) {
        // scroll to the day with puffer for the header
        window.scrollTo({
            top: dayElement.offsetTop - 130,
            behavior: 'smooth',
        });
    }
}

onMounted(() => {
    const ShiftCalendarListener = useShiftCalendarListener(newCalendarData.value);
    ShiftCalendarListener.init();
})

</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




