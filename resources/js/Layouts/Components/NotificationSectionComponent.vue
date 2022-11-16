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
                    {{name}}
                    <div v-if="notifications && !showSection" :class="notifications.length <= 9 ? 'px-2' : ''" class="ml-4 flex font-semibold items-center p-1 border-tagText border text-tagText bg-backgroundBlue xxsLight rounded-lg">
                    {{notifications.length}}
                    </div>
                </div>
                <div class="flex items-center justify-end linkText mr-8">
                    <img src="/Svgs/IconSvgs/icon_archive_blue.svg"
                         class="h-4 w-4 mr-2"
                         aria-hidden="true"/>alle archivieren
                </div>
            </div>
            <div v-if="showSection" @mouseover="notification.hovered = true" @mouseleave="notification.hovered = false" :class="index !== 0 && showSection ? 'border-t-2' : ''" class="flex flex-wrap justify-between mx-12 w-full py-6"
                 v-for="(notification,index) in notifications">
                {{notification}}
                    <div class="flex flex-wrap">
                        <div class="flex">
                            <!-- Notification Icon -->
                            <TeamIconCollection v-if="notification.data.team" class="h-12 w-12 mr-5" :iconName=notification.data.team.svg_name
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
                                         class="xxsLight ml-4 cursor-pointer items-center flex text-buttonBlue">
                                        <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
                                        Verlauf ansehen
                                    </div>
                                    <div class="ml-4 mt-1 flex xxsLight my-auto"
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
                                    v-if="notification.type === 'App\\Notifications\\EventNotification' || notification.data.type === 'ROOM_REQUEST' || notification.data.type === 'NOTIFICATION_CONFLICT'|| notification.data.type === 'NOTIFICATION_LOUD_ADJOINING_EVENT'"
                                    :declinedRoomId="notification.data.accepted ? null : notification.data.event?.declined_room_id"
                                    :projects="projects"
                                    :event="notification.data.conflict?.event ? notification.data.conflict.event : notification.data.conflict ? notification.data.conflict : notification.data.event"
                                    :rooms="this.rooms"
                                    :eventTypes="this.eventTypes"></NotificationEventInfoRow>
                                <Link :href="route('tasks.own')"
                                      v-else-if="notification.data.title.indexOf('neue Aufgaben') !== -1"
                                      class="xxsLight mt-1.5 cursor-pointer items-center flex text-buttonBlue">
                                    in Aufgaben ansehen
                                </Link>
                                <div class="mt-1.5 flex xxsLight my-auto"
                                     v-if="notification.data.type === 'NOTIFICATION_TEAM' || notification.data.type === 'NOTIFICATION_PROJECT' || notification.data.type === 'NOTIFICATION_ROOM_CHANGED'">
                                    <div v-if="notification.data.title.indexOf('Es gab Änderungen an') !== -1"
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
                            v-if="isErrorType(notification.type,notification) && notification.type.indexOf('RoomRequestNotification') !== -1"
                            class="flex w-full ml-16 mt-1">
                            <AddButton @click="openEventWithoutRoomComponent(notification.data.event)" class="flex px-12"
                                       text="Anfrage ändern" mode="modal"/>
                            <AddButton @click="openDeleteEventModal(notification.data.event)" type="secondary"
                                       text="Termin löschen"></AddButton>
                        </div>
                    </div>
                <!-- Archive Button -->
                <img @click="setOnRead(notification.id)" v-show="notification.hovered" src="/Svgs/IconSvgs/icon_archive_white.svg"
                     class="h-6 w-6 p-1 ml-1 flex cursor-pointer bg-buttonBlue rounded-full"
                     aria-hidden="true"/>

            </div>
            <div v-if="showSection" class="ml-12 my-6 linkText">
                alte Benachrichtigungen ansehen
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
</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {CheckIcon, ChevronRightIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import AddButton from "@/Layouts/Components/AddButton";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon";
import EventWithoutRoomNewRequestComponent from "@/Layouts/Components/EventWithoutRoomNewRequestComponent";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link} from "@inertiajs/inertia-vue3";

export default {
    name: 'NotificationSectionComponent',

    components: {
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
        Link
    },

    data() {
        return {
            showSection: true,
            eventToEdit: null,
            showEventWithoutRoomComponent: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            setOnReadForm: this.$inertia.form({
                _method: 'PATCH',
                notificationId: null
            })
        }
    },
    props: ['eventTypes', 'rooms', 'notifications', 'projects','name'],
    methods: {
        formatDate(isoDate) {
            return isoDate.split('T')[0].substring(8, 10) + '.' + isoDate.split('T')[0].substring(5, 7) + '.' + isoDate.split('T')[0].substring(0, 4) + ', ' + isoDate.split('T')[1].substring(0, 5)
        },
        isErrorType(type, notification) {
            if (type.indexOf('RoomRequestNotification') !== -1 && notification.data.accepted === false || type.indexOf('ConflictNotification') !== -1 || notification.data.title === 'Termin abgesagt') {
                return true;
            }
            return false;
        },
        openEventWithoutRoomComponent(event){
            this.eventToEdit = event;
            this.showEventWithoutRoomComponent = true;
        },
        onEventWithoutRoomComponentClose() {
            this.showEventWithoutRoomComponent = false;
        },
        openDeleteEventModal(event) {
            this.eventToDelete = event;
            this.deleteComponentVisible = true;
        },
        setOnRead(notificationId){
            this.setOnReadForm.notificationId = notificationId;
            this.setOnReadForm.patch(route('notifications.setReadAt'));
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            // TODO: HIER NOCH NOTIFICATION AUF READ_AT SETZEN

            return await axios
                .delete(`/events/${this.eventToDelete.id}`)
                .then(() => this.closeModal());
        },
    },
}
</script>

<style scoped></style>
