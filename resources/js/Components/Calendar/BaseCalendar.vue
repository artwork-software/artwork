<template>
    <div id="myCalendar"
         ref="calendarRef"
         class="bg-white max-h-screen"
         :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="-my-5 -mx-5 sticky top-0 z-40">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                @update-multi-edit="updateMultiEdit"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
            />
            <div class="flex justify-center w-full bg-white"
                 :class="filteredEvents?.length ? 'mt-5' : ''"
                 v-if="filteredEvents?.length > 0">
                <div class="flex errorText items-center cursor-pointer mb-2"
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
        <div class="pl-14 -mx-5 my-5 overflow-auto h-[90vh]">
            <div :class="project ? 'bg-lightBackgroundGray' : 'bg-white px-5'">
                <AsyncCalendarHeader :rooms="rooms"/>
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
    <div class="fixed bottom-0 w-full h-28 bg-artwork-navigation-background/30 z-100 pointer-events-none"
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
    <AddSubEventModal
        v-if="showAddSubEventModal"
        :event="eventToEdit"
        :event-types="eventTypes"
        :sub-event-to-edit="subEventToEdit"
        @close="showAddSubEventModal = false"/>
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
</template>
<script setup>
import {computed, defineAsyncComponent, inject, onMounted, ref, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import {usePermissions} from "@/Composeables/usePermissions.js";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {IconAlertTriangle} from "@tabler/icons-vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import AddSubEventModal from "@/Layouts/Components/AddSubEventModal.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {useEvents} from "@/Composeables/Event.js";
import dayjs from "dayjs";

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
    {getDaysOfEvent, reloadRoomsAndDays} = useEvents(),
    {hasAdminRole} = usePermissions(usePage().props),
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
        if (!receivedNewData.value) {
            return calendarDataRef;
        }

        receivedRoomMap.value.forEach(
            (dayMap, roomId) => {
                dayMap.forEach(
                    (events, day) => {
                        let desiredRoomName = props.rooms.find((room) => {
                            return room.id === roomId;
                        }).name;

                        calendarDataRef.value.forEach(
                            (roomWithEvents) => {
                                if (roomWithEvents[day].roomName === desiredRoomName) {
                                    roomWithEvents[day].events.data = JSON.parse(JSON.stringify(events));
                                }
                            }
                        );
                    }
                )
            }
        );

        receivedNewData.value = false;
        receivedRoomMap.value = new Map();

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
    receivedNewData = ref(false),
    receivedRoomMap = ref(new Map()),
    calendarDataRef = ref(JSON.parse(JSON.stringify(props.calendarData))),
    first_project_calendar_tab_id = inject('first_project_calendar_tab_id'),
    eventTypes = inject('eventTypes'),
    multiEdit = ref(false),
    isFullscreen = ref(false),
    zoom_factor = ref(usePage().props.user.zoom_factor ?? 1),
    checkedEventsForMultiEditCount = ref(0),
    showMultiEditModal = ref(false),
    editEvents = ref([]),
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
    getProjectIdFromProps = () => props.project ? props.project.id : 0,
    handleReload = async (desiredRoomIdsToReload, desiredDaysToReload) => {
        let roomMap = await reloadRoomsAndDays(
            desiredRoomIdsToReload,
            desiredDaysToReload,
            getProjectIdFromProps()
        );

        if (roomMap.size > 0) {
            receivedRoomMap.value = roomMap;
            receivedNewData.value = true;
        }
    },
    formatEventDateByDayJs = (date) => {
        return dayjs(date).format('YYYY-MM-DD');
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
    closeMultiEditModal = () => {
        removeCheckedState();
        multiEdit.value = false;
        showMultiEditModal.value = false;
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
    closeDeleteSelectedEventsModal = () => {
        openDeleteSelectedEventsModal.value = false;
        removeCheckedState()
        multiEdit.value = false;
    },
    removeCheckedState = () => {
        props.calendarData.forEach((room) => {
            props.days.forEach((day) => {
                room[day.full_day].events.data?.forEach((event) => {
                    event.clicked = false;
                });
            });
        });
    },
    deleteSelectedEvents = () => {
        router.post(route('multi-edit.delete'), {
            events: editEvents.value
        }, {
            onSuccess: () => {
                openDeleteSelectedEventsModal.value = false
                multiEdit.value = false;
            }
        })
    };

watch(
    () => props.calendarData,
    (value) => {
        let count = 0;
        value.forEach((room) => {
            props.days.forEach((day) => {
                room[day.full_day].events.data?.forEach((event) => {
                    if (event.clicked) {
                        count++;
                        if (!editEvents.value.includes(event.id)) {
                            editEvents.value.push(event.id);
                        }
                    } else {
                        editEvents.value = editEvents.value.filter((id) => id !== event.id);
                    }
                });
            });
        });
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




