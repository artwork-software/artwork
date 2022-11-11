<template>
    <app-layout>
        <div class="flex">
            <!-- Greetings Div -->
            <div class="mr-2 w-4/6">
                <div class="ml-12 mt-10">
                    <h2 class="headline1 flex mb-4">Benachrichtungen</h2>
                </div>
            </div>
        </div>
        <div class="ml-12 mt-8">
            <div class="mb-4 border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'notifications' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'notifications'">BENACHRICHTIGUNGEN
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'mailSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'mailSettings'">E-MAIL-EINSTELLUNGEN
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'pushSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold']"
                            @click="openTab = 'pushSettings'">PUSH-EINSTELLUNGEN
                        </button>
                    </li>
                </ul>
            </div>
            <div class="">
                <div class="flex" v-if="openTab === 'notifications'">
                    <button class="bg-buttonBlue flex relative w-6"
                            @click="showRoomsAndEvents = !showRoomsAndEvents">
                        <ChevronUpIcon v-if="showRoomsAndEvents"
                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                        <ChevronDownIcon v-else
                                         class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                    </button>
                    <div class="flex flex-wrap w-11/12 border border-2 border-gray-300">
                        <div class="flex justify-between w-full mb-6 ml-12">
                            <div class="flex headline2 my-10">
                                Raumbelegungen & Termine
                            </div>
                            <div class="flex justify-end xsLight mr-8 my-10">
                                alle archivieren
                            </div>
                        </div>
                        <div
                            v-for="(notificationGroup,type,index) in notifications">
                            <div :class="index !== 0 ? 'border-t-2' : ''"  class="flex flex-wrap mx-12 w-full py-6"
                                 v-if="showRoomsAndEvents" v-for="notification in notificationGroup">
                                <div  class="flex flex-wrap w-full">
                                    <div class="flex w-full">
                                        <img alt="Notification" v-if="!isErrorType(type,notification)"
                                             class="h-12 w-12 mr-5" src="/Svgs/IconSvgs/icon_notification_green.svg"/>
                                        <img alt="Notification" v-else class="h-12 w-12 mr-5"
                                             src="/Svgs/IconSvgs/icon_notification_red.svg"/>
                                        <div class="flex-col flex my-auto">
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
                                                     v-if="type === 'App\\Notifications\\RoomRequestNotification' || notification.data.title === 'Termin abgesagt'">
                                                    {{ this.formatDate(notification.created_at) }}
                                                    von
                                                    <NotificationUserIcon
                                                        :user="notification.data.created_by"></NotificationUserIcon>
                                                </div>
                                                <div class="ml-4 mt-1 flex xxsLight my-auto"
                                                     v-if="notification.data.title === 'Terminkonflikt'">
                                                    Konflikttermin belegt:
                                                    {{ this.formatDate(notification.data.conflict.created_at) }} von
                                                    <NotificationUserIcon
                                                        :user="notification.data.conflict.created_by"></NotificationUserIcon>
                                                </div>
                                            </div>
                                            <NotificationEventInfoRow
                                                :declinedRoomId="notification.data.accepted ? null : notification.data.event?.declined_room_id"
                                                :projects="projects"
                                                :event="notification.data.conflict?.event ? notification.data.conflict.event : notification.data.event"
                                                :rooms="this.rooms"
                                                :eventTypes="this.eventTypes"></NotificationEventInfoRow>
                                        </div>
                                    </div>

                                </div>
                                <div v-if="isErrorType(type,notification) && type.indexOf('RoomRequestNotification') !== -1" class="flex w-full ml-16 mt-1">
                                    <AddButton @click="openEventWithoutRoomComponent(notification.data.event)" class="flex px-12" text="Anfrage ändern" mode="modal"/>
                                    <AddButton @click="openDeleteEventModal(notification.data.event)" type="secondary" text="Termin löschen"></AddButton>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div v-if="openTab === 'mailSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">

                    </p>
                </div>
                <div v-if="openTab === 'pushSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">

                    </p>
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
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AddButton from "@/Layouts/Components/AddButton";
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronDownIcon,
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from '@heroicons/vue/outline'
import {CheckIcon, ChevronRightIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon} from '@heroicons/vue/solid'

import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from '@headlessui/vue'
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {Link} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon";
import EventWithoutRoomNewRequestComponent from "@/Layouts/Components/EventWithoutRoomNewRequestComponent";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";


export default defineComponent({
    components: {
        AddButton,
        TeamIconCollection,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        Link,
        InputComponent,
        EventTypeIconCollection,
        ChevronRightIcon,
        NotificationEventInfoRow,
        NotificationUserIcon,
        EventWithoutRoomNewRequestComponent,
        ConfirmationComponent

    },
    props: ['notifications', 'rooms', 'eventTypes', 'projects'],
    created() {

    },
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
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            // TODO: HIER NOCH NOTIFICATION AUF READ_AT SETZEN

            return await axios
                .delete(`/events/${this.eventToDelete.id}`)
                .then(() => this.closeModal());
        },
    },
    watch: {},
    data() {
        return {
            openTab: 'notifications',
            showRoomsAndEvents: true,
            eventToEdit: null,
            showEventWithoutRoomComponent: false,
            deleteComponentVisible: false,
            eventToDelete: null,
        }
    },
    setup() {
        return {}
    }
})
</script>
