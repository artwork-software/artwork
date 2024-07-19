<template>
    <div id="myCalendar"
         ref="calendarRef"
         class="bg-white" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="-my-5 -mx-5 sticky top-0 z-40">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                @update-multi-edit="updateMultiEdit"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
                :project="project"
            />
            <div class="flex justify-center w-full bg-white"
                 :class="filteredEvents?.length ? '' : ''"
                 v-if="filteredEvents?.length > 0">
                <div class="flex errorText items-center cursor-pointer my-2"
                     @click="showEventsWithoutRoomComponent = true"
                     v-if="filteredEvents?.length > 0">
                    <IconAlertTriangle class="h-6 mr-2"/>
                    {{
                        filteredEvents?.length === 1 ?
                            $t('{0} Event without room!', [filteredEvents?.length]) :
                            $t('{0} Events without room!', [filteredEvents?.length])
                    }}
                </div>
            </div>
        </div>

        <div class="pl-14 -mx-5 my-5">
            <div :class="project ? 'bg-lightBackgroundGray' : 'bg-white px-5'">
                <AsyncCalendarHeader :rooms="rooms" :filtered-events-length="filteredEvents?.length"/>
                <div class="divide-y divide-gray-200 divide-dashed" ref="calendarToCalculate">
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
                            <template v-if="currentDaysInView.has(day.full_day)">
                                <div v-for="event in room[day.full_day].events.data">
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
                                        />
                                    </div>
                                </div>
                            </template>
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
                <FormButton :disabled="checkedEventsForMultiEditCount === 0"
                            @click="showMultiEditModal = true"
                            :text="checkedEventsForMultiEditCount + ' Termin(e) verschieben'"
                            class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div>
                <FormButton
                    class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="openDeleteSelectedEventsModal = true"
                    :disabled="checkedEventsForMultiEditCount === 0"
                    :text="checkedEventsForMultiEditCount + ' ' + $t('Delete events')"/>
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


    <AddSubEventModal
        v-if="showAddSubEventModal"
        :event="eventToEdit"
        :event-types="eventTypes"
        :sub-event-to-edit="subEventToEdit"
        @close="showAddSubEventModal = false"/>
    <ConfirmDeleteModal
        v-if="openDeleteSelectedEventsModal"
        :title="$t('Delete assignments')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
        @closed="closeDeleteSelectedEventsModal"
        @delete="deleteSelectedEvents"/>
    <MultiEditModal v-if="showMultiEditModal"
                    :checked-events="editEvents"
                    :rooms="rooms"
                    @closed="closeMultiEditModal"/>
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="showEventsWithoutRoomComponent = false"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="filteredEvents"
        :isAdmin="hasAdminRole()"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
    />
    <div v-if="showReceivesNewDataOverlay"
         class="bg-opacity-50 bg-black text-white w-full h-full absolute inset-0 z-50 justify-center text-center">
        <div class="flex flex-col w-full h-full justify-center">
            {{ $t('Your view is being processed, please wait a moment.') }}
        </div>
    </div>
</template>
<script setup>
import {computed, defineAsyncComponent, inject, onMounted, ref, watch} from "vue";
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

onMounted(() => {
    const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const day = entry.target.dataset.day;
                    if (entry.isIntersecting) {
                        currentDaysInView.value.add(day);
                    } else {
                        currentDaysInView.value.delete(day);
                    }
                });
            }
        ),
        dayContainers = document.querySelectorAll('.day-container');

    dayContainers.forEach((container) => {
        observer.observe(container);
    });
});

const $t = useTranslation(),
    {getDaysOfEvent, reloadRoomsAndDays, formatEventDateByDayJs} = useEvent(),
    {hasAdminRole} = usePermission(usePage().props),
    AsyncFunctionBarCalendar = defineAsyncComponent(() =>
        import('@/Components/FunctionBars/FunctionBarCalendar.vue')
    ),
    AsyncCalendarHeader = defineAsyncComponent(() =>
        import('@/Components/Calendar/Elements/CalendarHeader.vue')
    ),
    AsyncSingleEventInCalendar = defineAsyncComponent({
        loader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue')
    }),
    props = defineProps({
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
    computedCalendarData = computed(() => {
        if (!hasReceivedNewData.value) {
            return calendarDataRef;
        }

        for (const [day, rooms] of Object.entries(receivedRoomData.value)) {
            for (const [roomId, events] of Object.entries(rooms)) {
                calendarDataRef.value.forEach(
                    (roomWithEvents) => {
                        if (roomWithEvents[day]?.roomId === Number(roomId)) {
                            roomWithEvents[day].events.data = JSON.parse(JSON.stringify(events));
                        }
                    }
                );
            }
        }

        hasReceivedNewData.value = false;
        receivedRoomData.value = [];

        if (showReceivesNewDataOverlay.value) {
            showReceivesNewDataOverlay.value = false;
        }

        return calendarDataRef;
    }),
    textStyle = computed(() => {
        const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
        const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
        return {
            fontSize,
            lineHeight,
        };
    }),
    filteredEvents = computed(() => {
        return props.eventsWithoutRoom?.filter((event) => {
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
    }),
    hasReceivedNewData = ref(false),
    receivedRoomData = ref([]),
    calendarDataRef = ref(JSON.parse(JSON.stringify(props.calendarData))),
    first_project_calendar_tab_id = inject('first_project_calendar_tab_id'),
    eventTypes = inject('eventTypes'),
    multiEdit = ref(false),
    isFullscreen = ref(false),
    zoom_factor = ref(usePage().props.user.zoom_factor ?? 1),
    checkedEventsForMultiEditCount = ref(0),
    showMultiEditModal = ref(false),
    editEvents = ref([]),
    editEventsRooms = ref([]),
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
    currentDaysInView = ref(new Set()),
    showReceivesNewDataOverlay = ref(false),
    getProjectIdFromProps = () => props.project ? props.project.id : 0,
    handleReload = async (desiredRoomIdsToReload, desiredDaysToReload) => {
        showReceivesNewDataOverlay.value = true;

        receivedRoomData.value = await reloadRoomsAndDays(
            desiredRoomIdsToReload,
            desiredDaysToReload,
            getProjectIdFromProps()
        );

        hasReceivedNewData.value = true;
    },
    updateMultiEdit = (value) => {
        multiEdit.value = value;
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
    openAddSubEventModal = (event) => {
        subEventToEdit.value = event;
        showAddSubEventModal.value = true;
    },
    showEditEventModel = (event) => {
        eventToEdit.value = event;
        showEventComponent.value = true;
    },
    openFullscreen = () => {
        let elem = document.getElementById('myCalendar');
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
            this.isFullscreen = true;
        } else if (elem.webkitRequestFullscreen) { /* Safari */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE11 */
            elem.msRequestFullscreen();
        }
    },
    listenToFullscreen = () => {
        if (window.innerHeight === screen.height) {
            isFullscreen.value = true;
        } else {
            isFullscreen.value = false;
            zoom_factor.value = 1;
        }
    },
    closeMultiEditModal = (closedOnPurpose, desiredRoomIds, desiredDays) => {
        showMultiEditModal.value = false;
        if (closedOnPurpose) {
            if (desiredRoomIds && desiredDays) {
                handleReload(desiredRoomIds, desiredDays);
            }

            removeCheckedState();
            multiEdit.value = false;
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
            )
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
            removeCheckedState()
            multiEdit.value = false;
        }
    },
    removeCheckedState = () => {
        calendarDataRef.value.forEach((room) => {
            props.days.forEach((day) => {
                room[day.full_day].events.data?.forEach((event) => {
                    event.clicked = false;
                });
            });
        });
        editEventsRooms.value = [];
        editEventsRoomsDesiredDays.value = [];
    },
    deleteSelectedEvents = (closedOnPurpose) => {
        axios.post(
            route('multi-edit.delete'),
            {
                events: editEvents.value
            }
        ).finally(
            () => {
                handleReload(
                    editEventsRooms.value,
                    editEventsRoomsDesiredDays.value
                );

                openDeleteSelectedEventsModal.value = false
                multiEdit.value = false;
            }
        );
    };

watch(
    () => calendarDataRef,
    (calendarData) => {
        let count = 0;
        calendarData.value.forEach((room) => {
            props.days.forEach((day) => {
                room[day.full_day].events.data?.forEach((event) => {
                    if (event.clicked) {
                        if (!editEvents.value.includes(event.id)) {
                            if (!editEventsRooms.value.includes(event.roomId)) {
                                editEventsRooms.value.push(event.roomId);
                            }
                            editEvents.value.push(event.id);
                            editEventsRoomsDesiredDays.value = getDaysOfEvent(
                                formatEventDateByDayJs(event.start),
                                formatEventDateByDayJs(event.end)
                            ).concat(editEventsRoomsDesiredDays.value);
                        }
                        count++;
                    } else {
                        editEvents.value = editEvents.value.filter((id) => id !== event.id);
                    }
                });
            });
        });

        editEventsRooms.value = Array.from(new Set(editEventsRooms.value));
        editEventsRoomsDesiredDays.value = Array.from(new Set(editEventsRoomsDesiredDays.value));
        checkedEventsForMultiEditCount.value = count;
    },
    {deep: true}
);

watch(multiEdit, (value) => {
    if (!value) {
        editEvents.value = [];
        removeCheckedState();
    }
});
</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




