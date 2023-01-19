<template>
    <div class="flex w-full mb-5">
        <button class="bg-buttonBlue flex relative w-6"
                @click="showSection = !showSection">
            <ChevronUpIcon v-if="showSection"
                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
            <ChevronDownIcon v-else
                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
        </button>
        <div class="flex flex-wrap w-11/12 border border-2 border-gray-300">
            <div :class="showSection ? 'mt-10 mb-5': 'my-10'" class="flex justify-between w-full ml-12">
                <div class="flex headline2 ">
                    {{ name }}
                    <div v-if="notifications && !showSection" :class="notifications.length <= 9 ? 'px-2' : ''"
                         class="ml-4 flex font-semibold items-center p-1 border-tagText border text-tagText bg-backgroundBlue xxsLight rounded-lg">
                        {{ notifications.length }}
                    </div>
                </div>
                <div @click="setAllOnRead(notifications)"
                     class="flex cursor-pointer items-center justify-end linkText mr-8">
                    <img src="/Svgs/IconSvgs/icon_archive_blue.svg"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>alle archivieren
                </div>
            </div>
            <div v-if="showSection" @mouseover="notification.hovered = true" @mouseleave="notification.hovered = false"
                 :class="index !== 0 && showSection ? 'border-t-2' : ''"
                 class="flex flex-wrap justify-between mx-12 w-full py-6"
                 v-for="(notification,index) in notifications">
                <div class="flex flex-wrap w-full justify-between">
                    <div class="flex">
                        <!-- Notification Icon -->
                        <TeamIconCollection v-if="notification.data.team" class="h-12 w-12 mr-5"
                                            :iconName=notification.data.team.svg_name
                                            alt="TeamIcon"/>
                        <img alt="Notification" v-else-if="!isErrorType(notification.type,notification)"
                             class="h-12 w-12 mr-5" src="/Svgs/IconSvgs/icon_notification_green.svg"/>
                        <img alt="Notification" v-else class="h-12 w-12 mr-5"
                             src="/Svgs/IconSvgs/icon_notification_red.svg"/>
                        <!-- Div with Content -->
                        <div class="flex-col flex my-auto w-full">
                            <!-- 1st Row of Notification -->
                            <div class="flex w-full">
                                <div class="sDark" v-if="isErrorType(notification.type,notification) && notification.data.title.indexOf('Neue Raumanfrage') !== -1">
                                    Erinnerung: {{ notification.data.title }}
                                </div>
                                <div class="sDark" v-else>
                                    {{ notification.data.title }}
                                </div>
                                <div v-if="notification.data.title === 'Termin geändert'"
                                     class="xxsLight ml-4 cursor-pointer items-center flex text-buttonBlue" @click="openEventHistoryModal(notification.data.eventHistory)">
                                    <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
                                    Verlauf ansehen
                                </div>
                                <div class="ml-6 mt-1 flex xxsLight my-auto"
                                     v-if="notification.type === 'App\\Notifications\\RoomRequestNotification' || notification.data.title === 'Termin abgesagt'">
                                    {{ this.formatDate(notification.created_at) }}
                                    von
                                    <NotificationUserIcon
                                        :user="notification.data.created_by"></NotificationUserIcon>
                                </div>
                                <div class="ml-4 mt-1 flex xxsLight my-auto"
                                     v-if="notification.data.title === 'Terminkonflikt'">
                                    Konflikttermin belegt:
                                    {{ this.formatDate(notification.data.conflict.event.created_at) }} von
                                    <NotificationUserIcon v-if="notification.data.conflict.created_by"
                                                          :user="notification.data.conflict.created_by"></NotificationUserIcon>
                                </div>
                                <div class="ml-4 mt-1 flex xxsLight my-auto"
                                     v-if="notification.data.type === 'NOTIFICATION_LOUD_ADJOINING_EVENT'">
                                    Termin belegt:
                                    {{ this.formatDate(notification.data.conflict.created_at) }} von
                                    <NotificationUserIcon v-if="notification.data.created_by"
                                                          :user="notification.data.created_by"></NotificationUserIcon>
                                </div>

                            </div>
                            <!-- 2nd Row of Notification-->
                            <NotificationEventInfoRow
                                v-if="notification.type === 'App\\Notifications\\EventNotification' || notification.type.indexOf('RoomRequestNotification') !== -1 || notification.data.type === 'NOTIFICATION_CONFLICT'|| notification.data.type === 'NOTIFICATION_LOUD_ADJOINING_EVENT'"
                                :declinedRoomId="notification.data.accepted ? null : notification.data.event?.declined_room_id"
                                :projects="projects"
                                :event="notification.data.conflict?.event ? notification.data.conflict.event : notification.data.conflict ? notification.data.conflict : notification.data.event"
                                :rooms="this.rooms"
                                :eventTypes="this.eventTypes"></NotificationEventInfoRow>
                            <NotificationBudgetRequest
                                v-if="notification.type === 'App\\Notifications\\BudgetVerified'"
                            :budget="notification.data">

                            </NotificationBudgetRequest>
                            <div class="flex">
                                <div class="mt-1.5 flex xxsLight my-auto"
                                     v-if="notification.type === 'App\\Notifications\\DeadlineNotification'">
                                    {{ this.formatDate(notification.data.task.deadline) }}
                                </div>
                                <Link :href="route('tasks.own')"
                                      v-if="notification.data.title.indexOf('neue Aufgaben') !== -1 || notification.type === 'App\\Notifications\\DeadlineNotification'"
                                      :class="notification.type === 'App\\Notifications\\DeadlineNotification' ? 'ml-4' : ''"
                                      class="xxsLight mt-1.5 cursor-pointer items-center flex text-buttonBlue">
                                    in Aufgaben ansehen
                                </Link>
                            </div>
                            <div class="mt-1.5 flex xxsLight my-auto"
                                 v-if="notification.data.type === 'NOTIFICATION_TEAM' || notification.data.type === 'NOTIFICATION_PROJECT' || notification.data.type === 'NOTIFICATION_ROOM_CHANGED' || notification.data.type === 'NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED'" >
                                <div v-if="notification.data.type === 'NOTIFICATION_ROOM_CHANGED'" @click="openRoomHistoryModal(notification.data.history)"
                                     class="xxsLight cursor-pointer items-center flex text-buttonBlue">
                                    <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
                                    Verlauf ansehen
                                </div>
                                <div class="flex" v-else>
                                    {{ this.formatDate(notification.created_at) }}
                                    von
                                    <NotificationUserIcon v-if="notification.data.created_by"
                                                          :user="notification.data.created_by"></NotificationUserIcon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="notification.type.indexOf('RoomRequestNotification') !== -1"
                        class="flex w-full ml-16 mt-1">
                        <div class="flex" v-if="notification.data.title.indexOf('Neue Raumanfrage') !== -1">
                            <AddButton
                                @click="openAnswerEventRequestModal(notification, notification.data.event,'accept')"
                                class="flex px-12"
                                text="Anfrage bestätigen" mode="modal"/>
                            <AddButton
                                @click="openAnswerEventRequestModal(notification, notification.data.event,'decline')"
                                type="secondary"
                                text="Anfrage ablehnen"></AddButton>
                            <AddButton
                                @click="openAnswerRequestWithRoomChangeModal(notification, notification.data.event, notification.data.created_by)"
                                type="secondary"
                                text="Raum ändern"></AddButton>
                        </div>
                        <div class="flex" v-else-if="notification.data.title.indexOf('Raumanfrage mit Raumänderung bestätigt') === -1">
                            <AddButton @click="openEventWithoutRoomComponent(notification.data.event, notification)"
                                       class="flex px-12"
                                       text="Anfrage ändern" mode="modal"/>
                            <AddButton @click="openDeleteEventModal(notification.data.event, notification)" type="secondary"
                                       text="Termin löschen"></AddButton>
                        </div>
                    </div>
                    <!-- Archive Button -->
                    <img v-else @click="setOnRead(notification.id)" v-show="notification.hovered"
                         src="/Svgs/IconSvgs/icon_archive_white.svg"
                         class="h-6 w-6 p-1 ml-1 flex cursor-pointer bg-buttonBlue rounded-full"
                         aria-hidden="true"/>
                </div>
            </div>
            <div @click="showReadSection = true" v-if="showSection && !showReadSection"
                 class="ml-12 my-6 linkText cursor-pointer">
                alte Benachrichtigungen ansehen
            </div>
            <div class="flex justify-between items-center w-full ml-12 mt-8 xsDark" v-if="showReadSection">
                <div :class="!readNotifications ? 'mb-12' : ''" class="flex items-center">
                    <img src="/Svgs/IconSvgs/icon_archive_black.svg"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>
                    Archiv
                </div>
                <div @click="showReadSection = false" v-if="showReadSection" class="mr-12 linkText cursor-pointer">
                    Archiv schließen
                </div>
            </div>
            <div v-if="showReadSection" @mouseover="notification.hovered = true"
                 @mouseleave="notification.hovered = false" :class="index !== 0 && showSection ? 'border-t-2' : ''"
                 class="flex flex-wrap justify-between mx-12 w-full py-6"
                 v-for="(notification,index) in readNotifications">
                <div class="flex flex-wrap">
                    <div class="flex">
                        <!-- Notification Icon -->
                        <TeamIconCollection v-if="notification.data.team" class="h-12 w-12 mr-5"
                                            :iconName=notification.data.team.svg_name
                                            alt="TeamIcon"/>
                        <img alt="Notification" v-else-if="!isErrorType(notification.type,notification)"
                             class="h-12 w-12 mr-5" src="/Svgs/IconSvgs/icon_notification_green.svg"/>
                        <img alt="Notification" v-else class="h-12 w-12 mr-5"
                             src="/Svgs/IconSvgs/icon_notification_red.svg"/>
                        <!-- Div with Content -->
                        <div class="flex-col flex my-auto w-full">
                            <!-- 1st Row of Notification -->
                            <div class="flex w-full">
                                <div class="sDark">
                                    {{ notification.data.title }}
                                </div>
                                <div v-if="notification.data.title === 'Termin geändert'"
                                     class="xxsLight ml-4 cursor-pointer items-center flex text-buttonBlue" @click="openEventHistoryModal(notification.data.eventHistory)">
                                    <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
                                    Verlauf ansehen
                                </div>
                                <div class="ml-6 mt-1 flex xxsLight my-auto"
                                     v-if="notification.type === 'App\\Notifications\\RoomRequestNotification' || notification.data.title === 'Termin abgesagt'">
                                    {{ this.formatDate(notification.created_at) }}
                                    von
                                    <NotificationUserIcon
                                        :user="notification.data.created_by"></NotificationUserIcon>
                                </div>
                                <div class="ml-4 mt-1 flex xxsLight my-auto"
                                     v-if="notification.data.title === 'Terminkonflikt'">
                                    Konflikttermin belegt:
                                    {{ this.formatDate(notification.data.conflict.created_at) }} von
                                    <NotificationUserIcon v-if="notification.data.conflict.created_by"
                                                          :user="notification.data.conflict.created_by"></NotificationUserIcon>
                                </div>
                                <div class="ml-4 mt-1 flex xxsLight my-auto"
                                     v-if="notification.data.type === 'NOTIFICATION_LOUD_ADJOINING_EVENT'">
                                    Termin belegt:
                                    {{ this.formatDate(notification.data.conflict.created_at) }} von
                                    <NotificationUserIcon v-if="notification.data.created_by"
                                                          :user="notification.data.created_by"></NotificationUserIcon>
                                </div>

                            </div>
                            <!-- 2nd Row of Notification-->
                            <NotificationEventInfoRow
                                v-if="notification.type === 'App\\Notifications\\EventNotification' || notification.type.indexOf('RoomRequestNotification') !== -1 || notification.data.type === 'NOTIFICATION_CONFLICT'|| notification.data.type === 'NOTIFICATION_LOUD_ADJOINING_EVENT'"
                                :declinedRoomId="notification.data.accepted ? null : notification.data.event?.declined_room_id"
                                :projects="projects"
                                :event="notification.data.conflict?.event ? notification.data.conflict.event : notification.data.conflict ? notification.data.conflict : notification.data.event"
                                :rooms="this.rooms"
                                :eventTypes="this.eventTypes"></NotificationEventInfoRow>
                            <div class="flex">
                                <div class="mt-1.5 flex xxsLight my-auto"
                                     v-if="notification.type === 'App\\Notifications\\DeadlineNotification'">
                                    {{ this.formatDate(notification.data.task.deadline) }}
                                </div>
                                <Link :href="route('tasks.own')"
                                      v-if="notification.data.title.indexOf('neue Aufgaben') !== -1 || notification.type === 'App\\Notifications\\DeadlineNotification'"
                                      :class="notification.type === 'App\\Notifications\\DeadlineNotification' ? 'ml-4' : ''"
                                      class="xxsLight mt-1.5 cursor-pointer items-center flex text-buttonBlue">
                                    in Aufgaben ansehen
                                </Link>
                            </div>
                            <div class="mt-1.5 flex xxsLight my-auto"
                                 v-if="notification.data.type === 'NOTIFICATION_TEAM' || notification.data.type === 'NOTIFICATION_PROJECT' || notification.data.type === 'NOTIFICATION_ROOM_CHANGED' || notification.data.type === 'NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED'">
                                <div v-if="notification.data.type === 'NOTIFICATION_ROOM_CHANGED'" @click="openRoomHistoryModal(notification.data.history)"
                                     class="xxsLight cursor-pointer items-center flex text-buttonBlue">
                                    <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
                                    Verlauf ansehen
                                </div>
                                <div class="flex" v-else>
                                    {{ this.formatDate(notification.created_at) }}
                                    von
                                    <NotificationUserIcon v-if="notification.data.created_by"
                                                          :user="notification.data.created_by"></NotificationUserIcon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <event-without-room-new-request-component
        v-if="showEventWithoutRoomComponent"
        @closed="onEventWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :event="this.eventToEdit"
        :projects="this.projects"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
    />
    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        confirm="Löschen"
        titel="Termin löschen"
        :description="'Bist du sicher, dass du den Termin ' + this.eventToDelete.eventName + ' in den Papierkorb legen möchtest? Du kannst ihn innerhalb von 30 Tagen wiederherstellen.'"
        @closed="afterConfirm"/>
    <!-- Raumbelegungsanfrage beantworten Modal -->
    <AnswerEventRequestComponent
        v-if="answerRequestModalVisible"
        :type="answerRequestType"
        :request="requestToAnswer"
        :rooms="this.rooms"
        :eventTypes="this.eventTypes"
        :projects="this.projects"
        @closed="afterRequestAnswer"/>
    <!-- Raumbelegungsanfrage beantworten mit Raumänderung Modal -->
    <AnswerEventRequestWithRoomChangeComponent
        v-if="answerRequestWithRoomChangeVisible"
        :request="requestToAnswerWithRoomChange"
        :rooms="this.rooms"
        :creator="this.creatorOfRequest"
        :eventTypes="this.eventTypes"
        :projects="this.projects"
        @closed="afterRequestAnswerWithRoomChange"/>
    <!-- Room History Modal-->
    <room-history-component
        v-if="showRoomHistory"
        :room_history="wantedHistory"
        @closed="closeRoomHistoryModal"
    />
    <event-history-component
        v-if="showEventHistory"
        :event_history="wantedHistory"
        @closed="closeEventHistoryModal"
    />
</template>

<script>


import {ChevronDownIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {CheckIcon, ChevronRightIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import AddButton from "@/Layouts/Components/AddButton";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon";
import EventWithoutRoomNewRequestComponent from "@/Layouts/Components/EventWithoutRoomNewRequestComponent";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent";
import AnswerEventRequestWithRoomChangeComponent from "@/Layouts/Components/AnswerEventRequestWithRoomChangeComponent";
import RoomHistoryComponent from "@/Layouts/Components/RoomHistoryComponent";
import EventHistoryComponent from "@/Layouts/Components/EventHistoryComponent";
import NotificationBudgetRequest from "@/Layouts/Components/NotificationBudgetRequest.vue";

export default  {
    name: 'NotificationSectionComponent',

    components: {
        NotificationBudgetRequest,
        TeamIconCollection,
        EventTypeIconCollection,
        ChevronDownIcon,
        ChevronUpIcon,
        ConfirmationComponent,
        AddButton,
        NotificationEventInfoRow,
        ChevronRightIcon,
        NotificationUserIcon,
        EventWithoutRoomNewRequestComponent,
        Link,
        AnswerEventRequestComponent,
        AnswerEventRequestWithRoomChangeComponent,
        RoomHistoryComponent,
        EventHistoryComponent
    },

    data() {
        return {
            showSection: true,
            showReadSection: false,
            eventToEdit: null,
            showEventWithoutRoomComponent: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            answerRequestModalVisible: false,
            requestToAnswer: null,
            answerRequestType: '',
            showRoomHistory: false,
            wantedHistory: null,
            answerRequestWithRoomChangeVisible: false,
            requestToAnswerWithRoomChange: null,
            creatorOfRequest: null,
            showEventHistory: false,
            notificationToDelete: null,
            answerRequestForm: useForm({
                accepted: false,
            }),
            setOnReadForm: this.$inertia.form({
                _method: 'PATCH',
                notificationId: null
            })
        }
    },
    props: ['eventTypes', 'rooms', 'notifications', 'readNotifications', 'projects', 'name'],
    methods: {
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
        isErrorType(type, notification) {
            if ((type.indexOf('RoomRequestNotification') !== -1 && this.isDateSoon(notification.data.event.start_time)) && notification.data.accepted === false || type.indexOf('ConflictNotification') !== -1 || notification.data.title === 'Termin abgesagt' || type.indexOf('DeadlineNotification') !== -1 || notification.data.title.indexOf('gelöscht') !== -1) {
                return true;
            }
            return false;
        },
        isDateSoon(date){
            const dateTemp = new Date(date);
            const threeDaysInMillis = 1000 * 60 * 60 * 24 * 3;
            const threeDaysFromNow = Date.now() + threeDaysInMillis;
            return dateTemp < threeDaysFromNow;

        },
        openRoomHistoryModal(history){
            this.wantedHistory = history;
            this.showRoomHistory = true;
        },
        openEventHistoryModal(history){
          this.wantedHistory = history;
          this.showEventHistory = true;
        },
        closeEventHistoryModal(){
            this.showEventHistory = false;
            this.wantedHistory = null
        },
        closeRoomHistoryModal(){
            this.showRoomHistory = false;
            this.wantedRoom = null;
        },
        openEventWithoutRoomComponent(event,notification) {
            this.eventToEdit = event;
            this.notificationToDelete = notification;
            this.showEventWithoutRoomComponent = true;
        },
        onEventWithoutRoomComponentClose() {
            this.showEventWithoutRoomComponent = false;
            this.deleteNotification();

        },
        openDeleteEventModal(event, notification) {
            this.eventToDelete = event;
            this.notificationToDelete = notification;
            this.deleteComponentVisible = true;
        },
        setOnRead(notificationId) {
            this.setOnReadForm.notificationId = notificationId;
            this.setOnReadForm.patch(route('notifications.setReadAt'));
        },
        setAllOnRead(notifications) {
            notifications.forEach((notification) => {
                if ((!this.isErrorType(notification.type, notification)  || notification.type.indexOf('RoomRequestNotification') === -1) && notification.data.title.indexOf('Neue Raumanfrage') === -1) {
                    this.setOnRead(notification.id);
                }
            })
        },
        openAnswerEventRequestModal(notification, event, type) {
            this.requestToAnswer = event;
            this.answerRequestType = type;
            this.answerRequestModalVisible = true;
            this.notificationToDelete = notification;
        },
        openAnswerRequestWithRoomChangeModal(notification, event, creator) {
            this.creatorOfRequest = creator;
            this.requestToAnswerWithRoomChange = event;
            this.answerRequestWithRoomChangeVisible = true;
            this.notificationToDelete = notification
        },
        async afterRequestAnswer(bool) {
            if (!bool) {
                return this.answerRequestModalVisible = false;
            }
            await this.deleteNotification();

            if (this.answerRequestType === 'accept') {
                this.answerRequestForm.accepted = true;
            } else if (this.answerRequestType === 'decline') {
                this.answerRequestForm.accepted = false;
            }
            this.answerRequestForm.put(route('events.accept', {event: this.requestToAnswer.id}));
            this.answerRequestModalVisible = false;
        },
        async afterRequestAnswerWithRoomChange(bool) {
            if (!bool) {
                return this.answerRequestWithRoomChangeVisible = false;
            }
            await this.deleteNotification();

            return await axios
                .put('/events/' + this.requestToAnswerWithRoomChange.id, this.eventData(this.requestToAnswerWithRoomChange))
                .then(() => this.answerRequestWithRoomChangeVisible = false)
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            await this.deleteNotification();

            return await axios
                .delete(`/events/${this.eventToDelete.id}`)
                .then(() => this.deleteComponentVisible = false)
        },
        async deleteNotification() {
            const notification = this.notificationToDelete
            await axios.delete(`/notifications/${notification.id}/`)
                .catch(err => console.log(err))
            this.notificationToDelete = null
        },

        eventData(event) {
            return {
                title: event.title,
                eventName: event.eventName,
                start: event.start_time,
                end: event.end_time,
                roomId: event.room_id,
                description: event.description,
                audience: event.audience,
                isLoud: event.is_loud,
                eventNameMandatory: false,
                projectId: event.project_id,
                projectName: event.creatingProject ? event.projectName : '',
                eventTypeId: event.event_type_id,
                projectIdMandatory: false,
                creatingProject: false,
                roomChange: true,
            };
        },
    },
}
</script>

<style scoped></style>
