<template>
    <div class="mt-10 items-center w-[95%] relative bg-secondaryHover" id="myCalendar">
        <div class="flex justify-center" :class="filteredEvents?.length ? 'mt-10' : ''">
            <div class="ml-5 flex errorText items-center cursor-pointer mb-5 "
                 @click="openEventsWithoutRoomComponent()"
                 v-if="filteredEvents?.length > 0"
                >

                <IconAlertTriangle class="h-6  mr-2"/>{{ filteredEvents?.length === 1 ? $t('{0} Event without room!', [filteredEvents?.length]) : $t('{0} Events without room!', [filteredEvents?.length]) }}
            </div>
        </div>
        <div class="bg-white">
          <div class="pl-14">
              <CalendarFunctionBar
                  @open-event-component="openEventComponent"
                  @nextDay="nextDay"
                  @previousDay="previousDay"
                  @enterFullscreenMode="openFullscreen"
                  :dateValue="dateValue"
                  @change-at-a-glance="changeAtAGlance"
                  :at-a-glance="atAGlance"
                  :filter-options="filterOptions"
                  :personal-filters="personalFilters"
                  :is-fullscreen="isFullscreen"
                  :user_filters="user_filters"
              />
          </div>

            <!--  Calendar  -->
            <div class="pl-14 overflow-x-scroll">
                <vue-cal
                    ref="vuecal"
                    id="vuecal"
                    style="height: 70rem; max-height: calc(100vh - 280px); min-width: 100%; width: fit-content;"
                    today-button
                    :time-cell-height=120
                    events-on-month-view="short"
                    locale="de"
                    hide-view-selector
                    show-week-numbers
                    :hideTitleBar="currentView !== 'year'"

                    sticky-split-labels
                    :disable-views="['years']"
                    :events="displayedEvents"
                    :split-days="displayedRooms"
                    :editable-events="{ title: false, drag: true, resize: false, delete: true, create: true }"
                    :snap-to-time="15"
                    :selected-date="selectedDate"
                    :drag-to-create-threshold="15"
                    events-count-on-year-view
                    v-model:active-view="currentView"
                    @event-drag-create="openEventComponent($event)"
                    @event-focus="openEventComponent($event)"
                    @ready="initializeCalendar"
                    @view-change="initializeCalendar($event)"
                >
                    <template #title="{ title, view }" class="group">
                        <div :class="currentView === 'year' ? 'ml-24' : ''" class="mb-6">
                            {{ title }}
                        </div>
                    </template>
                    <template #today-button>
                        <div class="flex w-24 xsDark text-artwork-buttons-create" v-if="currentView === 'year'">
                            {{ $t('Current year')}}
                        </div>
                    </template>
                    <template #weekday-heading="{ heading, view }">
                        <div v-if="currentView === 'week'">
                            {{ heading.label }}, {{ heading.date.format("DD.MM.YYYY") }}
                        </div>
                    </template>
                    <template #week-number-cell=" weekNumber, view">
                        <div>
                            KW {{ weekNumber.week }}
                        </div>
                    </template>
                    <template #split-label="{ split, view }">
                        <Link class="text-base font-bold" :href="route('rooms.show',{room: split.id})">
                            {{ split.name }}
                        </Link>
                    </template>
                    <template #event="{ event, view}">
                        <div class="text-left centered mt-3 cursor-pointer" :style="{backgroundColor: backgroundColorWithOpacity(event.event_type?.hex_code), color: TextColorWithDarken(event.event_type?.hex_code)}">
                            <div class="flex w-full justify-between items-center">
                                <div v-if="!project" class="flex eventHeader truncate mx-1">
                                    <div v-if="event.event_type.abbreviation" class="mr-1">
                                        {{ event.event_type.abbreviation }}:
                                    </div>
                                    {{ event.name }}
                                </div>
                                <div v-else class="truncate mx-1">
                                    {{ this.eventTypes.find(eventType => eventType.id === event.event_type.id)?.name }}
                                </div>
                                <div v-if="currentView !== 'month' && (event.audience || event.isLoud)"
                                     class="flex">
                                    <div v-if="event.audience"
                                         class="flex">
                                        <IconUsersGroup class="w-4 h-4" />
                                    </div>
                                </div>
                            </div>
                            <div class="eventTime mx-1" v-if="event.subEvents?.length > 0">
                                <div>
                                    {{$t('Sub-events')}}:
                                </div>
                                <div v-for="subEvent in event.subEvents">
                                    {{ subEvent.event_type.abbreviation }}:
                                    {{ subEvent.name }}
                                </div>

                            </div>


                            <div v-if="currentView !== 'month'" class="mx-1">
                                <div v-if="!project">
                        <span class="truncate"
                              v-if="event.eventName && event.eventName !== event.name"> {{ event.eventName }}</span>
                                </div>
                                <div v-else class="truncate">
                                    {{ event.eventName }}
                                </div>
                                <span class="flex w-full eventTime">
                        <span v-if="event.start.getDay() === event.end.getDay()"
                              class="items-center eventTime">
                            <span v-if="event.allDay" class="lowercase">
                            {{ $t('Full day') }}
                            </span>
                            <span v-else>
                                {{ event.start.formatTime("HH:mm") }} - {{
                                    event.end.formatTime("HH:mm")
                                }}
                            </span>

                        </span>
                        <span class="flex w-full eventTime" v-else>
                            <span class="text-error mx-1">
                        !
                        </span>
                            <span v-if="event.allDay">
                             {{event.start.format("DD.MM.")}} - {{ event.end.format("DD.MM.")}} <span class="lowercase">{{ $t('Full day') }}</span>
                            </span>
                            <span v-else class="items-center eventTime">
                                {{ event.start.format("DD.MM. HH:mm") }} - {{
                                    event.end.format("DD.MM. HH:mm")
                                }}
                            </span>

                        </span><br/>
                    </span>
                                <div class="flex">
                                    <div class="flex -ml-3">
                                        <div v-if="event.projectLeaders && !project"
                                             class="ml-2.5 flex flex-wrap ">
                                            <div class="-mr-3 flex flex-wrap flex-row"
                                                 v-for="user in event.projectLeaders?.slice(0,3)">
                                                <img :src="user.profile_photo_url" alt=""
                                                     class="mx-auto shrink-0 flex object-cover rounded-full"
                                                     :class="['h-' + 6, 'w-' + 6]">
                                            </div>
                                            <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                                                <Menu as="div" class="relative">
                                                    <div>
                                                        <MenuButton class="flex rounded-full focus:outline-none">
                                                            <div
                                                                :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                                                class="ml-2 flex-shrink-0 flex my-auto ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black">
                                                                <p class="">
                                                                    +{{ event.projectLeaders.length - 3 }}
                                                                </p>
                                                            </div>
                                                        </MenuButton>
                                                    </div>
                                                    <transition enter-active-class="transition ease-out duration-100"
                                                                enter-from-class="transform opacity-0 scale-95"
                                                                enter-to-class="transform opacity-100 scale-100"
                                                                leave-active-class="transition ease-in duration-75"
                                                                leave-from-class="transform opacity-100 scale-100"
                                                                leave-to-class="transform opacity-0 scale-95">
                                                        <MenuItems
                                                            class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                            <MenuItem v-for="user in event.projectLeaders"
                                                                      v-slot="{ active }">
                                                                <Link href="#"
                                                                      :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                    <img
                                                                        :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                                                        class="rounded-full"
                                                                        :src="user.profile_photo_url"
                                                                        alt=""/>
                                                                    <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                                                </Link>
                                                            </MenuItem>
                                                        </MenuItems>
                                                    </transition>
                                                </Menu>
                                            </div>
                                        </div>
                                        <div v-else-if="event.created_by"
                                             class="mt-1 ml-3 flex flex-wrap w-full">
                                            <div class="-mr-3 flex flex-wrap flex-row">
                                                <img :src="event.created_by.profile_photo_url" alt=""
                                                     class="mx-auto shrink-0 flex object-cover rounded-full"
                                                     :class="['h-' + 6, 'w-' + 6]">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </vue-cal>

            </div>
        </div>

    </div>
    <div class="ml-12">
        <CalendarFilterTagComponent
            class="flex"
            :calendar-filters="calendarFilters"
            :event-attributes="eventAttributes"
            :room-filters="roomFilters"
            :events-since="eventsSince"
            :events-until="eventsUntil"
        />
    </div>

    <!-- Termin erstellen Modal-->
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="selectedEvent"
        :wantedRoomId="wantedSplit"
        :isAdmin="this.hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="this.filteredEvents"
        :isAdmin="this.hasAdminRole()"
        :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
    />
</template>

<script>

import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    DotsVerticalIcon,
    ExclamationIcon,
    FilterIcon,
    PencilAltIcon,
    PlusCircleIcon,
    TrashIcon,
    XCircleIcon,
    XIcon,
} from '@heroicons/vue/outline';
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Link} from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import CalendarFilterTagComponent from "@/Layouts/Components/CalendarFilterTagComponent.vue";
import Button from "@/Jetstream/Button.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: 'CalendarComponent',
    mixins: [Permissions, IconLib],
    components: {
        CalendarFunctionBar,
        DatePickerComponent,
        NewUserToolTip,
        BaseFilter,
        PlusCircleIcon,
        ExclamationIcon,
        Button,
        CalendarFilterTagComponent,
        CalendarIcon,
        FilterIcon,
        SwitchLabel,
        SwitchGroup,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        VueCal,
        JetDialogModal,
        XIcon,
        DocumentTextIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        ChevronLeftIcon,
        ChevronRightIcon,
        SvgCollection,
        CheckIcon,
        Switch,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        Link,
        EventComponent,
        EventsWithoutRoomComponent,
        UserTooltip,
    },
    props: [
        'project',
        'room',
        'initialView',
        'eventTypes',
        'atAGlance',
        'dateValue',
        'selectedDate',
        'events',
        'rooms',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'first_project_calendar_tab_id'
    ],
    emits: ['changeAtAGlance'],
    mounted() {
        window.addEventListener('resize', this.listenToFullscreen);
    },
    data() {
        return {
            displayDate: '',
            filters: [],
            dateValueArray: this.dateValue ? this.dateValue : [],
            filterIds: {},
            filterName: '',
            wantedSplit: null,
            eventsSinceDateValue: null,
            eventsUntilDateValue: null,
            calendarFilters: {
                rooms: [],
                areas: [],
                eventTypes: [],
                roomAttributes: [],
                roomCategories: [],
                isLoud: null,
                isNotLoud: null,
                hasAudience: null,
                hasNoAudience: null,
                adjoiningNoAudience: null,
                adjoiningNotLoud: null,
                allDayFree: null,
                showAdjoiningRooms: null
            },
            saving: false,
            roomFilters: {
                showAdjoiningRooms: false,
                allDayFree: false
            },
            eventAttributes: {
                isLoud: {
                    name: this.$t('loud'),
                    value: 'isLoud',
                    checked: false
                },
                isNotLoud: {
                    name: this.$t('not loud'),
                    value: 'isNotLoud',
                    checked: false
                },
                adjoiningNotLoud: {
                    name: this.$t('without a loud side event'),
                    value: 'adjoiningNotLoud',
                    checked: false
                },
                hasAudience: {
                    name: this.$t('With audience'),
                    value: 'hasAudience',
                    checked: false
                },
                hasNoAudience: {
                    name: this.$t('without audience'),
                    value: 'hasNoAudience',
                    checked: false
                },
                adjoiningNoAudience: {
                    name: this.$t('without side event with audience'),
                    value: 'adjoiningNoAudience',
                    checked: false
                },
            },
            types: [],
            showFreeRooms: false,
            showAdjoiningRooms: false,
            myRooms: [],
            displayedEvents: [],
            areas: [],
            displayedRooms: [],
            projects: [],
            selectedEvent: null,
            collision: 0,
            eventsSince: null,
            eventsUntil: null,
            deletingEvent: false,
            currentView: this.initialView ?? 'week',
            roomCategories: [],
            roomAttributes: [],
            eventComponentIsVisible: false,
            createEventComponentIsVisible: false,
            showEventsWithoutRoomComponent: false,
            roomCollisions: [],
            isFullscreen: false,

        }
    },
    created() {
        Echo.private('events')
            .listen('OccupancyUpdated', () => {
                this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
            });
    },
    watch: {
        events: {
            handler() {
                this.initializeCalendar({view: null, startDate: null, endDate: null})
            },
        },
        rooms: {
            handler() {
                this.initializeCalendar({view: null, startDate: null, endDate: null})
            },
        }
    },
    computed: {
        filteredEvents() {
            return this.eventsWithoutRoom.filter((event) => {
                let createdBy = event.created_by;
                let projectLeaders = event.projectLeaders;

                if (projectLeaders && projectLeaders.length > 0) {
                    if (createdBy.id === this.$page.props.user.id || projectLeaders.some((leader) => leader.id === this.$page.props.user.id)) {
                        return true;
                    }
                } else if (createdBy.id === this.$page.props.user.id) {
                    return true;
                }

                return false;
            });
        }
    },
    methods: {
        backgroundColorWithOpacity(eventColor) {
            const color = eventColor;
            return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, 15%)`;
        },
        TextColorWithDarken(eventColor) {
            const color = eventColor;
            return `rgb(${parseInt(color.slice(-6, -4), 16) - 75}, ${parseInt(color.slice(-4, -2), 16) - 75}, ${parseInt(color.slice(-2), 16) - 75})`;
        },
        initializeCalendar({view = null, startDate = null, endDate = null}) {
            this.currentView = 'day';

            this.scrollToNine();

            this.eventsSince = startDate ?? this.eventsSince;
            this.eventsUntil = endDate ?? this.eventsUntil;

            this.events?.map(event => event.start = this.convertDateFormat(new Date(event.start)))
            this.events?.map(event => event.end = this.convertDateFormat(new Date(event.end)))

            //split is needed for the vue-cal component to connect the events with the rooms
            //class is needed for design purposes
            this.events?.forEach((event) => {
                event.split = event.roomId;
                event.class = event.event_type.hex_code;
            })
            this.displayedEvents = this.events
            this.displayedRooms = this.rooms
        },
        convertDateFormat(dateString) {
            // Create a new Date object using the original date string
            const date = new Date(dateString);

            // Pad the month and minutes with leading zeros if necessary
            const pad = (number) => number < 10 ? '0' + number : number;

            // Construct the formatted date string
            const year = date.getFullYear();
            const month = pad(date.getMonth() + 1); // getMonth() returns 0-11
            const day = pad(date.getDate());
            const hours = pad(date.getHours());
            const minutes = pad(date.getMinutes());

            // Combine the parts into the final string
            const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}`;

            return formattedDate;
        },
        changeAtAGlance() {
            this.$emit('changeAtAGlance')
        },
        async getAllDayFreeRooms() {
            let rooms = []
            await axios.get('/rooms/free', {
                params: {
                    start: this.eventsSince,
                    end: this.eventsUntil,
                }
            }).then(response => {
                rooms = response.data.rooms;
            })
            return rooms
        },
        openEventComponent(event = null) {

            this.wantedSplit = event?.split;
            if (event === null) {
                this.selectedEvent = null;
                if (this.isFullscreen) {
                    document.exitFullscreen();
                    this.isFullscreen = false;
                }

                this.createEventComponentIsVisible = true;
                return;
            }

            if (!event.id) {
                event = {
                    start: event?.start,
                    end: event?.end,
                    projectId: this.project?.id,
                    projectName: this.project?.name,
                    roomId: event.roomId,
                }
            }

            if (event?.start && event?.end) {
                axios.post('/collision/room', {
                    params: {
                        start: event?.start,
                        end: event?.end,
                    }
                })
                    .then(response => this.roomCollisions = response?.data);
            }
            this.selectedEvent = event;
            if (this.isFullscreen) {
                document.exitFullscreen();
                this.isFullscreen = false;
            }
            this.selectedEvent.room_id = event?.roomId;
            this.selectedEvent.eventTypeId = event?.event_type?.id;
            this.selectedEvent.projectId = event?.project_id;
            this.createEventComponentIsVisible = true;
        },
        openEventsWithoutRoomComponent() {
            this.showEventsWithoutRoomComponent = true;
        },

        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            router.reload();
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            router.reload();
        },
        scrollToNine() {
            if (this.currentView === 'month') {
                return;
            }
            const calendar = document.querySelector('#vuecal .vuecal__bg')
            if (calendar?.scrollTop < 326.1111145019531) {
                calendar?.scrollTo({top: 9 * 120, behavior: 'smooth'})
            }

        },
        areChecked(array) {
            let count = 0;
            array.forEach(object => {
                if (object.checked) {
                    count++;
                }
            })
            return count
        },
        // Currently not used
        setDisplayDate(view, startDate) {

            if (view === 'week') {
                let beginOfYear = new Date(startDate.getFullYear(), 0, 1);
                let days = Math.floor((startDate - beginOfYear) /
                    (24 * 60 * 60 * 1000));

                let weekNumber = Math.ceil(days / 7);
                this.displayDate = 'Woche - KW ' + weekNumber + ' ' + startDate.toLocaleDateString('de-DE', {year: 'numeric'})
            } else if (view === 'month') {
                this.displayDate = "Monat - " + startDate.toLocaleDateString('de-DE', {month: 'long', year: 'numeric'})
            } else {
                this.displayDate = "Jahr - " + startDate.toLocaleDateString('de-DE', {year: 'numeric'})
            }
        },
        openFullscreen() {
            let elem = document.getElementById("myCalendar");
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        },
        nextDay() {
            this.$refs.vuecal.next();
            this.dateValueArray[0] = this.addOneDay(this.dateValueArray[0]);
            this.dateValueArray[1] = this.addOneDay(this.dateValueArray[1]);
            router.patch(route('update.user.calendar.filter.dates', this.$page.props.user.id), {
                start_date:  this.dateValueArray[0],
                end_date: this.dateValueArray[1],
            },{
                preserveScroll: true
            })
        },
        previousDay() {
            this.$refs.vuecal.previous();
            this.dateValueArray[0] = this.subtractOneDay(this.dateValueArray[0]);
            this.dateValueArray[1] = this.subtractOneDay(this.dateValueArray[1]);
            router.patch(route('update.user.calendar.filter.dates', this.$page.props.user.id), {
                start_date:  this.dateValueArray[0],
                end_date: this.dateValueArray[1],
            },{
                preserveScroll: true
            })
        },
        formatDate(date) {
            return new Date((new Date(date)).getTime() - ((new Date(date)).getTimezoneOffset() * 60000)).toISOString().slice(0, 10);
        },
        subtractOneDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        addOneDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        listenToFullscreen() {
            if (window.innerHeight === screen.height) {
                this.isFullscreen = true;
            } else {
                this.isFullscreen = false;
                this.zoomFactor = 1;
            }
        },
    }
}
</script>

<style>
/* Styling of Vue Cal */

.day-split-header {
    min-width: 200px;
    text-align: center;
    font: normal normal 13px/18px Inter;
    letter-spacing: -0.2px;
    color: #FCFCFB;
    opacity: 1;
}

.vuecal__no-event {
    display: none;
}

.vuecal__flex {
}

.vuecal__cell-events-count {
    color: #3017AD;
    background-color: rgba(48, 23, 173, 0.1);
    height: 18px;
    width: 18px;
    justify-content: center; /* Centering Horizantly */
    align-items: center;
    display: flex;


}

.vuecal__bg {

}

.vuecal__weekdays-headings {
    background-color: #D8D7DE;
    letter-spacing: -0.01em;
    line-height: 18px;
    text-align: center;
    color: #fcfcfb;
    z-index: 10;
}

.vuecal__flex .vuecal__week-numbers {
    background-color: #D8D7DE;
}

.vuecal__flex .vuecal__split-days-headers {
    background-color: #828190;
}

.vuecal__flex .vuecal__week-number-cell {
    color: #27233C;
    font-size: 0.81rem;
}

.vuecal__week-numbers .vuecal__week-number-cell {
    opacity: 1;
}

.vuecal--month-view .vuecal__cell {
    height: 95px;
}

.vuecal--month-view .vuecal__cell-content {
    justify-content: flex-start;
    align-items: flex-end;
    overflow-y: auto;
}

.vuecal--month-view .vuecal__cell-date {
    padding: 4px;
}

.vuecal--month-view .vuecal__event {
    padding-top: 0px;

}

.vuecal--month-view .vuecal__no-event {
    display: none;
}

.vuecal__flex .vuecal__cell-content .vuecal__cell-split {
    min-width: 200px !important;
}

.vuecal__event {
    font-size: 0.75rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
    border-right: 3px solid #ffffff;
    overflow: overlay;
}


.vuecal__event-time {
    font-size: 12px;
    letter-spacing: -0.01em;
    line-height: 18px;
    color: #a7a6b1;
}

.vuecal__title {
    font-size: 2rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
    padding-top: 22px;
    background-color: white;
}

.vuecal--month-view .vuecal__cell {
    height: 10rem;
}

.vuecal__view-btn {
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__title-bar {
    background-color: #ffffff;
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__split-days-headers {
    font-size: 0.75rem; /* 12px */
    line-height: 1rem; /* 16px */
    color: #ffffff;
}

.vuecal__flex.weekday-label {
    text-align: center;
    font: normal normal 600 13px/18px Inter;
    letter-spacing: -0.2px;
    color: #27233C;
    border-right: 2px solid #828190;
    opacity: 1;
}

.vuecal__header {
    background-color: #828190;
}

.vuecal__cell--today .vuecal__cell--current {
    background-color: rgba(48, 23, 173, 0.02) !important;
}

.vuecal__cell--selected {
    background-color: rgba(48, 23, 173, 0.02) !important;
}

.vuecal__cell-split {
    border: 1px solid #D8D7DE;
}

.vuecal--month-view .vuecal__cell {
    height: 95px;
}

.vuecal--month-view .vuecal__cell-content {
    justify-content: flex-start;
    height: 100%;
    align-items: flex-end;
    overflow-y: auto;
}

.vuecal--month-view .vuecal__cell-date {
    padding: 4px;
}

.vuecal--month-view .vuecal__event {
    padding-top: 0px;
}

.vuecal--month-view .vuecal__cell {
    height: 10rem;
}

.vuecal--month-view .vuecal__no-event {
    display: none;
}


/* Custom Event Type Colors */
.vuecal__event.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}

.occupancy_option_eventType0 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #d2d2d2 8px);
}

.occupancy_option_eventType1 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e0abd1 8px);
}

.occupancy_option_eventType2 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #f6b9d4 8px);
}

.occupancy_option_eventType3 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, rgb(227, 185, 162) 8px);
}

.occupancy_option_eventType4 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e5c386 8px);
}

.occupancy_option_eventType5 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #cce5ac 8px);
}

.occupancy_option_eventType6 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #b0e8c5 8px);
}

.occupancy_option_eventType7 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a9dde1 8px);
}

.occupancy_option_eventType8 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a0cfe5 8px);
}

.occupancy_option_eventType9 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a6dcda 8px);
}

.occupancy_option_eventType10 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a2cbe0 8px);
}

.vuecal__event.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.vuecal__event.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.vuecal__event.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.vuecal__event.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.vuecal__event.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.vuecal__event.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.vuecal__event.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.vuecal__event.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.vuecal__event.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.vuecal__event.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.vuecal__event.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}

.centered {
    position: relative;
    top: 50%;
    transform: translateY(-50%);
    -webkit-transform: translateY(-50%);
}
</style>
