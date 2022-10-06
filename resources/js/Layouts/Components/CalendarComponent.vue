<template>

    <div class="flex justify-end mb-5">

        <!-- Calendar Filter -->
        <Menu as="div" class="relative inline-block text-left w-80">
            <div>
                <MenuButton
                    class="mt-1 border border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >
                    <span class="float-left">Filter</span>
                    <ChevronDownIcon class="ml-2 -mr-1 h-5 w-5 text-primary float-right" aria-hidden="true"/>
                </MenuButton>
            </div>
            <MenuItems
                class="absolute right-0 mt-2 w-80 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                <div class="inline-flex border-none w-1/5">
                    <button>
                        <FilterIcon class="w-3 mr-1 mt-0.5"/>
                    </button>
                </div>
                <div class="inline-flex border-none justify-end w-4/5">
                    <button class="flex">
                        <XIcon class="w-3 mr-1 mt-0.5"/>
                        <div class="text-xs">Zurücksetzen</div>
                    </button>
                    <button class="flex ml-4" @click="saving = !saving">
                        <DocumentTextIcon class="w-3 mr-1 mt-0.5"/>
                        <div class="text-xs">Speichern</div>
                    </button>
                </div>
                <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">

                    <!-- Save Filter Section -->
                    <Disclosure v-slot="{ open }" v-if="saving" default-open>
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                Gespeicherte Filter
                            </span>
                            <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white flex justify-between">
                            <input id="saveFilter" v-model="filterName" type="text" autocomplete="off"
                                class="shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                placeholder="Name des Filters"/>
                            <AddButton text="Speichern" class="text-sm ml-0"></AddButton>
                        </DisclosurePanel>
                        <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                    </Disclosure>

                    <!-- Room Filter Section -->
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                Räume
                            </span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="currentView !== 'month'">
                                <SwitchGroup>
                                    <div class="flex items-center">
                                        <Switch v-model="roomFilters.showAdjoiningRooms"
                                            :class="roomFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                            class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full"/>
                                        </Switch>
                                        <SwitchLabel class="ml-4 text-xs"
                                            :class="roomFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                            Nebenräume anzeigen
                                        </SwitchLabel>
                                    </div>
                                </SwitchGroup>
                                <SwitchGroup>
                                    <div class="flex items-center mt-2">
                                        <Switch v-model="roomFilters.onlyFreeRooms"
                                            :class="roomFilters.onlyFreeRooms ? 'bg-white' : 'bg-darkGray'"
                                            class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.onlyFreeRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full"/>
                                        </Switch>
                                        <SwitchLabel class="ml-4 text-xs"
                                            :class="roomFilters.onlyFreeRooms ? 'text-white' : 'text-secondary'">
                                            Nur freie Räume anzeigen
                                        </SwitchLabel>
                                    </div>
                                </SwitchGroup>

                                <Menu as="div" v-if="roomFilters.onlyFreeRooms">
                                    <div>
                                        <MenuButton class="p-2 my-4 text-darkInputText bg-darkInputBg border border-secondary flex w-full justify-between">
                                            <label v-if="currentInterval === ''" class="text-sm">
                                                Zeitraum auswählen
                                            </label>
                                            <label v-else class="text-sm">{{ currentInterval }}</label>
                                            <ChevronDownIcon class="h-4 w-4 shadow-sm text-white mt-0.5 float-right"></ChevronDownIcon>
                                        </MenuButton>
                                    </div>

                                    <MenuItems
                                        class="z-40 origin-top-left absolute overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none w-2/3">
                                        <MenuItem v-for="interval in freeTimeIntervals" v-slot="{ active }">
                                            <div @click="currentInterval = interval"
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary',
                                                  'group px-3 py-2 text-sm ']">
                                                {{ interval }}
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </Menu>
                            </div>

                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Raumkategorien</span>
                                    <ChevronDownIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-4 w-4 mt-0.5 text-white"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-if="roomCategories.length > 0" v-for="category in roomCategories"
                                        class="flex w-full mb-2">
                                        <input type="checkbox" v-model="category.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[category.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ category.name }}</p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Kategorien angelegt</div>
                                </DisclosurePanel>
                            </Disclosure>
                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Areale</span>
                                    <ChevronDownIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-4 w-4 mt-0.5 text-white"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-if="areas.length > 0" v-for="area in areas" class="flex w-full mb-2">
                                        <input type="checkbox" v-model="area.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[area.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ area.name }}</p>
                                    </div>
                                    <div v-else class="text-secondary">Keine Areale angelegt</div>
                                </DisclosurePanel>
                            </Disclosure>
                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                        Raumeigenschaften
                                    </span>
                                    <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-if="roomAttributes.length > 0" v-for="attribute in roomAttributes"
                                        class="flex w-full mb-2">
                                        <input type="checkbox" v-model="attribute.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[attribute.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ attribute.name }}</p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Raumeigenschaften angelegt</div>
                                </DisclosurePanel>
                            </Disclosure>
                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                                    <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-if="rooms.length > 0" v-for="room in rooms"
                                        class="flex w-full mb-2">
                                        <input type="checkbox" v-model="room.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[room.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ room.name }}</p>
                                    </div>
                                    <div v-else class="text-secondary">Noch keine Räume angelegt</div>
                                </DisclosurePanel>
                            </Disclosure>
                        </DisclosurePanel>
                    </Disclosure>

                    <hr class="border-secondary rounded-full border-2 mt-2 mb-2">

                    <!-- Event Filter Section -->
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                        >
                                <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                    Termine
                                </span>
                            <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                        </DisclosureButton>
                        <DisclosurePanel class=" pt-2 pb-2 text-sm text-white">
                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termintyp</span>
                                    <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-for="eventType in eventTypes" class="flex w-full mb-2">
                                        <input type="checkbox" v-model="eventType.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[eventType.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ eventType.name }}</p>
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                            <hr class="border-gray-500 mt-2 mb-2">
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">
                                        Termineigenschaften
                                    </span>
                                    <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-for="eventAttribute in eventAttributes" class="flex w-full mb-2">
                                        <input type="checkbox" v-model="eventAttribute.checked"
                                            class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                        <p :class="[eventAttribute.checked ? 'text-white' : 'text-secondary', '']"
                                            class="ml-1.5 text-xs align-text-middle">
                                            {{ eventAttribute.name }}</p>
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                        </DisclosurePanel>
                    </Disclosure>

                </div>
            </MenuItems>
        </Menu>
        <AddButton class="bg-primary hover:bg-secondary text-white" @click="openEventComponent()" text="Neue Belegung"/>
    </div>

    <!--  Calendar  -->
    <div>
        <vue-cal
            style="height: 500px"
            today-button
            events-on-month-view="short"
            locale="de"

            :stickySplitLabels="true"
            :disable-views="['years']"
            :events="events"
            :split-days="displayedRooms"
            :editable-events="{ title: false, drag: true, resize: false, delete: true, create: true }"
            :snap-to-time="15"
            :drag-to-create-threshold="15"
            :active-view="initialView ?? 'week'"

            @event-drag-create="openEventComponent($event)"
            @event-focus="openEventComponent($event)"

            @ready="fetchEvents"
            @view-change="fetchEvents($event)"
        />
    </div>

    <!-- Termin erstellen Modal-->
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :event="selectedEvent"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
    />
</template>

<script>

import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import {ChevronDownIcon, DocumentTextIcon, FilterIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import AddButton from "@/Layouts/Components/AddButton";
import EventComponent from "@/Layouts/Components/EventComponent";

export default {
    name: 'CalendarComponent',
    components: {
        ChevronDownIcon,
        SwitchLabel,
        SwitchGroup,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        FilterIcon,
        VueCal,
        XIcon,
        DocumentTextIcon,
        EventTypeIconCollection,
        SvgCollection,
        Switch,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        AddButton,
        EventComponent,
    },
    props: ['project', 'room', 'initialView', 'eventTypes'],
    data() {
        return {
            currentInterval: '',
            freeTimeIntervals: [
                'min 0.5h',
                'min 1h',
                'min 2h',
                'min 3h',
                'min 4h',
                'min 5h',
                'min 6h',
                'ganztägig',
            ],
            saving: false,
            roomFilters: {
                showAdjoiningRooms: false,
                onlyFreeRooms: false
            },
            eventAttributes: [
                {
                    name: 'laut',
                    value: 'is_loud',
                    checked: true
                },
                {
                    name: 'nicht laut',
                    value: 'is_not_loud',
                    checked: false
                },
                {
                    name: 'ohne laute Nebenveranstaltung',
                    value: 'adjoining_not_loud',
                    checked: false
                },
                {
                    name: 'mit Publikum',
                    value: 'public',
                    checked: true
                },
                {
                    name: 'ohne Publikum',
                    value: 'no_public',
                    checked: false
                },
                {
                    name: 'ohne Nebenveranstaltung mit Publikum',
                    value: 'adjoining_not_public',
                    checked: false
                }
            ],
            showAdjoiningRooms: false,
            selectedRoom: null,
            events: [],
            areaFilter: [],
            roomFilter: [],
            typeFilter: [],
            rooms: [],
            roomCategories: [],
            roomAttributes: [],
            areas: [],
            displayedRooms: [],
            projects: [],
            selectedEvent: null,
            eventsSince: null,
            eventsUntil: null,
            eventComponentIsVisible: false,
            currentView: this.initialView ?? 'week',
            createEventComponentIsVisible: false,
        }
    },
    methods: {

        openEventComponent(event = null) {
            if (event === null) {
                this.selectedEvent = null;
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

            this.selectedEvent = event;
            this.createEventComponentIsVisible = true;
        },

        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            this.fetchEvents();
        },

        /**
         * Fetch the events from server
         * initialise possible rooms, types and projects
         *
         * @param view
         * @param {Date} startDate
         * @param {Date} endDate
         * @returns {Promise<void>}
         */
        async fetchEvents({view = null, startDate = null, endDate = null}) {

            this.currentView = view ?? this.currentView ?? 'week';
            const colors = ['blue', 'pink', 'green']

            this.eventsSince = startDate ?? this.eventsSince;
            this.eventsUntil = endDate ?? this.eventsUntil;

            await axios
                .get('/events/', {
                    params: {
                        start: this.eventsSince,
                        end: this.eventsUntil,
                        projectId: this.projectId,
                        roomId: this.roomId,
                    }
                })
                .then(response => {
                    this.events = response.data.events
                    this.types = response.data.types
                    this.rooms = response.data.rooms
                    this.projects = response.data.projects
                    this.roomCategories = response.data.roomCategories
                    this.roomAttributes = response.data.roomAttributes
                    this.areas = response.data.areas

                    this.rooms.map(room => room.checked = false);
                    this.roomCategories.map(roomCategory => roomCategory.checked = false);
                    this.roomAttributes.map(roomAttribute => roomAttribute.checked = false);
                    this.areas.map(area => area.checked = false);
                    this.eventTypes.map(eventType => eventType.checked = false);
                    this.rooms.map(room => room.checked = false);

                    // color coding of rooms
                    this.events.map(event => event.class = colors[event.split % colors.length])

                    // fix timezone to current local
                    this.events.map(event => event.start = new Date(event.start))
                    this.events.map(event => event.end = new Date(event.end))
                });
        },
    },
}
</script>

<style>
/* Styling of Vue Cal */

.vuecal--month-view .vuecal__cell {
    height: 75px;
}

.vuecal--month-view .vuecal__cell-content {
    justify-content: flex-start;
    height: 100%;
    align-items: flex-end;
    overflow: hidden;
}

.vuecal--month-view .vuecal__cell-date {
    padding: 4px;
}

.vuecal--month-view .vuecal__no-event {
    display: none;
}

.vuecal__event {
    font-size: 0.75rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
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
}

.vuecal__flex.weekday-label {
    font-size: 1rem; /* 12px */
    line-height: 1rem; /* 16px */
    color: #707070;
}

/* Custom Room Colors */

.vuecal__event.blue {
    border: solid rgba(135, 208, 224, 0.9);
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.pink {
    border: solid rgba(209, 130, 211, 0.9);
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.green {
    border: solid rgba(148, 236, 145, 0.9);
    border-width: 0px 0px 0px 3px;
}
</style>
