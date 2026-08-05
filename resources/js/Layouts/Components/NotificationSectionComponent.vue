<template>
    <div class="flex w-full mb-5 gap-2">
        <button class="bg-text-subtle rounded-lg flex relative w-6" @click="showSection = !showSection">
            <IconChevronUp v-if="showSection" class="h-6 w-6 text-white my-auto"></IconChevronUp>
            <IconChevronDown v-else class="h-6 w-6 text-white my-auto"></IconChevronDown>
        </button>
        <div class="border-2 border-border rounded-lg px-10 w-full">
            <div :class="showSection ? 'mt-10 mb-5': 'my-10'" class="flex justify-between w-full">
                <div class="flex font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text ">
                    {{ name }}
                    <div v-if="!showSection && displayUnreadCount > 0"
                         class="ml-4 flex font-semibold items-center p-1  border   text-xs/[18px] text-text-subtle rounded-lg">
                        {{ displayUnreadCount }}
                    </div>
                </div>
                <div @click="setAllOnRead()"
                     class="flex cursor-pointer items-center justify-end text-xs/[18px] text-accent-600 mr-8">
                    <img src="/Svgs/IconSvgs/icon_archive_blue.svg"
                         alt="Archive icon"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>{{$t('Archive all')}}
                </div>
            </div>
            <div v-if="showSection"
                 @mouseover="notification.hovered = true"
                 @mouseleave="notification.hovered = false"
                 :class="index !== 0 && showSection ? 'border-t-2 mb-2 mt-3' : ''"
                 class=""
                 v-for="(notification,index) in unread.items" :key="notification.id">
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
                    :event-statuses="eventStatuses"
                />
            </div>
            <div v-if="showSection && unread.loading && unread.items.length === 0"
                 class="ml-12 my-6 text-sm/5 font-bold text-text-subtle">
                {{ $t('Loading...') }}
            </div>
            <div v-if="showSection && !unread.loading && unread.items.length === 0 && displayUnreadCount === 0"
                 class="ml-12 my-6 text-sm/5 font-bold text-text-subtle">
                {{ $t('No new notifications') }}
            </div>
            <div v-if="showSection && unread.items.length < unread.total"
                 @click="loadMoreUnread"
                 class="ml-12 my-4 text-xs/[18px] text-accent-600 cursor-pointer">
                {{ $t('Show more') }} ({{ unread.items.length }}/{{ unread.total }})
            </div>
            <div @click="openArchive" v-if="showSection && !showReadSection"
                 class="ml-12 my-6 text-xs/[18px] text-accent-600 cursor-pointer">
                {{ $t('View old notifications')}}
            </div>
            <div class="flex justify-between items-center w-full mt-8 text-sm/5 font-semibold text-text border-t-2 pt-4 pl-4 pr-4" v-if="showReadSection">
                <div :class="archived.items.length === 0 ? 'mb-12' : ''" class="flex items-center">
                    <img src="/Svgs/IconSvgs/icon_archive_black.svg"
                         alt="Archive icon black"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>
                    {{$t('Archive')}}
                </div>
                <div @click="showReadSection = false" v-if="showReadSection" class="text-xs/[18px] text-accent-600 cursor-pointer">
                    {{$t('Close archive')}}
                </div>
            </div>
            <div v-if="showReadSection" @mouseover="notification.hovered = true"
                 @mouseleave="notification.hovered = false" :class="index !== 0 && showSection ? 'border-t-2' : ''"
                 class=" w-full"
                 v-for="(notification,index) in archived.items" :key="notification.id">
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
            <div v-if="showReadSection && archived.loading && archived.items.length === 0"
                 class="ml-12 my-4 text-sm/5 font-bold text-text-subtle">
                {{ $t('Loading...') }}
            </div>
            <div v-if="showReadSection && !archived.loading && archived.items.length === 0"
                 class="ml-12 my-4 text-sm/5 font-bold text-text-subtle">
                {{ $t('No archived notifications') }}
            </div>
            <div v-if="showReadSection && archived.items.length < archived.total"
                 @click="loadMoreArchived"
                 class="ml-12 my-4 text-xs/[18px] text-accent-600 cursor-pointer">
                {{ $t('Show more') }} ({{ archived.items.length }}/{{ archived.total }})
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
import {IconChevronDown, IconChevronRight, IconChevronUp} from "@tabler/icons-vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow.vue";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent.vue";
import AnswerEventRequestWithRoomChangeComponent
    from "@/Layouts/Components/AnswerEventRequestWithRoomChangeComponent.vue";
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
        IconChevronDown,
        IconChevronUp,
        ConfirmationComponent,
        NotificationEventInfoRow,
        IconChevronRight,
        NotificationUserIcon,
        Link,
        AnswerEventRequestComponent,
        AnswerEventRequestWithRoomChangeComponent,
        RoomHistoryComponent,
        EventHistoryComponent
    },
    data() {
        return {
            perPage: 20,
            unread: { items: [], page: 0, total: 0, lastPage: 1, loading: false },
            archived: { items: [], page: 0, total: 0, lastPage: 1, loading: false },
            archiving: false,
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
        }
    },
    props: [
        'eventTypes',
        'rooms',
        'groupType',
        'unreadCount',
        'archivedCount',
        'projects',
        'name',
        'historyObjects',
        'event',
        'project',
        'wantedSplit',
        'roomCollisions',
        'first_project_shift_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id',
        'eventStatuses'
    ],
    computed: {
        // Badge/empty-state count: prefer the locally loaded total (kept in sync after archiving),
        // fall back to the server-provided prop before the first fetch.
        displayUnreadCount() {
            return this.unread.page > 0 ? this.unread.total : (this.unreadCount || 0);
        },
    },
    mounted() {
        if (this.showSection && (this.unreadCount || 0) > 0) {
            this.fetchUnread(1);
        }
    },
    watch: {
        // After a single archive/delete inside a NotificationBlock the whole page is reloaded via
        // Inertia, refreshing these count props. Re-fetch the visible lists so they stay in sync.
        unreadCount() {
            if (this.showSection) {
                this.fetchUnread(1);
            }
        },
        showSection(open) {
            if (open && this.unread.page === 0 && (this.unreadCount || 0) > 0) {
                this.fetchUnread(1);
            }
        },
    },
    methods: {
        async fetchUnread(page = 1) {
            this.unread.loading = true;
            try {
                const { data } = await axios.get(route('notifications.list'), {
                    params: { groupType: this.groupType, status: 'unread', page, perPage: this.perPage },
                });
                this.unread.items = page === 1 ? data.data : this.unread.items.concat(data.data);
                this.unread.page = data.current_page;
                this.unread.lastPage = data.last_page;
                this.unread.total = data.total;
            } catch (err) {
                console.error(err);
            } finally {
                this.unread.loading = false;
            }
        },
        loadMoreUnread() {
            if (!this.unread.loading && this.unread.page < this.unread.lastPage) {
                this.fetchUnread(this.unread.page + 1);
            }
        },
        async fetchArchived(page = 1) {
            this.archived.loading = true;
            try {
                const { data } = await axios.get(route('notifications.list'), {
                    params: { groupType: this.groupType, status: 'archived', page, perPage: this.perPage },
                });
                this.archived.items = page === 1 ? data.data : this.archived.items.concat(data.data);
                this.archived.page = data.current_page;
                this.archived.lastPage = data.last_page;
                this.archived.total = data.total;
            } catch (err) {
                console.error(err);
            } finally {
                this.archived.loading = false;
            }
        },
        loadMoreArchived() {
            if (!this.archived.loading && this.archived.page < this.archived.lastPage) {
                this.fetchArchived(this.archived.page + 1);
            }
        },
        openArchive() {
            this.showReadSection = true;
            this.fetchArchived(1);
        },
        formatDate(isoDate) {
            if(isoDate?.split('T').length > 1){
                return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
            }else if(isoDate?.split(' ').length > 1){
                return isoDate.split(' ')[0].substring(8, 10) + '.' + isoDate.split(' ')[0].substring(5, 7) + '.' + isoDate.split(' ')[0].substring(0, 4) + ', ' + isoDate.split(' ')[1].substring(0, 5)
            }
        },
        isErrorType(type, notification) {
            return (type.indexOf('RoomRequestNotification') !== -1 &&
                    this.isDateSoon(notification.data.event.start_time)) &&
                notification.data.accepted === false || type.indexOf('ConflictNotification') !== -1 ||
                notification.data.title === 'Termin abgesagt' ||
                type.indexOf('DeadlineNotification') !== -1 ||
                notification.data.title.indexOf('gelöscht') !== -1;

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
        async setAllOnRead() {
            if (this.archiving || this.displayUnreadCount === 0) {
                return;
            }
            // The archivable-button filtering happens server-side; we only pass the group so even
            // tens of thousands of notifications are archived in one chunked bulk operation (and
            // offloaded to a queued job above the backend threshold) instead of shipping all ids.
            this.archiving = true;
            try {
                await axios.patch(route('notifications.setReadAtAll'), { groupType: this.groupType });
                await this.fetchUnread(1);
                if (this.showReadSection) {
                    await this.fetchArchived(1);
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.archiving = false;
            }
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
