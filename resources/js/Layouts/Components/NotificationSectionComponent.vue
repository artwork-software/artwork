<template>
    <div class="flex w-full mb-5">
        <button class="bg-artwork-buttons-create flex relative w-6" @click="showSection = !showSection">
            <ChevronUpIcon v-if="showSection" class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
            <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
        </button>
        <div class="border border-2 border-gray-300 px-10 w-full">
            <div :class="showSection ? 'mt-10 mb-5': 'my-10'" class="flex justify-between w-full">
                <div class="flex headline2 ">
                    {{ name }}
                    <div v-if="notifications && !showSection" :class="notifications.length <= 9 ? '' : ''"
                         class="ml-4 flex font-semibold items-center p-1 border-tagText border text-tagText bg-backgroundBlue xxsLight rounded-lg">
                        {{ notifications.length }}
                    </div>
                </div>
                <div @click="setAllOnRead()"
                     class="flex cursor-pointer items-center justify-end linkText mr-8">
                    <img src="/Svgs/IconSvgs/icon_archive_blue.svg"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>{{$t('Archive all')}}
                </div>
            </div>
            <div v-if="showSection" @mouseover="notification.hovered = true" @mouseleave="notification.hovered = false"
                 :class="index !== 0 && showSection ? 'border-t-2 mb-2 mt-3' : ''"
                 class=""
                 v-for="(notification,index) in notifications">
                <NotificationBlock
                    :notification="notification"
                    :event-types="eventTypes"
                    :history-objects="historyObjects"
                    :event="event"
                    :rooms="rooms"
                    :room-collisions="roomCollisions"
                    :project="project"
                    :wanted-split="wantedSplit"
                    :isArchive="false"
                    :first_project_shift_tab_id="first_project_shift_tab_id"
                    :first_project_budget_tab_id="first_project_budget_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                />
            </div>
            <div @click="showReadSection = true" v-if="showSection && !showReadSection"
                 class="ml-12 my-6 linkText cursor-pointer">
                {{ $t('View old notifications')}}
            </div>
            <div class="flex justify-between items-center w-full ml-12 mt-8 xsDark" v-if="showReadSection">
                <div :class="!readNotifications ? 'mb-12' : ''" class="flex items-center">
                    <img src="/Svgs/IconSvgs/icon_archive_black.svg"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>
                    {{$t('Archive')}}
                </div>
                <div @click="showReadSection = false" v-if="showReadSection" class="mr-12 linkText cursor-pointer">
                    {{$t('Close archive')}}
                </div>
            </div>
            <div v-if="showReadSection" @mouseover="notification.hovered = true"
                 @mouseleave="notification.hovered = false" :class="index !== 0 && showSection ? 'border-t-2' : ''"
                 class=" w-full"
                 v-for="(notification,index) in readNotifications">
                <NotificationBlock
                    :notification="notification"
                    :event-types="eventTypes"
                    :history-objects="historyObjects"
                    :event="event"
                    :rooms="rooms"
                    :room-collisions="roomCollisions"
                    :project="project"
                    :wanted-split="wantedSplit"
                    :isArchive="true"
                    :first_project_shift_tab_id="first_project_shift_tab_id"
                    :first_project_budget_tab_id="first_project_budget_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                />
            </div>
        </div>
    </div>
    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        :confirm="$t('Delete')"
        :titel="$t('Delete event')"
        :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [this.eventToDelete.eventName])"
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
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
        @closed="afterRequestAnswerWithRoomChange"
    />
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
import {ChevronRightIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow.vue";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent.vue";
import AnswerEventRequestWithRoomChangeComponent from "@/Layouts/Components/AnswerEventRequestWithRoomChangeComponent.vue";
import RoomHistoryComponent from "@/Layouts/Components/RoomHistoryComponent.vue";
import EventHistoryComponent from "@/Layouts/Components/EventHistoryComponent.vue";
import NotificationPublicChangesInfo from "@/Layouts/Components/NotificationPublicChangesInfo.vue";
import NotificationBlock from "@/Layouts/Components/NotificationComponents/NotificationBlock.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default  {
    name: 'NotificationSectionComponent',
    mixins: [Permissions],
    components: {
        NotificationBlock,
        NotificationPublicChangesInfo,
        TeamIconCollection,
        ChevronDownIcon,
        ChevronUpIcon,
        ConfirmationComponent,
        NotificationEventInfoRow,
        ChevronRightIcon,
        NotificationUserIcon,
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
            }),
            setOnReadAll: useForm({
                notificationIds: [],
            })
        }
    },
    props: [
        'eventTypes',
        'rooms',
        'notifications',
        'readNotifications',
        'projects',
        'name',
        'historyObjects',
        'event',
        'project',
        'wantedSplit',
        'roomCollisions',
        'first_project_shift_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id'
    ],
    methods: {
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
        isErrorType(type, notification) {
            if ((type.indexOf('RoomRequestNotification') !== -1 &&
                    this.isDateSoon(notification.data.event.start_time)) &&
                    notification.data.accepted === false || type.indexOf('ConflictNotification') !== -1 ||
                    notification.data.title === 'Termin abgesagt' ||
                    type.indexOf('DeadlineNotification') !== -1 ||
                    notification.data.title.indexOf('gelöscht') !== -1
            ) {
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
        closeEventHistoryModal(){
            this.showEventHistory = false;
            this.wantedHistory = null
        },
        closeRoomHistoryModal(){
            this.showRoomHistory = false;
            this.wantedRoom = null;
        },
        openDeleteEventModal(event, notification) {
            this.eventToDelete = event;
            this.notificationToDelete = notification;
            this.deleteComponentVisible = true;
        },
        setOnRead(notificationId) {
            this.setOnReadForm.notificationId = notificationId;
            this.setOnReadForm.patch(route('notifications.setReadAt'), {
                preserveScroll: true,
            });
        },
        setAllOnRead() {
            // filter all notifications that icon is not blue
            const notifications = this.notifications.filter(notification => notification.data.icon !== 'blue');

            // get all notification ids and push it in set on read all form
            notifications.forEach(notification => {
                this.setOnReadAll.notificationIds.push(notification.id);
            });

            this.setOnReadAll.patch(route('notifications.setReadAtAll'), {
                preserveScroll: true,
            });
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
