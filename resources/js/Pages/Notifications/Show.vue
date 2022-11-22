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
                <div class="flex flex-wrap" v-if="openTab === 'notifications'">
                    <!-- Raumbelegungen und Termine Notifications -->
                    <NotificationSectionComponent :readNotifications="readNotifications['EVENTS']"
                                                  name="Raumbelegungen & Termine" :rooms="rooms" :projects="projects"
                                                  :event-types="eventTypes"
                                                  :notifications="notifications['EVENTS']"></NotificationSectionComponent>
                    <!-- Räume und Raumbelegungsanfragen -->
                    <NotificationSectionComponent :readNotifications="readNotifications['ROOMS']"
                                                  name="Räume & Raumbelegungsanfragen" :rooms="rooms"
                                                  :projects="projects" :event-types="eventTypes"
                                                  :notifications="notifications['ROOMS']"></NotificationSectionComponent>
                    <!-- Aufgaben -->
                    <NotificationSectionComponent :readNotifications="readNotifications['TASKS']" name="Aufgaben"
                                                  :rooms="rooms" :projects="projects" :event-types="eventTypes"
                                                  :notifications="notifications['TASKS']"></NotificationSectionComponent>
                    <!-- Projekte & Teams -->
                    <NotificationSectionComponent :readNotifications="readNotifications['PROJECTS']"
                                                  name="Projekte & Teams" :rooms="rooms" :projects="projects"
                                                  :event-types="eventTypes"
                                                  :notifications="notifications['PROJECTS']"></NotificationSectionComponent>

                </div>
                <div v-if="openTab === 'mailSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <NotificationFrequencySettings :group-types="groupTypes"
                                                       :notification-frequencies="notificationFrequencies"
                                                       :notificationSettings="notificationSettings"/>
                    </p>
                </div>
                <div v-if="openTab === 'pushSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <NotificationPushSettings :group-types="groupTypes"
                                                  :notificationSettings="notificationSettings"/>
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
import {Link, useForm} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import NotificationEventInfoRow from "@/Layouts/Components/NotificationEventInfoRow";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon";
import EventWithoutRoomNewRequestComponent from "@/Layouts/Components/EventWithoutRoomNewRequestComponent";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import NotificationFrequencySettings from "@/Layouts/Components/NotificationFrequencySettings";
import NotificationSectionComponent from "@/Layouts/Components/NotificationSectionComponent";
import NotificationPushSettings from "@/Layouts/Components/NotificationPushSettings";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent";


export default defineComponent({
    components: {
        NotificationPushSettings,
        NotificationSectionComponent,
        NotificationFrequencySettings,
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
        ConfirmationComponent,
        AnswerEventRequestComponent,

    },
    props: ['notifications', 'rooms', 'eventTypes', 'projects', 'readNotifications', 'notificationSettings', 'notificationFrequencies', 'groupTypes'],
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
        openEventWithoutRoomComponent(event) {
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
            showRoomsAndRoomRequests: true,
            eventToEdit: null,
            showEventWithoutRoomComponent: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            answerRequestModalVisible: false,
            requestToAnswer: null,
            answerRequestType: '',
            answerRequestForm: useForm({
                accepted: false,
            }),
        }
    },
    setup() {
        return {}
    }
})
</script>
