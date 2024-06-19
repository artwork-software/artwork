<template>
    <app-layout :title="room.name">
        <div class="max-w-screen-xl my-12 ml-14">
            <div class="flex-wrap">
                <div class="flex items-center">
                    <h2 class="headline1">{{ room.name }}</h2>
                    <BaseMenu :right="false" v-if="this.hasAdminRole() || $canAny(['create, delete and update rooms']) || this.is_room_admin" class="ml-2">
                        <MenuItem v-slot="{ active }">
                            <a @click="openEditRoomModal(room)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                <IconEdit stroke-width="1.5"
                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                          aria-hidden="true"/>
                                {{$t('edit')}}
                            </a>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <a href="#" @click="duplicateRoom(room)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconCopy stroke-width="1.5"
                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                          aria-hidden="true"/>
                                {{ $t('Duplicate')}}
                            </a>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <a @click="openSoftDeleteRoomModal(room)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconTrash  stroke-width="1.5"
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                {{$t('In the recycle bin')}}
                            </a>
                        </MenuItem>
                    </BaseMenu>
                </div>
                <div v-if="room.room_history[0]"
                     class="mt-2 subpixel-antialiased text-secondary text-xs flex items-center">
                    <div>
                        {{$t('last modified')}}:
                    </div>
                    <UserPopoverTooltip :user="room.room_history[0].changes[0].changed_by"
                                        :id="room.room_history[0].changes[0].changed_by?.id"
                                        height="4" width="4" class="ml-2"/>
                    <span class="ml-2 subpixel-antialiased">
                        {{ room.room_history[0].created_at }}
                    </span>
                    <button class="ml-4 subpixel-antialiased flex items-center cursor-pointer text-artwork-buttons-create"
                            @click="openRoomHistoryModal()">
                        <ChevronRightIcon
                            class="-mr-0.5 h-4 w-4 group-hover:text-white"
                            aria-hidden="true"/>
                        {{$t('View history')}}
                    </button>
                </div>
                <div v-if="room.temporary === true" class="font-lexend my-4 font-semibold">
                    {{ room.start_date }} - {{ room.end_date }}
                </div>
                <div class="w-[95%] grid grid-cols-7 mt-6">
                    <div class="col-span-5 mr-14">
                        <span class="xsLight">
                            {{ room.area.name }}
                        </span>
                        <p class="xsLight mt-4">
                            {{$t('Can be booked by anyone ')}}: <label v-if="room.everyone_can_book">{{$t('Yes')}}</label>
                            <label v-else>{{ $t('No') }}</label>
                        </p>
                        <span class="flex mt-6 xsLight subpixel-antialiased">
                            {{ room.description }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-12 ml-14" v-if="$role('artwork admin') || $canAny(['create, delete and update rooms']) || this.is_room_admin">
            <div class="flex mt-6 items-center mb-2 ml-14">
                <h3 class="headline2"> {{$t('Room assignment')}} </h3>
            </div>
            <div>
                <div v-if="calendarType && calendarType === 'daily'">
                    <div class="min-w-[50%] mt-5 overflow-x-auto px-2">
                        <CalendarComponent initial-view="day"
                                           :project="null"
                                           :atAGlance="false"
                                           :room="this.room"
                                           :personal-filters="personalFilters"
                                           :filter-options="filterOptions"
                                           :eventsWithoutRoom="eventsWithoutRoom"
                                           :events="this.calendar[this.formatSelectedDate(this.selectedDate)]?.events?.data ?? []"
                                           :dateValue="dateValue"
                                           :eventTypes=this.event_types
                                           :rooms="rooms"
                                           :user_filters="user_filters"
                                           :selected-date="selectedDate"
                                           :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                        />
                    </div>
                </div>
                <div v-else>
                    <SingleRoomCalendarComponent  :personal-filters="personalFilters"
                                                  :filter-options="filterOptions"
                                                  :eventsWithoutRoom="eventsWithoutRoom"
                                                  :dateValue="dateValue"
                                                  :eventTypes=this.event_types
                                                  :calendarData="calendar"
                                                  :days="days"
                                                  :rooms="rooms"
                                                  :user_filters="user_filters"
                                                  :first_project_tab_id="this.first_project_tab_id"
                                                  :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                    />
                </div>
            </div>
        </div>

        <!-- Raum Bearbeiten-->
        <BaseModal @closed="closeEditRoomModal" v-if="showEditRoomModal" modal-image="/Svgs/Overlays/illu_room_edit.svg">
                <div class="mx-3">
                    <div class="headline1 my-2">
                        {{$t('Edit room')}}
                    </div>
                    <div class="mt-4">
                        <div class="flex mt-10 relative">
                            <input id="roomNameEdit" v-model="editRoomForm.name" type="text"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="roomNameEdit"
                                   class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                                {{$t('Room name')}}
                            </label>
                            <jet-input-error :message="editRoomForm.error" class="mt-2"/>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                :placeholder="$t('Short description')"
                                                v-model="editRoomForm.description" rows="4"
                                                class="shadow-sm placeholder-secondary resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                        </div>
                        <div class="flex items-center my-6">
                            <input v-model="editRoomForm.temporary"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[editRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                               class="ml-4 my-auto text-sm">{{$t('Temporary room')}}</p>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                                <span
                                    class="ml-1 my-auto hind">{{$t('Set up a temporary room - e.g. if part of a room is partitioned off. This is only displayed in the calendar during this period.')}}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-3" v-if="editRoomForm.temporary">
                            <input
                                v-model="editRoomForm.start_date_dt_local" id="startDateEdit"
                                :placeholder="$t('To be completed by?')" type="date"
                                class="border-gray-300 col-span-1 placeholder-secondary mr-2 w-full"/>
                            <input
                                v-model="editRoomForm.end_date_dt_local" id="endDateEdit"
                                :placeholder="$t('To be completed by?')" type="date"
                                class="border-gray-300 col-span-1 placeholder-secondary w-full"/>
                        </div>

                        <div class="flex items-center my-6">
                            <input v-model="editRoomForm.everyone_can_book"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[editRoomForm.everyone_can_book ? 'text-primary font-black' : 'text-secondary']"
                               class="ml-4 my-auto text-sm">{{ $t('Can be booked by anyone')}}</p>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2"/>
                                <span
                                    class="ml-1 my-auto hind">{{ $t('Decides whether this room can be booked by everyone or only by the room admins.')}}</span>
                            </div>
                        </div>

                        <div class="flex justify-center pt-8">
                            <FormButton
                                :disabled="editRoomForm.name.length === 0"
                                @click="editRoom"
                                :text="$t('Save')"
                            />
                        </div>

                    </div>
                </div>
        </BaseModal>
        <!-- Success Modal -->
        <SuccessModal
            :title="successHeading"
            :description="successDescription"
            :show="showSuccessModal"
            @closed="closeSuccessModal"
        />
        <!-- Approve Request Modal -->
        <BaseModal @closed="closeApproveRequestModal" v-if="showApproveRequestModal" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{ $t('Confirm room occupancy')}}
                    </div>
                    <div class="successText">
                        {{$t('Bist du sicher, dass du die Raumbelegung zusagen möchtest?')}}
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <div>
                                        <div class="block w-6 h-6 rounded-full" :style="{'backgroundColor' : requestToApprove.eventType?.hex_code }" />
                                    </div>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToApprove.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToApprove.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToApprove.audience"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToApprove.is_loud"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full xsLight whitespace-nowrap ml-3"
                                         v-if="requestToApprove.start_time.split(',')[0] === requestToApprove.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }}, {{
                                            requestToApprove.start_time.split(',')[0]
                                        }},{{ requestToApprove.start_time.split(',')[1] }}
                                        - {{ requestToApprove.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full xsLight whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }},
                                        {{ requestToApprove.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.end_time_weekday) }},
                                        {{ requestToApprove.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToApprove.project" class="w-80">
                                    <div class="ml-16  xsLight flex items-center">
                                        {{$t('assigned to')}}
                                        <div class="xsDark ml-2">
                                            {{ requestToApprove.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="xsLight ml-10" v-else>
                                    {{$t('Not assigned to a project')}}
                                </div>
                                <div class="flex xsLight items-center">
                                    {{$t('requested')}}:
                                    <UserPopoverTooltip :height="7" :width="7" v-if="requestToApprove.created_by"
                                                    :user="requestToApprove.created_by" :id="1"/>
                                    <span class="ml-2 xsLight"> {{ requestToApprove.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 xsLight items-center w-full"
                                 v-if="requestToApprove.description">
                                {{ requestToApprove.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="approveRequest">
                            {{$t('Commitments')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeApproveRequestModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <!-- Decline Request Modal -->
        <BaseModal @closed="closeDeclineRequestModal" v-if="showDeclineRequestModal" modal-image="/Svgs/Overlays/illu_appointment_warning.svg">
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{ $t('Cancel room reservation')}}
                    </div>
                    <div class="errorText">
                        {{$t('Are you sure you want to cancel the room reservation?')}}
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <div>
                                        <div class="block w-6 h-6 rounded-full" :style="{'backgroundColor' : requestToDecline.eventType?.hex_code }" />
                                    </div>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToDecline.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToDecline.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToDecline.audience"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToDecline.is_loud"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full xsLight whitespace-nowrap ml-3"
                                         v-if="requestToDecline.start_time.split(',')[0] === requestToDecline.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }}, {{
                                            requestToDecline.start_time.split(',')[0]
                                        }},{{ requestToDecline.start_time.split(',')[1] }}
                                        - {{ requestToDecline.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full xsLight whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }},
                                        {{ requestToDecline.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.end_time_weekday) }},
                                        {{ requestToDecline.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToDecline.project" class="w-80">
                                    <div class="ml-16 xsLight flex items-center">
                                        {{$t('assigned to')}}
                                        <div class="xsDark ml-2">
                                            {{ requestToDecline.project.name }}
                                        </div>
                                    </div>
                                    <!--
                                    <div v-for="projectLeader in requestToApprove.project.project_managers">
                                        <img :data-tooltip-target="projectLeader.id"
                                             :src="projectLeader.profile_photo_url"
                                             :alt="projectLeader.name"
                                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                        <UserTooltip :user="projectLeader"/>
                                    </div>
                                    -->
                                </div>
                                <div class="xsLight ml-10" v-else>
                                    {{$t('Not assigned to a project')}}
                                </div>
                                <div class="flex xsLight items-center">
                                    {{$t('requested')}}:
                                    <UserPopoverTooltip :height="7" :width="7" v-if="requestToDecline.created_by"
                                                    :user="requestToDecline.created_by" :id="1"/>
                                    <span class="ml-2 xsLight"> {{ requestToDecline.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 xsLight items-center w-full"
                                 v-if="requestToDecline.description">
                                {{ requestToDecline.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <FormButton
                            @click="declineRequest"
                            :text="$t('Cancellations')"
                            class="inline-flex items-center"
                        />
                        <div class="flex my-auto">
                            <span @click="closeDeclineRequestModal"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
    </app-layout>

    <BaseSidenav :show="showSidenav" @toggle="this.showSidenav =! this.showSidenav">
        <RoomSidenav
            :room="room"
            :categories="roomCategories.data"
            :available-categories="available_categories"
            :attributes="roomAttributes.data"
            :available-attributes="available_attributes"
            :adjoiningRooms="adjoiningRooms.data"
            :available-adjoining-rooms="available_rooms"
        />
    </BaseSidenav>

    <!-- Room History Modal-->
    <room-history-component
        v-if="showRoomHistory"
        :room_history="room.room_history"
        @closed="closeRoomHistoryModal"
    />
    <!-- Delete Room Modal -->
    <ConfirmationComponent v-if="showSoftDeleteRoomModal"
                           :confirm="$t('Delete room')"
                           :titel="$t('Room in the recycle bin')"
                           :description="roomDeleteDescriptionText"
                           @closed="afterSoftDeleteRoomConfirm"/>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Disclosure,
    DisclosureButton,
    DisclosurePanel
} from "@headlessui/vue";
import {
    DocumentTextIcon,
    DuplicateIcon,
    PencilAltIcon,
    TrashIcon,
    XIcon,
    PlusIcon,
    MinusIcon
} from "@heroicons/vue/outline";
import {
    CheckIcon,
    ChevronDownIcon,
    DotsVerticalIcon,
    PlusSmIcon,
    XCircleIcon,
    ChevronRightIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link, useForm} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import RoomHistoryComponent from "@/Layouts/Components/RoomHistoryComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import RoomSidenav from "@/Layouts/Components/RoomSidenav.vue";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import SingleRoomCalendarComponent from "@/Layouts/Components/SingleRoomCalendarComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";


export default {
    mixins: [Permissions, IconLib],
    name: "Show",
    props: [
        'room',
        'rooms',
        'event_types',
        'projects',
        'is_room_admin',
        'available_categories',
        'roomCategoryIds',
        'roomCategories',
        'available_attributes',
        'roomAttributeIds',
        'roomAttributes',
        'available_rooms',
        'adjoiningRoomIds',
        'adjoiningRooms',
        'calendarType',
        'selectedDate',
        'dateValue',
        'calendar',
        'days',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    components: {
        BaseModal,
        BaseMenu,
        FormButton,
        SuccessModal,
        ConfirmationComponent,
        UserPopoverTooltip,
        IndividualCalendarComponent,
        RoomSidenav,
        BaseSidenav,
        NewUserToolTip,
        PlusIcon,
        MinusIcon,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        DocumentTextIcon,
        DuplicateIcon,
        UserTooltip,
        PlusSmIcon,
        Link,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        CalendarComponent,
        ChevronRightIcon,
        RoomHistoryComponent,
        SingleRoomCalendarComponent
    },
    computed: {
        eventTypeFilters: function () {
            let filters = [];
            this.event_types.forEach((eventType) => {
                filters.push({eventTypeId: eventType.id, name: eventType.name});
            })
            return filters;
        },
        requestsToShow: function () {
            let requestsToShow;
            if (this.hasAdminRole() || this.is_room_admin || this.$canAny(['create, delete and update rooms'])) {
                requestsToShow = this.room.event_requests
            }
            return requestsToShow
        },
        roomDeleteDescriptionText() {
            return this.$t('Are you sure you want to put the room {0} in the trash?', [this.roomToSoftDelete.name]);
        },
    },
    mounted() {
        setTimeout(() => {
            this.showSidenav = false;
        }, 1000)
    },
    data() {
        return {
            showSidenav: true,
            attributesOpened: false,
            showSuccess: false,
            user_query: "",
            user_search_results: [],
            uploadDocumentFeedback: "",
            showEditRoomModal: false,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            requestToDecline: null,
            requestToApprove: null,
            showApproveRequestModal: false,
            showDeclineRequestModal: false,
            showRoomHistory: false,
            successHeading: "",
            successDescription: "",
            roomForm: this.$inertia.form({
                _method: 'PUT',
                room_admins: this.room.room_admins,
                room_categories: this.roomCategoryIds,
                room_attributes: this.roomAttributeIds,
                adjoining_rooms: this.adjoiningRoomIds
            }),
            editRoomForm: useForm({
                id: null,
                name: '',
                description: '',
                temporary: false,
                room_admins: this.room.room_admins,
                room_categories: this.roomCategoryIds,
                room_attributes: this.roomAttributeIds,
                adjoining_rooms: this.adjoiningRoomIds,
                start_date: null,
                start_date_dt_local: null,
                end_date: null,
                end_date_dt_local: null,
                area_id: null,
                user_id: null,
                everyone_can_book: this.room.everyone_can_book
            }),
            documentForm: useForm({
                file: null
            }),
            approveRequestForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                user_id: this.$page.props.user.id,
            }),
            declineRequestForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                user_id: this.$page.props.user.id,
            }),
        }
    },
    methods: {
        formatSelectedDate(selectedDate) {
            let parts = selectedDate.split('-');
            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        getGermanWeekdayAbbreviation(englishWeekday) {
            switch (englishWeekday) {
                case 'Monday':
                    return 'Mo';
                case 'Tuesday':
                    return 'Di';
                case 'Wednesday':
                    return 'Mi';
                case 'Thursday':
                    return 'Do';
                case 'Friday':
                    return 'Fr';
                case 'Saturday':
                    return 'Sa';
                case 'Sunday':
                    return 'So';
            }
        },
        afterSoftDeleteRoomConfirm(confirmed) {
            if (confirmed) {
                this.softDeleteRoom()
            } else {
                this.closeSoftDeleteRoomModal()
            }
        },

        openApproveRequestModal(eventRequest) {
            this.requestToApprove = eventRequest;
            this.showApproveRequestModal = true;
        },
        closeApproveRequestModal() {
            this.showApproveRequestModal = false;
            this.requestToApprove = null;
        },
        openDeclineRequestModal(eventRequest) {
            this.requestToDecline = eventRequest;
            this.showDeclineRequestModal = true;
        },
        closeDeclineRequestModal() {
            this.showDeclineRequestModal = false;
            this.requestToDecline = null;
        },
        approveRequest() {
            this.approveRequestForm.name = this.requestToApprove.name;
            this.approveRequestForm.start_time = this.requestToApprove.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToApprove.end_time_dt_local;
            this.approveRequestForm.description = this.requestToApprove.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToApprove.is_loud;
            this.approveRequestForm.audience = this.requestToApprove.audience;
            if (this.requestToApprove.room) {
                this.approveRequestForm.room_id = this.requestToApprove.room.id;
            }
            if (this.requestToApprove.project) {
                this.approveRequestForm.project_id = this.requestToApprove.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToApprove.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToApprove.id}));
            this.closeApproveRequestModal();
        },
        declineRequest() {
            this.approveRequestForm.name = this.requestToDecline.name;
            this.approveRequestForm.start_time = this.requestToDecline.start_time_dt_local;
            this.approveRequestForm.end_time = this.requestToDecline.end_time_dt_local;
            this.approveRequestForm.description = this.requestToDecline.description;
            this.approveRequestForm.occupancy_option = false;
            this.approveRequestForm.is_loud = this.requestToDecline.is_loud;
            this.approveRequestForm.audience = this.requestToDecline.audience;
            this.approveRequestForm.room_id = null;
            if (this.requestToDecline.project) {
                this.approveRequestForm.project_id = this.requestToDecline.project.id;
            }
            this.approveRequestForm.event_type_id = this.requestToDecline.event_type.id;
            this.approveRequestForm.patch(route('events.update', {event: this.requestToDecline.id}));
            this.closeDeclineRequestModal();
        },
        selectNewFiles() {
            this.$refs.room_files.click();
        },
        removeFile(room_file) {
            this.$inertia.delete(`/room_files/${room_file.id}`, {
                preserveState: true,
            })
        },
        downloadFile(room_file) {
            let link = document.createElement('a');
            link.href = route('download_room_file', {room_file: room_file});
            link.target = '_blank';
            link.click();
        },
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {

                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = this.$t('Videos, .exe and .dmg files are not supported')
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = this.$t('Files larger than 2MB cannot be uploaded.')
                    } else {
                        this.uploadDocumentToRoom(file)
                    }

                }
            }
        },
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
        },
        uploadDocumentToRoom(file) {
            this.documentForm.file = file

            this.documentForm.post(`/rooms/${this.room.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
        },
        openChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = true;
        },
        closeChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = false;
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        saveRoomAttributes() {
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
            this.attributesOpened = false;
        },
        removeCategoryFromRoom(index) {
            this.roomForm.room_categories.splice(index, 1)
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
        },
        removeAttributeFromRoom(index) {
            this.roomForm.room_attributes.splice(index, 1)
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
        },
        removeAdjoiningRoomFromRoom(index) {
            this.roomForm.adjoining_rooms.splice(index, 1)
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
        },
        addUserToRoomAdminsArray(user) {
            for (let adminUser of this.roomForm.room_admins) {
                //if User is already Room Admin, do nothing
                if (user.id === adminUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.roomForm.room_admins.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
        duplicateRoom(room) {
            this.$inertia.post(`/rooms/${room.id}/duplicate`);
        },
        openEditRoomModal(room) {
            this.editRoomForm.id = room.id;
            this.editRoomForm.name = room.name;
            this.editRoomForm.description = room.description;
            this.editRoomForm.start_date = room.start_date;
            this.editRoomForm.end_date = room.end_date;
            this.editRoomForm.start_date_dt_local = room.start_date_dt_local;
            this.editRoomForm.end_date_dt_local = room.end_date_dt_local;
            if (room.temporary === true) {
                this.editRoomForm.temporary = true;
            }
            this.showEditRoomModal = true;
        },
        closeEditRoomModal() {
            this.showEditRoomModal = false;
            this.editRoomForm.id = null;
            this.editRoomForm.name = null;
            this.editRoomForm.description = null;
            this.editRoomForm.start_date = null;
            this.editRoomForm.end_date = null;
            this.editRoomForm.start_date_dt_local = null;
            this.editRoomForm.end_date_dt_local = null;
        },
        openSoftDeleteRoomModal(room) {
            this.roomToSoftDelete = room;
            this.showSoftDeleteRoomModal = true;
        },
        closeSoftDeleteRoomModal() {
            this.showSoftDeleteRoomModal = false;
            this.roomToSoftDelete = null;
        },
        softDeleteRoom() {
            this.$inertia.delete(`/rooms/${this.roomToSoftDelete.id}`);
            this.closeSoftDeleteRoomModal();
            this.successHeading = this.$t('Room in the recycle bin')
            this.successDescription = this.$t('The room has been successfully moved to the trash.')
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
        },
        editRoom() {
            this.editRoomForm.start_date = this.editRoomForm.start_date_dt_local;
            this.editRoomForm.end_date = this.editRoomForm.end_date_dt_local;
            this.editRoomForm.patch(route('rooms.update', {room: this.editRoomForm.id}));
            this.closeEditRoomModal();
        },
        openRoomHistoryModal() {
            this.showRoomHistory = true;
        },
        closeRoomHistoryModal() {
            this.showRoomHistory = false;
        },
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
}
</script>

<style scoped>

</style>
