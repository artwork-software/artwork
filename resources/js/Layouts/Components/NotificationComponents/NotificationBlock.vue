<template>
    <div class="flex justify-between items-start" :class="isDashboard ? '' : 'my-5'">
        <div class="flex items-start gap-3">
            <img :src="'/Svgs/IconSvgs/icon_notification_' + notification.data.icon + '.svg'" alt="">
            <div class="">
                <div class="flex items-center gap-4">
                    <div class="flex gap-5 items-center">
                        <h4 class="xsDark">{{ notification.data.title }}</h4>
                        <div class="" v-if="notification.data.showHistory">
                            <div @click="openHistory" class="xxsLight cursor-pointer items-center flex text-artwork-buttons-create">
                                <ChevronRightIcon class="h-3 w-3"/>
                                <span>
                                    {{ $t('View history')}}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 xxsLight" v-if="notification.data?.description[0]">
                        {{ notification.data?.description[0].text }}
                        {{ $t('from')}}
                        <UserPopoverTooltip :id="notification.id" :user="notification.data.created_by"
                                        height="5" width="5"/>
                    </div>
                    <div class="flex items-center gap-2 xxsLight" v-else-if="notification.data.created_by">
                        {{ notification.data.created_at }}
                        {{ $t('from')}}
                        <UserPopoverTooltip :id="notification.id" :user="notification.data?.created_by" height="5"
                                        width="5"/>
                    </div>
                </div>
                <div class="xxsLight mt-2 flex gap-1 items-center" v-if="notification.data?.description">
                    <div v-for="(description, index) in notification.data?.description" class="divide-x">
                        <p v-if="description.type !== 'comment'">
                            <a :href="description.href" v-if="description.type === 'link'"
                               class="text-artwork-buttons-create">{{ description.title }}</a>
                            <span v-else>{{ description.title }}</span>
                        </p>
                    </div>
                </div>
                <p v-if="notification.data?.description[5]" class="mt-2 xxsLight">
                    {{ notification.data?.description[5]?.title }}
                </p>
                <NotificationButtons v-if="!isArchive"
                                     :buttons="notification.data.buttons"
                                     @openDeclineModal="loadEventDataForDecline"
                                     @openEventEditAccept="loadEventDataForEditAndAccept"
                                     @openDialogModal="loadEventDataForDialog"
                                     @deleteEvent="showDeleteConfirmModal = true"
                                     @openProjectCalculation="openProjectBudget(notification.data?.projectId)"
                                     @open-event-without-room-modal="loadEventDataForEventWithoutRoom"
                                     @deleteNotification="setReadAt"
                                     @openProject="openProjectShift(notification.data?.projectId, notification.data?.eventId, notification.data?.shiftId)"
                                     @showInTask="openProjectTasks(notification.data?.taskId)"
                                     @show-project="openProject(notification.data?.projectId)"
                                     @delete-verification-request="deleteVerificationRequest"
                />
            </div>
        </div>
        <img @click="setReadAt"
             v-show="notification.hovered"
             v-if="!isArchive && notification.data.buttons.length === 0"
             src="/Svgs/IconSvgs/icon_archive_white.svg"
             class="h-6 w-6 p-1 ml-1 flex cursor-pointer bg-artwork-buttons-create rounded-full"
             aria-hidden="true"
             alt=""/>
    </div>
    <ProjectHistoryWithoutBudgetComponent
        v-if="showProjectHistory"
        :project_history="historyObjects"
        @closed="showProjectHistory = false"
    />
    <UserVacationHistoryModal
        v-if="showUserVacationHistory"
        :project_history="historyObjects"
        @closed="showUserVacationHistory = false" />
    <EventHistoryModal
        v-if="showEventHistory"
        :project_history="historyObjects"
        @closed="showEventHistory = false" />
    <DeclineEventModal
        :request-to-decline="event"
        :event-types="eventTypes"
        @closed="closeDeclineEventModal"
        @declined="finishDeclineEvent"
        v-if="showDeclineEventModal"
    />
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        show-comments="true"
        :project="project"
        :event="event"
        :wantedRoomId="wantedSplit"
        :isAdmin="hasAdminRole() || $canAny(['create, delete and update rooms'])"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
        :event-statuses="eventStatuses"
    />
    <room-request-dialog-component
        v-if="showRoomRequestDialogComponent"
        @closed="onDialogComponentClose"
        :showHints="this.$page.props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        show-comments="true"
        :project="project"
        :event="event"
        :wantedRoomId="wantedSplit"
        :isAdmin="this.hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventWithoutRoomComponent"
        :showHints="this.$page.props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="[event]"
        :isAdmin="this.hasAdminRole()"
        :removeNotificationOnAction="true"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
        :notification-key="this.notification.data?.notificationKey"
        @closed="onEventWithoutRoomComponentClose"
    />
    <ConfirmDeleteModal
        @closed="showDeleteConfirmModal = false"
        @delete="deleteEvent"
        :title="$t('Delete event?')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
        v-if="showDeleteConfirmModal"
    />
</template>

<script>
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import {ChevronRightIcon} from "@heroicons/vue/solid";
import {router, usePage} from "@inertiajs/vue3";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryWithoutBudgetComponent from "@/Layouts/Components/ProjectHistoryWithoutBudgetComponent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import RoomRequestDialogComponent from "@/Layouts/Components/RoomRequestDialogComponent.vue";
import UserVacationHistoryModal from "@/Pages/Notifications/Components/UserVacationHistoryModal.vue";
import EventHistoryModal from "@/Pages/Notifications/Components/EventHistoryModal.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { provide } from 'vue';

export default {
    name: "NotificationBlock",
    components: {
        UserPopoverTooltip,
        EventsWithoutRoomComponent,
        EventHistoryModal,
        UserVacationHistoryModal,
        ConfirmDeleteModal,
        EventComponent,
        ProjectHistoryWithoutBudgetComponent,
        NewUserToolTip,
        DeclineEventModal,
        NotificationButtons, ChevronRightIcon,
        RoomRequestDialogComponent
    },
    mixins: [Permissions],
    props: [
        'notification',
        'eventTypes',
        'historyObjects',
        'event',
        'rooms',
        'project',
        'wantedSplit',
        'roomCollisions',
        'isArchive',
        'first_project_shift_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id',
        'eventStatuses',
        'isDashboard',
    ],
    setup() {
        // Get event_properties from page props
        const event_properties = usePage().props.event_properties;

        // Provide event_properties to child components
        provide('event_properties', event_properties);

        return {};
    },
    data() {
        return {
            showDeclineModal: false,
            showProjectHistory: false,
            showDeclineEventModal: false,
            createEventComponentIsVisible: false,
            showDeleteConfirmModal: false,
            showEventWithoutRoomComponent: false,
            showRoomRequestDialogComponent: false,
            showUserVacationHistory: false,
            showEventHistory: false
        }
    },
    computed: {},
    methods: {
        deleteVerificationRequest() {
            router.post(
                route('project.budget.remove.verification'),
                {
                    type: this.notification.data.positionVerifyRequestType,
                    notificationKey: this.notification.data.notificationKey,
                    position: {
                        'id': this.notification.data.positionVerifyRequestId
                    }
                },
                {
                    preserveScroll: true
                }
            );
        },
        setReadAt() {
            router.patch(
                route('notifications.setReadAt'),
                {
                    notificationId: this.notification.id
                },
                {
                    preserveScroll: true,
                }
            );
        },
        openHistory() {
            router.reload({
                data: {
                    showHistory: true,
                    historyType: this.notification.data?.historyType,
                    modelId: this.notification.data?.modelId,
                },
                onFinish: () => {
                    if (this.notification.data?.historyType === 'project') {
                        this.showProjectHistory = true;
                    }
                    if (this.notification.data?.historyType === 'vacations') {
                        this.showUserVacationHistory = true;
                    }
                    if (this.notification.data?.historyType === 'event') {
                        this.showEventHistory = true;
                    }
                }
            })
        },
        loadEventDataForDecline() {
            router.reload({
                data: {
                    openDeclineEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.showDeclineEventModal = true
                }
            })
        },
        closeDeclineEventModal() {
            this.showDeclineEventModal = false;
        },
        loadEventDataForEditAndAccept() {
            router.reload({
                data: {
                    openEditEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.createEventComponentIsVisible = true;
                }
            });
        },
        loadEventDataForDialog() {
            router.reload({
                data: {
                    openEditEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.showRoomRequestDialogComponent = true;
                }
            });
        },
        loadEventDataForEventWithoutRoom(){
            router.reload({
                data: {
                    openEditEvent: true,
                    eventId: this.notification.data?.eventId
                },
                onFinish: () => {
                    this.showEventWithoutRoomComponent = true
                }
            })
        },
        onEventComponentClose(bool) {
            this.createEventComponentIsVisible = false;

            if (bool && this.checkNotificationKey(this.notification.data?.notificationKey)) {
                router.post(route('event.notification.delete', this.notification.data?.notificationKey), {
                    notificationKey: this.notification.data?.notificationKey
                }, {
                    preserveScroll: true,
                    preserveState: true,
                });
            }
        },
        onDialogComponentClose(bool) {
            this.showRoomRequestDialogComponent = false;

            if (bool && this.checkNotificationKey(this.notification.data?.notificationKey)) {
                router.post(route('event.notification.delete', this.notification.data?.notificationKey), {
                    notificationKey: this.notification.data?.notificationKey
                }, {
                    preserveScroll: true,
                    preserveState: true
                });
            }
        },
        onEventWithoutRoomComponentClose(bool) {
            this.showEventWithoutRoomComponent = false;

            if (bool && this.checkNotificationKey(this.notification.data?.notificationKey)) {
                router.post(route('event.notification.delete', this.notification.data?.notificationKey), {
                    notificationKey: this.notification.data?.notificationKey
                }, {
                    preserveScroll: true,
                    preserveState: true
                });
            }
        },
        finishDeclineEvent(){
            if (this.checkNotificationKey(this.notification.data?.notificationKey)) {
                router.post(route('event.notification.delete', this.notification.data?.notificationKey), {
                    notificationKey: this.notification.data?.notificationKey
                }, {
                    preserveScroll: true,
                    preserveState: true
                });
            }
        },
        deleteEvent() {
            if (this.checkNotificationKey(this.notification.data?.notificationKey)) {
                router.post(route('events.delete.by.notification', this.notification.data?.eventId), {
                    notificationKey: this.notification.data?.notificationKey
                }, {
                    preserveScroll: true,
                    preserveState: true
                });
                this.showDeleteConfirmModal = false;
            }
        },
        checkNotificationKey(key){
            return key !== null && key.length > 0;
        },
        openProjectBudget(projectId) {
            if (this.first_project_budget_tab_id) {
                window.location.href = route(
                    'projects.tab',
                    {
                        project: projectId,
                        projectTab: this.first_project_budget_tab_id
                    }
                );
            }
        },
        openProject(projectId) {
            window.location.href = route('projects.tab', {
                project: projectId,
                projectTab: 1
            });
        },
        openProjectShift(projectId, eventId, shiftId) {
            if (this.first_project_shift_tab_id) {
                window.location.href = route(
                    'projects.tab',
                    {
                        project: projectId,
                        projectTab: this.first_project_shift_tab_id
                    }
                ) + '?eventId=' + eventId + '&shiftId=' + shiftId;
            }
        },
        openProjectTasks(taskId){
            window.location.href = route('tasks.own') + '?taskId=' + taskId;
        }
    }
}
</script>

<style scoped>

</style>
