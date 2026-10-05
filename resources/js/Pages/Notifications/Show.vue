<template>
    <app-layout :title="$t('Notifications')">
        <div class="artwork-container">
            <div class="flex">
                <!-- Greetings Div -->
                <div class="mr-2 w-4/6">
                    <div class=" mt-10">
                        <h2 class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text flex mb-4">{{$t('Notifications')}}</h2>
                    </div>
                </div>
            </div>
            <div class=" mt-8">
                <div class="mb-4 border-border-subtle ">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                        <li v-for="tab in tabs" :key="tab.key" class="mr-2" role="presentation">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="openTab === tab.key"
                                :class="[openTab === tab.key ? 'border-accent-600 text-accent-600' : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border', 'py-4 px-2 border-b-2 font-semibold']"
                                @click="switchTab(tab.key)">{{ $t(tab.label) }}
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="">
                    <div class="grid grid-cols-12 mt-12 gap-12" v-if="openTab === 'notifications'">
                        <div class="col-span-8">
                            <!-- Raumbelegungen und Termine Notifications -->
                            <NotificationSectionComponent group-type="EVENTS"
                                                          :unread-count="notificationCounts['EVENTS'].unread"
                                                          :archived-count="notificationCounts['EVENTS'].archived"
                                                          :name="$t('Room bookings & events')" :rooms="rooms"
                                                          :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <!-- Räume und Raumbelegungsanfragen -->
                            <NotificationSectionComponent group-type="ROOMS"
                                                          :unread-count="notificationCounts['ROOMS'].unread"
                                                          :archived-count="notificationCounts['ROOMS'].archived"
                                                          :name="$t('Rooms & room booking requests')" :rooms="rooms"
                                                          :projects="projects" :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <!-- Aufgaben -->
                            <NotificationSectionComponent group-type="TASKS"
                                                          :unread-count="notificationCounts['TASKS'].unread"
                                                          :archived-count="notificationCounts['TASKS'].archived"
                                                          :name="$t('Tasks')"
                                                          :rooms="rooms" :projects="projects" :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <!-- Projekte & Teams -->
                            <NotificationSectionComponent group-type="PROJECTS"
                                                          :unread-count="notificationCounts['PROJECTS'].unread"
                                                          :archived-count="notificationCounts['PROJECTS'].archived"
                                                          :name="$t('Projects & Teams')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <NotificationSectionComponent group-type="BUDGET"
                                                          :unread-count="notificationCounts['BUDGET'].unread"
                                                          :archived-count="notificationCounts['BUDGET'].archived"
                                                          :name="$t('Project budgets & sources of funding')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <NotificationSectionComponent group-type="SHIFTS"
                                                          :unread-count="notificationCounts['SHIFTS'].unread"
                                                          :archived-count="notificationCounts['SHIFTS'].archived"
                                                          :name="$t('Shift planning')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                            />
                            <!-- Inventory and Issues -->
                            <NotificationSectionComponent group-type="INVENTORY"
                                                          :unread-count="notificationCounts['INVENTORY'].unread"
                                                          :archived-count="notificationCounts['INVENTORY'].archived"
                                                          :name="$t('Inventory & Material Issues')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                                                          />
                            <!-- Documents -->
                            <NotificationSectionComponent group-type="DOCUMENTS"
                                                          :unread-count="notificationCounts['DOCUMENTS'].unread"
                                                          :archived-count="notificationCounts['DOCUMENTS'].archived"
                                                          :name="$t('Documents')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                                                          />
                            <!-- External access -->
                            <NotificationSectionComponent group-type="EXTERNAL_ACCESS"
                                                          :unread-count="notificationCounts['EXTERNAL_ACCESS'].unread"
                                                          :archived-count="notificationCounts['EXTERNAL_ACCESS'].archived"
                                                          :name="$t('External access')" :rooms="rooms" :projects="projects"
                                                          :event-types="eventTypes"
                                                          :history-objects="historyObjects"
                                                          :event="event"
                                                          :wanted-split="wantedSplit"
                                                          :project="project"
                                                          :room-collisions="roomCollisions"
                                                          :first_project_shift_tab_id="first_project_shift_tab_id"
                                                          :first_project_budget_tab_id="first_project_budget_tab_id"
                                                          :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                          :event-statuses="eventStatuses"
                                                          />
                        </div>
                        <div  class="col-span-4 pr-8">
                            <div v-if="globalNotification.image_url || globalNotification.title">
                                <div class=" rounded-xl">
                                    <img v-if="globalNotification.image_url" alt="Benachrichtigungs-Bild" class="max-h-96 rounded-t-xl"
                                         :src="globalNotification.image_url"/>
                                    <div class="px-4 py-4">
                                        <div class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text mt-2 mb-2">
                                            {{ globalNotification.title }}
                                        </div>
                                        <div class="text-sm/5 font-bold text-text-subtle">
                                            {{ globalNotification.description }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4" v-if="hasAdminRole() || $canAny(['change system notification'])">
                                <SecondaryButton :text="$t('Change notification to all')"
                                                 class="col-span-12"
                                                 @click="showGlobalNotificationModal = true"/>
                            </div>
                        </div>
                    </div>
                    <div v-if="openTab === 'settings'" class="mb-20">
                        <NotificationSettingsPanel :groups="notificationSettingGroups"
                                                   :frequencies="notificationFrequencies"/>
                    </div>
                </div>
            </div>
        </div>
        <GlobalNotificationModal v-if="showGlobalNotificationModal" @closed="showGlobalNotificationModal = false" :global-notification="globalNotification"/>
    </app-layout>
</template>

<script>
import {IconCheck, IconChevronDown, IconChevronRight, IconChevronUp, IconCircleX, IconDotsVertical, IconEdit, IconInfoCircle, IconPlus, IconSearch, IconTrash, IconX} from "@tabler/icons-vue";
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

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
import Button from "@/Jetstream/Button.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import {Link, useForm} from "@inertiajs/vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import NotificationUserIcon from "@/Layouts/Components/NotificationUserIcon.vue";
import NotificationSettingsPanel from "@/Layouts/Components/NotificationComponents/NotificationSettingsPanel.vue";
import NotificationSectionComponent from "@/Layouts/Components/NotificationSectionComponent.vue";
import AnswerEventRequestComponent from "@/Layouts/Components/AnswerEventRequestComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import GlobalNotificationModal from "@/Pages/Notifications/Components/GlobalNotificationModal.vue";
import SecondaryButton from "@/Layouts/Components/General/Buttons/SecondaryButton.vue";
import NotificationBlock from "@/Layouts/Components/NotificationComponents/NotificationBlock.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        NotificationBlock,
        SecondaryButton,
        GlobalNotificationModal,
        NotificationSectionComponent,
        NotificationSettingsPanel,
        TeamIconCollection,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        IconDotsVertical,
        IconPlus,
        IconSearch,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        IconCheck,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        IconInfoCircle,
        IconChevronDown,
        IconChevronUp,
        Checkbox,
        IconX,
        IconEdit,
        IconTrash,
        IconCircleX,
        Link,
        InputComponent,
        IconChevronRight,
        NotificationUserIcon,
        AnswerEventRequestComponent,
    },
    props: [
        'historyObjects',
        'notificationCounts',
        'rooms',
        'eventTypes',
        'projects',
        'notificationSettingGroups',
        'notificationFrequencies',
        'event',
        'project',
        'wantedSplit',
        'roomCollisions',
        'globalNotification',
        'first_project_shift_tab_id',
        'first_project_budget_tab_id',
        'first_project_calendar_tab_id',
        'eventStatuses'
    ],
    data() {
        return {
            // Reiter per URL verlinkbar (?tab=settings), z. B. aus Benachrichtigungen und E-Mails
            openTab: new URLSearchParams(window.location.search).get('tab') === 'settings' ? 'settings' : 'notifications',
            tabs: [
                { key: 'notifications', label: 'Notifications' },
                { key: 'settings', label: 'Settings' },
            ],
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
        switchTab(key) {
            this.openTab = key;
            const url = new URL(window.location.href);
            if (key === 'settings') {
                url.searchParams.set('tab', 'settings');
            } else {
                url.searchParams.delete('tab');
                url.searchParams.delete('type');
            }
            window.history.replaceState(window.history.state, '', url);
        },
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
