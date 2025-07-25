<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white">
        <div class="w-full left-8 top-0 px-5 fixed z-40" >
            <FunctionBarCalendar
                :multi-edit="multiEdit"
                :project="project"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
                @update-multi-edit="toggleMultiEdit"
                @jump-to-day-of-month="jumpToDayOfMonth"
                :is-planning="isPlanning"
            >
            </FunctionBarCalendar>
            <div class="w-full h-8 px-4 py-2 bg-red-500 cursor-pointer" v-if="eventsWithoutRoom.length > 0" @click="showEventsWithoutRoomComponent = true">
                <div class="flex items-center justify-center w-full h-full gap-x-1">
                    <component is="IconAlertTriangle" class="size-4 text-white" aria-hidden="true" />
                    <div class="text-white text-sm font-bold">
                        {{
                            eventsWithoutRoom.length === 1 ?
                                $t('{0} Event without room!', [eventsWithoutRoom.length]) :
                                $t('{0} Events without room!', [eventsWithoutRoom.length])
                        }}
                    </div>
                </div>
            </div>
        </div>
        <div class="pt-20">
            <div v-if="!usePage().props.auth.user.daily_view && !usePage().props.auth.user.at_a_glance">
                <div class="w-max -ml-3" :class="eventsWithoutRoom.length > 0 ? '' : ''">
                    <div :class="project ? 'bg-lightBackgroundGray/50' : 'bg-white'">
                        <CalendarHeader :rooms="rooms" :filtered-events-length="eventsWithoutRoom.length"/>
                        <div class="w-fit events-by-days-container" :class="[!project ? '' : '', isFullscreen ? 'mt-4': '',]" ref="calendarToCalculate">
                            <div v-for="day in days"
                                 :key="day.fullDay"
                                 :style="{ height: usePage().props.auth.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px', minHeight: usePage().props.auth.user.calendar_settings.expand_days ? zoom_factor * 115 + 'px' : '' }"
                                 class="flex gap-0.5 day-container"
                                 :class="day.isWeekend ? 'bg-gray-50' : ''"
                                 :data-day="day.fullDay"
                                 :data-day-to-jump="day.withoutFormat">
                                <SingleDayInCalendar :isFullscreen="isFullscreen" :day="day" v-if="!day.isExtraRow"/>
                                <div v-for="room in newCalendarData" :key="room.id" class="relative" v-if="!day.isExtraRow">
                                    <div v-if="room.content[day.fullDay]?.events.length > 1 && !usePage().props.auth.user.calendar_settings.expand_days" class="absolute bottom-2 right-4 z-10">
                                        <component is="IconChevronDown" @click="scrollToNextEventInDay(day.withoutFormat, room.content[day.fullDay].events.length,room.roomId)" class="h-6 w-6 text-gray-400 text-hover cursor-pointer" stroke-width="2"/>
                                    </div>
                                    <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: usePage().props.auth.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px', minHeight: usePage().props.auth.user.calendar_settings.expand_days ? zoom_factor * 115 + 'px' : '' }"
                                         :class="[zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                                         class="group/container border-t border-gray-300 border-dashed" :id="'scroll_container-' + day.withoutFormat">
                                        <div v-if="usePage().props.auth.user.calendar_settings.display_project_groups" v-for="group in getAllProjectGroupsInEventsByDay(room.content[day.fullDay].events)" :key="group.id">
                                            <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: first_project_tab_id })"  class="bg-artwork-navigation-background text-white text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1">
                                                <component :is="group.icon" class="size-4" aria-hidden="true"/>
                                                <span>{{ group.name }}</span>
                                            </Link>
                                        </div>
                                        <div v-for="(event, index) in room.content[day.fullDay].events">
                                            <div class="py-0.5" :key="event.id" :id="'event_scroll-' + index + '-day-' + day.withoutFormat + '-room-' + room.roomId">
                                                <AsyncSingleEventInCalendar
                                                    :event="event"
                                                    v-if="event.roomId === room.roomId"
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
                                                    :verifierForEventTypIds="verifierForEventTypIds"
                                                    :is-planning="isPlanning"
                                                />
                                            </div>
                                        </div>
                                        <div class="absolute left-2 bottom-3 hidden group-hover/container:block">

                                            <ToolTipComponent
                                                :tooltip-text="isPlanning ? $t('Add new planned event') : $t('Add new event on this day')"
                                                icon="IconCircleDashedPlus"
                                                classes="cursor-pointer card glassy text-artwork-buttons-create h-8 w-8 flex items-center justify-center rounded-full"
                                                @click="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="usePage().props.auth.user.daily_view && !usePage().props.auth.user.at_a_glance">
                <AsyncDailyViewCalendar
                    :multi-edit="multiEdit"
                    :rooms="rooms"
                    :days="days"
                    :calendarData="newCalendarData"
                    :project="project"
                    :eventStatuses="eventStatuses"
                    :eventTypes="eventTypes"
                    :eventsWithoutRoom="eventsWithoutRoom"
                    :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                    :firstProjectShiftTabId="firstProjectShiftTabId"
                    :first-project-tab-id="first_project_tab_id"
                    @edit-event="showEditEventModel"
                    @edit-sub-event="openAddSubEventModal"
                    @open-add-sub-event-modal="openAddSubEventModal"
                    @open-confirm-modal="openDeleteEventModal"
                    @show-decline-event-modal="openDeclineEventModal"
                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                    :verifierForEventTypIds="verifierForEventTypIds"
                    :is-planning="isPlanning"
                />
            </div>
            <div class="mt-[4.5rem] w-max" v-else>
                <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg mb-3">
                    <div v-for="room in newCalendarData" :key="room.roomId">
                        <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                            <SingleRoomInHeader :room="room" is-light   />
                        </div>
                    </div>
                </div>
                <div class="flex gap-0.5">
                    <div v-for="room in newCalendarData">
                        <div v-for="events in room.content" :key="events" class="flex flex-col">
                            <div v-for="(event, index) in events.events" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="mb-0.5" :id="'scroll_container-' + events.date">
                                <div class="py-0.5" :key="event.id">
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
                                        :verifierForEventTypIds="verifierForEventTypIds"
                                        :is-planning="isPlanning"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="!checkIfAnyRoomHasAnEventOrShift && usePage().props.auth.user.calendar_settings.use_project_time_period">
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
        <div class="flex items-center justify-center h-full gap-4" v-if="!isPlanning">
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
        <div class="flex items-center justify-center h-full gap-4" v-else>
            <div v-if="can('can see planning calendar') || hasAdminRole()">
                <FormButton :disabled="computedCheckedEventsForMultiEditCount === 0"
                            @click="requestVerification"
                            :text="computedCheckedEventsForMultiEditCount + ' ' + $t('request verification')"
                            class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div v-if="can('can edit planning calendar') || hasAdminRole()">
                <FormButton :disabled="computedCheckedEventsForMultiEditCount === 0"
                            @click="approveRequests"
                            :text="computedCheckedEventsForMultiEditCount + ' ' + $t('Approve events')"
                            class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div v-if="can('can edit planning calendar') || hasAdminRole()">
                <FormButton
                    class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="showRejectEventVerificationModal = true"
                    :disabled="computedCheckedEventsForMultiEditCount === 0"
                    :text="computedCheckedEventsForMultiEditCount + ' ' + $t('Reject events')"/>
            </div>
        </div>
    </div>

    <AsyncEventComponent
        v-if="showEventComponent"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :calendarProjectPeriod="usePage().props.auth.user.calendar_settings.use_project_time_period"
        :project="project"
        :event="eventToEdit"
        :wantedRoomId="wantedRoom"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :requires-axios-requests="true"
        @closed="eventComponentClosed"
        :event-statuses="eventStatuses"
        :is-planning="isPlanning"
        :wanted-date="wantedDate"

    />

    <!--<CreateOrUpdateEventModal
        v-if="showEventComponent"
        :event-to-edit="eventToEdit"
        :rooms="rooms"
        :room-collisions="roomCollisions"
        @closed="eventComponentClosed"
    />-->

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
    <AsyncEventsWithoutRoomComponent
        v-if="showEventsWithoutRoomComponent"
        @closed="showEventsWithoutRoomComponent = false"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="usePage().props.eventsWithoutRoom"
        :isAdmin="hasAdminRole()"
        :event-statuses="eventStatuses"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
    />

    <RejectEventVerificationRequestModal
        v-if="showRejectEventVerificationModal"
        @close="closeShowRejectEventVerificationModal"
        :event-ids="editEvents"
    />

</template>

<script setup>
import {computed, defineAsyncComponent, inject, onMounted, onUnmounted, ref} from "vue";
import {router, usePage, Link} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import {usePermission} from "@/Composeables/Permission.js";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import AddSubEventModal from "@/Layouts/Components/AddSubEventModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {useDaysAndEventsIntersectionObserver} from "@/Composeables/IntersectionObserver.js";
import MultiDuplicateModal from "@/Layouts/Components/MultiDuplicateModal.vue";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";
import CalendarHeader from "@/Components/Calendar/Elements/CalendarHeader.vue";
import FunctionBarCalendar from "@/Components/FunctionBars/FunctionBarCalendar.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {can} from "laravel-permission-to-vuejs";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

const props = defineProps({
    rooms: {
        type: Object,
        required: true,
    },
    days: {
        type: Object,
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
    },
    isPlanning: {
        type: Boolean,
        required: false,
        default: false
    },
    verifierForEventTypIds: {
        type: Array,
        required: false,
        default: []
    }
})

const $t = useTranslation()
const { composedCurrentDaysInViewRef, composedStartDaysAndEventsIntersectionObserving} = useDaysAndEventsIntersectionObserver()
const {hasAdminRole} = usePermission(usePage().props)
const AsyncSingleEventInCalendar = defineAsyncComponent({
    loader: () =>  import('@/Components/Calendar/Elements/SingleEventInCalendar.vue'),
    loadingComponent: CalendarPlaceholder,
});

const AsyncEventComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/EventComponent.vue'),
})

const AsyncEventsWithoutRoomComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/EventsWithoutRoomComponent.vue'),
})

const AsyncDailyViewCalendar = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/DailyViewCalendar.vue'),
})

const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import('@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue'),
    delay: 200,
    timeout: 3000,
})

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return {
        fontSize,
        lineHeight,
    };
})
const scrollToNextEventInDay = (day, length,room) => {
    let eventScroll = document.getElementById('event_scroll-' + (length - 1) + '-day-' + day + '-room-' + room);
    if (eventScroll) {
        eventScroll.scrollIntoView({
            behavior: 'smooth', // Optionale Animation für weiches Scrollen
            block: 'nearest',  // Scrolle nur so weit wie nötig
            inline: 'nearest' // Stelle sicher, dass es nicht die ganze Seite beeinflusst
        })
    }
};

const computedCheckedEventsForMultiEditCount = computed(() => {
    return editEvents.value.length;
});

const eventsWithoutRoomRef = ref(props.eventsWithoutRoom );
const first_project_calendar_tab_id = inject('first_project_calendar_tab_id');
const first_project_tab_id = inject('first_project_tab_id');
const eventTypes = inject('eventTypes');
const multiEdit = ref(false);
const isFullscreen = ref(false);
const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
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
const wantedDate = ref(null);
const showRejectEventVerificationModal = ref(false);

const openNewEventModalWithBaseData = (day, roomId) => {
    eventToEdit.value = false
    wantedRoom.value = roomId;
    wantedDate.value = day;
    showEventComponent.value = true;
};


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
    //toggleMultiEdit(false);
};


const getAllProjectGroupsInEventsByDay = (events) => {
    let projectGroups = [];

    events.forEach(event => {
        if (event?.project) {
            let project = event.project;

            if (project.isGroup) {
                // Falls das Projekt selbst eine Gruppe ist, hinzufügen
                if (!projectGroups.some(group => group.id === project.id)) {
                    projectGroups.push(project);
                }
            } else if (project.isInGroup && Array.isArray(project.group)) {
                // Falls das Projekt in einer Gruppe ist, die Gruppen-Infos nutzen
                project.group.forEach(group => {
                    if (!projectGroups.some(g => g.id === group.id)) {
                        projectGroups.push(group);
                    }
                });
            }
        }
    });

    return projectGroups;
};

const checkIfUserIsAdminOrInGroup = (group) => {
    if (hasAdminRole()) {
        return false;
    }

    return !group.userIds.includes(usePage().props.auth.user.id);
}


const checkIfAnyRoomHasAnEventOrShift = computed(() => {
    // Prüfen, ob irgendein Raum ein Event oder eine Schicht im aktuellen Kalender hat
    return newCalendarData.value.some(room => {
        return room.content && Object.entries(room.content).some(([date, { events }]) => {
            return events && events.length > 0;
        });
    });
});

const toggleMultiEdit = (value, isPlanning = false) => {
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
    if (closedOnPurpose) {}

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
        let calendar_settings = usePage().props.auth.user.calendar_settings;
        //@todo: temporary see ARTWORK-300
        if (calendar_settings.use_project_time_period) {
            router.patch(route('user.calendar_settings.toggle_calendar_settings_use_project_period'), {
                    use_project_time_period: true,
                    project_id: calendar_settings.time_period_project_id,
                }, {
                    preserveState: false,
                    preserveScroll: true
                }
            );
            return;
        }
    }

    // Reset event data to prevent stale data when reopening the component
    showEventComponent.value = false;
    eventToEdit.value = null;
    wantedRoom.value = null;
    wantedDate.value = null;
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
    axios.post(route('multi-edit.delete'), {
            events: editEvents.value
        }
    ).finally(() => {
            openDeleteSelectedEventsModal.value = false;
            resetMultiEdit();
        }
    );
};

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

const approveRequests = () => {
    router.post(route('event-verifications.approved-by-events'), {
        events: editEvents.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            resetMultiEdit();
        }
    })
}

const requestVerification = () => {
    router.post(route('events-verifications.request-verification'), {
        events: editEvents.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            resetMultiEdit();
        }
    })
}

const closeShowRejectEventVerificationModal = () => {
    showRejectEventVerificationModal.value = false;
    resetMultiEdit();
}

onMounted(() => {
    const ShiftCalendarListener = useShiftCalendarListener(newCalendarData);
    ShiftCalendarListener.init();
})

onMounted(async () => {
    const dateRanges = splitByMonth();
    const updated = JSON.parse(JSON.stringify(newCalendarData.value));

    for (let i = 0; i < dateRanges.length; i++) {
        const batch = dateRanges.slice(i, i + 1);
        const responses = await Promise.all(
            batch.map(([start_date, end_date]) =>
                axios.get(route('events'), { params: { start_date, end_date, isPlanning: props.isPlanning } })
            )
        );

        responses.forEach(res => {
            res.data.calendar.forEach(fullRoom => {
                const target = updated.find(r => r.roomId === fullRoom.roomId);
                if (!target) return;
                Object.entries(fullRoom.content).forEach(([day, { events }]) => {
                    if (target.content[day]) {
                        target.content[day].events = events;
                    }
                });
            });
        });

        newCalendarData.value = JSON.parse(JSON.stringify(updated));
    }
});

const splitByMonth = () => {
    const dateValues = inject('dateValue');
    const result = [];
    const start = new Date(dateValues[0]);
    const end   = new Date(dateValues[1]);

    const lastDayOf = (year, month) => new Date(year, month + 1, 0).getDate();

    let currYear  = start.getFullYear();
    let currMonth = start.getMonth();
    let currDay   = start.getDate();

    while (currYear < end.getFullYear() || currMonth < end.getMonth()) {
        const monthLastDay = lastDayOf(currYear, currMonth);
        result.push([
            `${currYear.toString().padStart(4,'0')}-${(currMonth+1).toString().padStart(2,'0')}-${currDay.toString().padStart(2,'0')}`,
            `${currYear.toString().padStart(4,'0')}-${(currMonth+1).toString().padStart(2,'0')}-${monthLastDay.toString().padStart(2,'0')}`
        ]);
        currDay = 1;
        currMonth++;
        if (currMonth === 12) {
            currMonth = 0;
            currYear++;
        }
    }

    result.push([
        `${currYear.toString().padStart(4,'0')}-${(currMonth+1).toString().padStart(2,'0')}-${currDay.toString().padStart(2,'0')}`,
        dateValues[1]
    ]);

    return result;
}
</script>

<style scoped>
.cell {
    overflow: auto;

    /* Standard-Scrollbar für Firefox */
    scrollbar-color: #d4d4d4 #f3f3f3;
    scrollbar-width: thin;
}

/* Webkit (Chrome, Edge, Safari) */
.cell::-webkit-scrollbar {
    width: 2px !important; /* Sehr schmal */
    height: 2px !important; /* Falls horizontal */
}

.cell::-webkit-scrollbar-thumb {
    background-color: #d4d4d4;
    border-radius: 10px;
}

.cell::-webkit-scrollbar-track {
    background-color: #f3f3f3;
}

</style>
