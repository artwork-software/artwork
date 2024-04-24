<template>
    <app-layout>
        <div class="flex">
            <!-- Greetings Div -->
            <div class="mr-2 w-4/6">
                <div class="ml-12 mt-10">
                    <h2 class="headline1 flex mb-4">{{$t('Notifications')}}</h2>
                </div>
            </div>
        </div>
        <div class="ml-12 mt-8">
            <div class="mb-4 border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'notifications' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold uppercase']"
                            @click="openTab = 'notifications'">{{$t('Notifications')}}
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'mailSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold uppercase']"
                            @click="openTab = 'mailSettings'">{{$t('E-mail settings')}}
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button
                            :class="[openTab === 'pushSettings' ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'py-4 px-2 border-b-2 font-semibold uppercase']"
                            @click="openTab = 'pushSettings'">{{ $t('Push settings')}}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="">
                <div class="grid grid-cols-12 mt-12 gap-12" v-if="openTab === 'notifications'">
                    <div class="col-span-8">
                        <!-- Raumbelegungen und Termine Notifications -->
                        <NotificationSectionComponent :readNotifications="readNotifications['EVENTS']"
                                                      :name="$t('Room bookings & events')" :rooms="rooms"
                                                      :projects="projects"
                                                      :event-types="eventTypes"
                                                      :notifications="notifications['EVENTS']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                        <!-- Räume und Raumbelegungsanfragen -->
                        <NotificationSectionComponent :readNotifications="readNotifications['ROOMS']"
                                                      :name="$t('Rooms & room booking requests')" :rooms="rooms"
                                                      :projects="projects" :event-types="eventTypes"
                                                      :notifications="notifications['ROOMS']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                        <!-- Aufgaben -->
                        <NotificationSectionComponent :readNotifications="readNotifications['TASKS']" :name="$t('Tasks')"
                                                      :rooms="rooms" :projects="projects" :event-types="eventTypes"
                                                      :notifications="notifications['TASKS']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                        <!-- Projekte & Teams -->
                        <NotificationSectionComponent :readNotifications="readNotifications['PROJECTS']"
                                                      :name="$t('Projects & Teams')" :rooms="rooms" :projects="projects"
                                                      :event-types="eventTypes"
                                                      :notifications="notifications['PROJECTS']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                        <NotificationSectionComponent :readNotifications="readNotifications['BUDGET']"
                                                      :name="$t('Project budgets & sources of funding')" :rooms="rooms" :projects="projects"
                                                      :event-types="eventTypes"
                                                      :notifications="notifications['BUDGET']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                        <NotificationSectionComponent :readNotifications="readNotifications['SHIFTS']"
                                                      :name="$t('Shift planning')" :rooms="rooms" :projects="projects"
                                                      :event-types="eventTypes"
                                                      :notifications="notifications['SHIFTS']"
                                                      :history-objects="historyObjects"
                                                      :event="event"
                                                      :wanted-split="wantedSplit"
                                                      :project="project"
                                                      :room-collisions="roomCollisions"
                                                      :first_project_shift_tab_id="first_project_shift_tab_id"
                                                      :first_project_budget_tab_id="first_project_budget_tab_id"
                                                      :first_project_calendar_tab_id="first_project_calendar_tab_id"
                        />
                    </div>
                    <div  class="col-span-4 pr-8">
                        <div v-if="globalNotification.image_url || globalNotification.title">
                            <div class="bg-backgroundGray">
                                <img v-if="globalNotification.image_url" alt="Benachrichtigungs Bild" class="max-h-96"
                                     :src="globalNotification.image_url"/>
                                <div class="px-4 py-4">
                                    <div class="headline2 mt-2 mb-2">
                                        {{ globalNotification.title }}
                                    </div>
                                    <div class="xsLight">
                                        {{ globalNotification.description }}
                                    </div>
                                </div>
                            </div>
                        </div>
                       <div class="mt-4" v-if="hasAdminRole() || $canAny(['change system notification'])">
                           <SecondaryButton :text="$t('Change notification to all')" class="col-span-12" @click="showGlobalNotificationModal = true"/>
                       </div>
                    </div>
                </div>
                <div v-if="openTab === 'mailSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-20">
                        <NotificationFrequencySettings :group-types="groupTypes"
                                                       :notification-frequencies="notificationFrequencies"
                                                       :notificationSettings="notificationSettings"/>
                    </p>
                </div>
                <div v-if="openTab === 'pushSettings'">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-20">
                        <NotificationPushSettings :group-types="groupTypes"
                                                  :notificationSettings="notificationSettings"/>
                    </p>
                </div>
            </div>
        </div>
        <GlobalNotificationModal v-if="showGlobalNotificationModal" @closed="showGlobalNotificationModal = false" :global-notification="globalNotification"/>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
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
import {
    CheckIcon,
    ChevronRightIcon,
    ChevronUpIcon,
    PlusSmIcon,
    XCircleIcon
} from '@heroicons/vue/solid'

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
import {
    Link,
    useForm
} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon";
import NotificationFrequencySettings from "@/Layouts/Components/NotificationFrequencySettings";
import NotificationSectionComponent from "@/Layouts/Components/NotificationSectionComponent";
import NotificationPushSettings from "@/Layouts/Components/NotificationPushSettings";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent";
import Permissions from "@/mixins/Permissions.vue";
import GlobalNotificationModal from "@/Pages/Notifications/Components/GlobalNotificationModal.vue";
import SecondaryButton from "@/Layouts/Components/General/Buttons/SecondaryButton.vue";
import NotificationBlock from "@/Layouts/Components/NotificationComponents/NotificationBlock.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        NotificationBlock,
        SecondaryButton,
        GlobalNotificationModal,
        NotificationPushSettings,
        NotificationSectionComponent,
        NotificationFrequencySettings,
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
        ChevronRightIcon,
        NotificationUserIcon,
        AnswerEventRequestComponent,
    },
    props: [
        'historyObjects',
        'notifications',
        'rooms',
        'eventTypes',
        'projects',
        'readNotifications',
        'notificationSettings',
        'notificationFrequencies',
        'groupTypes',
        'event',
        'project',
        'wantedSplit',
        'roomCollisions',
        'globalNotification',
        'first_project_shift_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id'
    ],
    data() {
        return {
            openTab: 'notifications',
            showRoomsAndEvents: true,
            showRoomsAndRoomRequests: true,
            deleteComponentVisible: false,
            answerRequestModalVisible: false,
            requestToAnswer: null,
            answerRequestType: '',
            answerRequestForm: useForm({
                accepted: false,
            }),
            showGlobalNotificationModal: false
        }
    },
    methods: {
        formatDate(isoDate) {
            if (isoDate?.split('T').length > 1) {
                return isoDate.split('T')[0].substring(8, 10) + '.' +
                    isoDate.split('T')[0].substring(5, 7) + '.' +
                    isoDate.split('T')[0].substring(0, 4) + ', ' +
                    isoDate.split('T')[1].substring(0, 5)
            } else if(isoDate?.split(' ').length > 1) {
                return isoDate.split(' ')[0].substring(8, 10) + '.' +
                    isoDate.split(' ')[0].substring(5, 7) + '.' +
                    isoDate.split(' ')[0].substring(0, 4) + ', ' +
                    isoDate.split(' ')[1].substring(0, 5)
            }
        },
        isErrorType(type, notification) {
            return type.indexOf('RoomRequestNotification') !== -1 && notification.data.accepted === false ||
                type.indexOf('ConflictNotification') !== -1 ||
                notification.data.title === 'Termin abgesagt';
        }
    }
})
</script>
