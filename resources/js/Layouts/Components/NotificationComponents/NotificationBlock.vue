<template>
    <div class="flex justify-between items-start" :class="isDashboard ? '' : 'my-5'">
        <div class="flex items-start gap-3">
            <img :src="'/Svgs/IconSvgs/icon_notification_' + notification.data.icon + '.svg'" alt="">
            <div class="">
                <div class="flex items-center gap-4">
                    <div class="flex gap-5 items-center">
                        <h4 class="text-sm/5 font-semibold text-text">{{ notification.data.title }}</h4>
                        <div class="" v-if="notification.data.showHistory">
                            <div @click="openHistory" class="text-xs/[18px] text-text-subtle cursor-pointer items-center flex text-accent-600">
                                <IconChevronRight class="h-3 w-3"/>
                                <span>
                                    {{ $t('View history')}}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Zeitpunkt immer, „von“ nur mit bekannter Person (Scheduler/Externe: ohne). Kontaktdaten
                         lädt der Tooltip rechtegeprüft nach – im Payload stehen nur Name und Foto. -->
                    <div class="flex items-center gap-2 text-xs/[18px] text-text-subtle" v-if="notification.data?.created_at || notification.data?.created_by">
                        {{ notification.data.created_at }}
                        <template v-if="notification.data?.created_by">
                            {{ $t('from')}}
                            <UserPopoverTooltip :id="notification.id" :user="notification.data.created_by"
                                                lazy-load height="5" width="5"/>
                        </template>
                    </div>
                </div>
                <div class="text-xs/[18px] text-text-subtle mt-2 flex gap-1 items-center" v-if="notification.data?.description">
                    <div v-for="(description, index) in notification.data?.description" class="divide-x">
                        <p v-if="description?.type !== 'comment' && description?.title">
                            <!-- auch Textzeilen mit Ziel sind klickbar (Schicht-, Konflikt-, Regelmeldungen) -->
                            <a :href="description.href" v-if="description.href"
                               class="text-accent-600">{{ description.title }}</a>
                            <span v-else>{{ description.title }}</span>
                        </p>
                    </div>
                </div>
                <!-- Kommentare (z. B. Begründung einer Absage) eigene Zeile – früher fest description[5] -->
                <p v-for="(comment, index) in commentRows" :key="'comment-' + index"
                   class="mt-2 text-xs/[18px] text-text-subtle italic">
                    „{{ comment.title }}“
                </p>
                <span v-if="notification.data.isModified" class="text-special-orange bg-special-orange-surface px-2 py-1 rounded text-xs font-medium">
                    {{ $t('modified') }}
                </span>
                <div v-if="notification.data.handledStatus" class="mt-2 text-xs font-medium">
                    <span v-if="notification.data.handledStatus === 'accepted'" class="text-success bg-success-surface px-2 py-1 rounded">
                        {{ $t('Already accepted by') }} {{ notification.data.handledBy?.name }}
                    </span>
                    <span v-else-if="notification.data.handledStatus === 'declined'" class="text-danger bg-danger-surface px-2 py-1 rounded">
                        {{ $t('Already declined by') }} {{ notification.data.handledBy?.name }}
                    </span>
                    <span v-else-if="notification.data.handledStatus === 'deleted'" class="text-text-muted bg-surface-sunken px-2 py-1 rounded">
                        {{ $t('Event deleted by') }} {{ notification.data.handledBy?.name }}
                    </span>
                    <span v-else-if="notification.data.handledStatus === 'material_returned'" class="text-success bg-success-surface px-2 py-1 rounded">
                        {{ $t('Return confirmed by') }} {{ notification.data.handledBy?.name }}<template v-if="notification.data.handledAt"> ({{ notification.data.handledAt }})</template>
                    </span>
                    <span v-else-if="notification.data.handledStatus === 'material_not_returned'" class="text-danger bg-danger-surface px-2 py-1 rounded">
                        {{ $t('Reported as not returned by') }} {{ notification.data.handledBy?.name }}<template v-if="notification.data.handledAt"> ({{ notification.data.handledAt }})</template>
                    </span>
                </div>
                <NotificationButtons v-if="!isArchive"
                                     :buttons="notification.data.buttons"
                                     @showInCalendar="showInCalendar"
                                     @openDeclineModal="loadEventDataForDecline"
                                     @openEventEditAccept="loadEventDataForEditAndAccept"
                                     @acceptRoomRequest="acceptRoomRequest"
                                     @openDialogModal="loadEventDataForDialog"
                                     @deleteEvent="showDeleteConfirmModal = true"
                                     @see-shift="openShift"
                                     @openProjectCalculation="openProjectBudget(notification.data?.projectId)"
                                     @open-event-without-room-modal="loadEventDataForEventWithoutRoom"
                                     @deleteNotification="setReadAt"
                                     @openProject="openProjectShift(notification.data?.projectId, notification.data?.eventId, notification.data?.shiftId)"
                                     @showInTask="openProjectTasks(notification.data?.taskId)"
                                     @show-project="openProject(notification.data?.projectId)"
                                     @delete-verification-request="deleteVerificationRequest"
                                     @confirm-material-return="showMaterialReturnConfirmModal = true"
                                     @decline-material-return="declineMaterialReturn"
                />
                <Link v-if="notification.data?.type && !isDashboard"
                      :href="route('notifications.index', { tab: 'settings', type: notification.data.type })"
                      class="mt-2 inline-block text-xs text-text-subtle hover:text-accent-600 underline-offset-2 hover:underline focus-visible:opacity-100"
                      :class="notification.hovered ? '' : 'md:opacity-0'">
                    {{ $t('Settings for this type') }}
                </Link>
            </div>
        </div>
        <!-- immer sichtbar (auch auf Touch-Geräten), bei Hover hervorgehoben -->
        <button v-if="!isArchive && isArchivable(notification.data.buttons)"
                type="button"
                class="ml-1 shrink-0 rounded-full bg-accent-600 p-1 transition-opacity"
                :class="notification.hovered ? 'opacity-100' : 'opacity-40 hover:opacity-100 focus-visible:opacity-100'"
                :aria-label="$t('Archive notification')"
                :title="$t('Archive notification')"
                @click="setReadAt">
            <img src="/Svgs/IconSvgs/icon_archive_white.svg" class="h-4 w-4" alt="" aria-hidden="true"/>
        </button>
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
        :request-to-decline="eventToDecline"
        :event-types="eventTypes"
        preserve-state
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
        :event-statuses="eventStatuses"
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
    <MaterialIssueReturnConfirmModal
        v-if="showMaterialReturnConfirmModal"
        :external-issue-id="notification.data?.modelId"
        @close="showMaterialReturnConfirmModal = false"
    />
</template>

<script>
import {IconChevronRight} from "@tabler/icons-vue";
import { isArchivable } from "@/Layouts/Components/NotificationComponents/archivableButtons.js";
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryWithoutBudgetComponent from "@/Layouts/Components/ProjectHistoryWithoutBudgetComponent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import RoomRequestDialogComponent from "@/Layouts/Components/RoomRequestDialogComponent.vue";
import UserVacationHistoryModal from "@/Pages/Notifications/Components/UserVacationHistoryModal.vue";
import EventHistoryModal from "@/Pages/Notifications/Components/EventHistoryModal.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import MaterialIssueReturnConfirmModal from "@/Layouts/Components/NotificationComponents/MaterialIssueReturnConfirmModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { provide } from 'vue';

export default {
    name: "NotificationBlock",
    components: {
        Link,
        UserPopoverTooltip,
        MaterialIssueReturnConfirmModal,
        EventsWithoutRoomComponent,
        EventHistoryModal,
        UserVacationHistoryModal,
        ConfirmDeleteModal,
        EventComponent,
        ProjectHistoryWithoutBudgetComponent,
        NewUserToolTip,
        DeclineEventModal,
        NotificationButtons, IconChevronRight,
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
            // Stand beim Öffnen: die Seiten-Prop `event` teilen sich alle Blöcke – lädt ein anderer
            // Block nach, darf die Absage nicht plötzlich dessen Termin treffen
            eventToDecline: null,
            createEventComponentIsVisible: false,
            showDeleteConfirmModal: false,
            showEventWithoutRoomComponent: false,
            showRoomRequestDialogComponent: false,
            showUserVacationHistory: false,
            showEventHistory: false,
            showMaterialReturnConfirmModal: false,
            answering: false,
        }
    },
    computed: {
        commentRows() {
            const description = this.notification.data?.description;
            return description
                ? Object.values(description).filter((row) => row?.type === 'comment' && row?.title)
                : [];
        },
    },
    methods: {
        isArchivable,
        declineMaterialReturn() {
            if (!this.notification.data?.modelId || this.answering) {
                return;
            }
            this.answering = true;
            router.post(
                route('extern-issue-of-material.return-decline', this.notification.data.modelId),
                {},
                {
                    preserveScroll: true,
                    onFinish: () => { this.answering = false; },
                }
            );
        },
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
        /**
         * Dialog-Daten nachladen (nur diese Props) und den Dialog erst öffnen, wenn sie da sind.
         * Fehlt der Termin (gelöscht, kein Zugriff), Hinweis statt leerem Dialog.
         */
        loadDialogData(data, open) {
            router.reload({
                data,
                only: ['event', 'historyObjects', 'wantedSplit'],
                preserveScroll: true,
                onSuccess: () => {
                    if (data.eventId && String(this.event?.id ?? '') !== String(data.eventId)) {
                        this.$toast.error(this.$t('The entry no longer exists. Please reload the page.'));
                        return;
                    }
                    open();
                },
            });
        },
        openHistory() {
            const historyType = this.notification.data?.historyType;
            this.loadDialogData(
                {showHistory: true, historyType, modelId: this.notification.data?.modelId},
                () => {
                    this.showProjectHistory = historyType === 'project';
                    this.showUserVacationHistory = historyType === 'vacations';
                    this.showEventHistory = historyType === 'event';
                }
            );
        },
        loadEventDataForDecline() {
            this.loadDialogData(
                {openDeclineEvent: true, eventId: this.notification.data?.eventId},
                () => {
                    this.eventToDecline = this.event;
                    this.showDeclineEventModal = true;
                }
            );
        },
        closeDeclineEventModal() {
            this.showDeclineEventModal = false;
        },
        loadEventDataForEditAndAccept() {
            this.loadDialogData(
                {openEditEvent: true, eventId: this.notification.data?.eventId},
                () => { this.createEventComponentIsVisible = true; }
            );
        },
        loadEventDataForDialog() {
            this.loadDialogData(
                {openEditEvent: true, eventId: this.notification.data?.eventId},
                () => { this.showRoomRequestDialogComponent = true; }
            );
        },
        loadEventDataForEventWithoutRoom(){
            this.loadDialogData(
                {openEditEvent: true, eventId: this.notification.data?.eventId},
                () => { this.showEventWithoutRoomComponent = true; }
            );
        },
        /** „Schicht ansehen“: erstes Ziel aus der Beschreibung, sonst Schichten-Tab des Projekts */
        openShift() {
            const target = this.descriptionRows().find((row) => row.href);
            if (target) {
                window.location.href = target.href;
                return;
            }
            if (this.notification.data?.projectId) {
                this.openProjectShift(this.notification.data.projectId, this.notification.data?.eventId, this.notification.data?.shiftId);
            }
        },
        onEventComponentClose(bool) {
            this.createEventComponentIsVisible = false;

            // nur bei echtem Speichern (true) – „Abbrechen“ lieferte früher das Klick-Event (truthy)
            // und löschte die Benachrichtigung
            if (bool === true && this.checkNotificationKey(this.notification.data?.notificationKey)) {
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

            // nur bei echtem Speichern (true) – „Abbrechen“ lieferte früher das Klick-Event (truthy)
            // und löschte die Benachrichtigung
            if (bool === true && this.checkNotificationKey(this.notification.data?.notificationKey)) {
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

            // nur bei echtem Speichern (true) – „Abbrechen“ lieferte früher das Klick-Event (truthy)
            // und löschte die Benachrichtigung
            if (bool === true && this.checkNotificationKey(this.notification.data?.notificationKey)) {
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
            // Schlüssel ist optional (räumt nur die Benachrichtigung mit auf) – ohne ihn passierte vorher
            // nichts und der Bestätigungsdialog blieb offen
            if (this.notification.data?.eventId) {
                router.post(route('events.delete.by.notification', this.notification.data.eventId), {
                    notificationKey: this.notification.data?.notificationKey ?? ''
                }, {
                    preserveScroll: true,
                    preserveState: true
                });
            }
            this.showDeleteConfirmModal = false;
        },
        checkNotificationKey(key){
            // ältere Einträge haben gar keinen Schlüssel (undefined) – vorher TypeError bei .length
            return typeof key === 'string' && key.length > 0;
        },
        openProjectBudget(projectId) {
            const projectTab = this.first_project_budget_tab_id ?? this.$page.props.first_project_budget_tab_id;
            if (projectId && projectTab) {
                window.location.href = route('projects.tab', {project: projectId, projectTab});
            }
        },
        /**
         * Beschreibungszeilen als Liste – gespeichert ist meist ein Objekt mit Schlüsseln 1, 2, …
         * (vorher .find() direkt darauf → TypeError, „Zum Projekt“ tat nichts).
         */
        descriptionRows() {
            const description = this.notification.data?.description;
            return description ? Object.values(description).filter(Boolean) : [];
        },
        openProject(projectId) {
            // Link aus der Beschreibung (zeigt auf den passenden Reiter), sonst ein vorhandener Reiter
            const projectLink = this.descriptionRows().find((row) => row.type === 'link' && row.href);
            if (projectLink?.href) {
                window.location.href = projectLink.href;
                return;
            }
            const props = this.$page.props;
            const projectTab = this.notification.data?.groupType === 'SHIFTS'
                ? (this.first_project_shift_tab_id ?? props.first_project_shift_tab_id)
                : (props.first_project_tab_id ?? this.first_project_calendar_tab_id ?? props.first_project_calendar_tab_id);
            // vorher fest Reiter-ID 1 – die gibt es nicht in jeder Instanz (404)
            if (projectId && projectTab) {
                window.location.href = route('projects.tab', {project: projectId, projectTab});
            }
        },
        openProjectShift(projectId, eventId, shiftId) {
            const projectTab = this.first_project_shift_tab_id ?? this.$page.props.first_project_shift_tab_id;
            if (!projectId || !projectTab) {
                return;
            }
            // nur vorhandene IDs anhängen (vorher „?eventId=undefined&shiftId=undefined“)
            const query = new URLSearchParams(
                Object.entries({eventId, shiftId}).filter(([, value]) => value !== null && value !== undefined)
            ).toString();
            window.location.href = route('projects.tab', {project: projectId, projectTab}) + (query ? '?' + query : '');
        },
        openProjectTasks(taskId){
            window.location.href = route('tasks.own') + (taskId ? '?taskId=' + taskId : '');
        },
        showInCalendar() {
            if (this.notification.data?.eventId) {
                window.location.href = route('event-verifications.redirect-to-calendar', this.notification.data.eventId);
            }
        },
        acceptRoomRequest() {
            if (this.notification.data?.eventId && !this.answering) {
                this.answering = true;
                router.put(
                    route('events.accept', { event: this.notification.data.eventId }),
                    { accepted: true },
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            if (this.checkNotificationKey(this.notification.data?.notificationKey)) {
                                router.post(route('event.notification.delete', this.notification.data.notificationKey), {
                                    notificationKey: this.notification.data.notificationKey
                                }, { preserveScroll: true, preserveState: true });
                            }
                        },
                        onFinish: () => { this.answering = false; },
                    }
                );
            }
        }
    }
}
</script>

<style scoped>

</style>
