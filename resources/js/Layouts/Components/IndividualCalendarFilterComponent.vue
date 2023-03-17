<template>
    <BaseFilter use-icon="true">
        <div class="inline-flex border-none justify-end w-full">
            <button class="flex" @click="resetCalendarFilter">
                <XIcon class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs">Zurücksetzen</label>
            </button>
            <button class="flex ml-4" @click="saving = !saving">
                <DocumentTextIcon class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs">Speichern</label>
            </button>
        </div>
        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">

            <!-- Save Filter Section -->
            <Disclosure v-slot="{ open }" default-open>
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                >
                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Gespeicherte Filter</span>
                    <ChevronDownIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <div v-if="saving">
                        <div class="flex">
                            <input id="saveFilter" v-model="filterName" type="text"
                                   class="shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                   placeholder="Name des Filters"/>
                            <button
                                class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1 ml-2">
                                <label @click="saveFilter"
                                       class="cursor-pointer text-white text-xs">Speichern</label>
                            </button>
                            <!-- <AddButton text="Speichern" class="text-sm ml-0"
                                       @click="saveFilter"></AddButton> -->
                        </div>
                        <hr class="border-gray-500 mt-4 mb-4">
                    </div>
                    <button
                        class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1"
                        v-for="filter of filters">
                        <label @click="applyFilter(filter)"
                               class="cursor-pointer text-white">{{ filter.name }}</label>
                        <XIcon @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                    </button>
                    <p v-if="filters.length === 0" class="text-secondary py-1">Noch keine Filter
                        gespeichert</p>
                </DisclosurePanel>
                <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
            </Disclosure>

            <!-- Room Filter Section -->
            <Disclosure v-slot="{ open }">
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                    <ChevronDownIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <div>
                        <SwitchGroup>
                            <div class="flex items-center">
                                <Switch v-model="roomFilters.showAdjoiningRooms"
                                        @click="this.changeFilterBoolean('showAdjoiningRooms', roomFilters.showAdjoiningRooms); this.changeDisplayedRooms()"
                                        :class="roomFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                </Switch>
                                <SwitchLabel class="ml-4 text-xs"
                                             :class="roomFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                    Nebenräume anzeigen
                                </SwitchLabel>
                            </div>
                        </SwitchGroup>
                        <SwitchGroup v-if="currentView === 'day'">
                            <div class="flex items-center mt-2">
                                <Switch v-model="roomFilters.allDayFree"
                                        @click="this.changeFilterBoolean('allDayFree', roomFilters.allDayFree);"
                                        :class="roomFilters.allDayFree ? 'bg-white' : 'bg-darkGray'"
                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.allDayFree ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                </Switch>
                                <SwitchLabel class="ml-4 text-xs"
                                             :class="roomFilters.allDayFree ? 'text-white' : 'text-secondary'">
                                    ganztägig frei
                                </SwitchLabel>
                            </div>
                        </SwitchGroup>

                        <!--
                        <Menu as="div" v-if="calendarFilters.allDayFree">
                            <div>
                                <MenuButton
                                    class="p-2 my-4 text-darkInputText bg-darkInputBg border border-secondary flex w-full justify-between">
                                    <label v-if="currentInterval === ''" class="text-sm">Zeitraum
                                        auswählen</label>
                                    <label v-else class="text-sm">{{ currentInterval }}</label>
                                    <ChevronDownIcon
                                        class="h-4 w-4 shadow-sm text-white mt-0.5 float-right"></ChevronDownIcon>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="z-40 origin-top-left absolute overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none w-2/3">
                                    <MenuItem v-for="interval in freeTimeIntervals" v-slot="{ active }">
                                        <div @click="currentInterval = interval"
                                             :class="[active ? 'bg-primaryHover text-white' : 'text-secondary',
                                  'group px-3 py-2 text-sm subpixel-antialiased']">
                                            {{ interval }}
                                        </div>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu> -->
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
                                       @change="this.changeFilterElements(calendarFilters.roomCategories, 'roomCategories', category)"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[category.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
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
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Areale</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="areas.length > 0" v-for="area in areas" class="flex w-full mb-2">
                                <input type="checkbox" v-model="area.checked"
                                       @change="this.changeFilterElements(calendarFilters.areas,'areas', area);"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[area.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ area.label || area.name }}</p>
                            </div>
                            <div v-else class="text-secondary">Keine Areale angelegt</div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Raumeigenschaften</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="roomAttributes.length > 0" v-for="attribute in roomAttributes"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="attribute.checked"
                                       @change="this.changeFilterElements(calendarFilters.roomAttributes,'roomAttributes', attribute);"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[attribute.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ attribute.name }}</p>
                            </div>
                            <div v-else class="text-secondary">Noch keine Raumeigenschaften angelegt
                            </div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="rooms.length > 0" v-for="room in rooms"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="room.checked"
                                       @change="this.changeFilterElements(calendarFilters.rooms,'rooms', room)"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[room.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ room.label }}</p>
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
                                <span
                                    :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termine</span>
                    <ChevronDownIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termintyp</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-for="eventType in types" class="flex w-full mb-2">
                                <input type="checkbox" v-model="eventType.checked"
                                       @change="this.changeFilterElements(calendarFilters.eventTypes,'eventTypes', eventType)"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[eventType.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ eventType.name }}</p>
                            </div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termineigenschaften</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-for="eventAttribute in eventAttributes" class="flex w-full mb-2">
                                <input type="checkbox" v-model="eventAttribute.checked"
                                       @change="this.changeFilterBoolean(eventAttribute.value, eventAttribute.checked)"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[eventAttribute.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ eventAttribute.name }}</p>
                            </div>
                        </DisclosurePanel>
                    </Disclosure>
                </DisclosurePanel>
            </Disclosure>

        </div>
    </BaseFilter>
</template>

<script>
import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Menu,
    MenuButton,
    MenuItems, Switch, SwitchGroup, SwitchLabel,
} from "@headlessui/vue";

import {
    ChevronDownIcon, DocumentTextIcon,
} from '@heroicons/vue/outline';
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {XIcon} from "@heroicons/vue/solid";

export default {
    name: "IndividualCalendarComponent",
    components: {
        SwitchLabel,
        Switch,
        SwitchGroup,
        DisclosureButton,
        DisclosurePanel,
        Disclosure,
        Menu,
        MenuItems,
        MenuButton,
        ChevronDownIcon,
        BaseFilter,
        XIcon,
        DocumentTextIcon

    },
    data() {
        return {
            displayDate: '',
            filters: [],
            filterIds: {},
            filterName: '',
            wantedSplit: null,
            selectedDate: null,
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
                    name: 'laut',
                    value: 'isLoud',
                    checked: false
                },
                isNotLoud: {
                    name: 'nicht laut',
                    value: 'isNotLoud',
                    checked: false
                },
                adjoiningNotLoud: {
                    name: 'ohne laute Nebenveranstaltung',
                    value: 'adjoiningNotLoud',
                    checked: false
                },
                hasAudience: {
                    name: 'Mit Publikum',
                    value: 'hasAudience',
                    checked: false
                },
                hasNoAudience: {
                    name: 'ohne Publikum',
                    value: 'hasNoAudience',
                    checked: false
                },
                adjoiningNoAudience: {
                    name: 'ohne Nebenveranstaltung mit Publikum',
                    value: 'adjoiningNoAudience',
                    checked: false
                },
            },
            types: [],
            showFreeRooms: false,
            showAdjoiningRooms: false,
            myRooms: [],
            events: [],
            eventsWithoutRoom: [],
            displayedEvents: [],
            rooms: [],
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

        }
    },
    props:['useIcon'],
    methods: {
        openEventComponent(event = null) {

            this.wantedSplit = event?.split;
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

            if (event?.start && event?.end) {
                axios.post('/collision/room', {
                    params: {
                        start: event?.start,
                        end: event?.end,
                    }
                })
                    .then(response => this.roomCollisions = response.data);
            }
            this.selectedEvent = event;
            this.createEventComponentIsVisible = true;
        },
        resetCalendarFilter() {
            this.addFilterableVariable(this.rooms, false);
            this.addFilterableVariable(this.areas, false);
            this.addFilterableVariable(this.roomCategories, false);
            this.addFilterableVariable(this.roomAttributes, false);
            this.addFilterableVariable(this.types, false);
            Object.entries(this.calendarFilters).forEach(entry => {
                if (Array.isArray(entry[1])) {
                    entry[1].length = 0;
                } else {
                    this.calendarFilters[entry[0]] = null;
                }
            })

            Object.entries(this.eventAttributes).forEach(entry => {
                entry[1].checked = null;
            })

            this.displayedRooms = this.rooms

            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil
            });
        },
        async saveFilter() {
            const filterIds = this.getFilterIds();
            await axios.post('/filters', {name: this.filterName, calendarFilters: filterIds}).then(() => {
                this.filterName = ""
            })
            await axios.get('/filters')
                .then(response => {
                    this.filters = response.data;
                })
        },
        applyFilter(filter) {
            this.calendarFilters = filter;
            this.changeChecked(this.rooms, 'rooms')
            this.changeChecked(this.areas, 'areas')
            this.changeChecked(this.roomCategories, 'roomCategories')
            this.changeChecked(this.roomAttributes, 'roomAttributes')
            this.changeChecked(this.types, 'eventTypes')

            Object.entries(this.eventAttributes).forEach(entry => {
                Object.entries(this.calendarFilters).forEach(filterEntry => {
                    if (entry[1].value === filterEntry) {
                        entry[1].checked = true
                    }
                })
            })

            this.changeDisplayedRooms();

            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
        },
        async deleteFilter(id) {
            await axios.delete(`/filters/${id}`)
            this.resetCalendarFilter();
            await axios.get('/filters')
                .then(response => {
                    this.filters = response.data
                })
        },
        async changeFilterBoolean(filter, variable) {
            this.calendarFilters[`${filter}`] = variable
            await this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
            await this.changeDisplayedRooms()
        },
        async changeDisplayedRooms() {

            let allRooms = this.rooms;
            if (this.calendarFilters.allDayFree === true) {
                allRooms = await this.getAllDayFreeRooms();
            }

            //decides for every room if it should be displayed in the calendar
            this.displayedRooms = allRooms.filter(room => {
                let include = false;

                this.areas.forEach(area => {
                    //if the current area is checked
                    if (area.checked) {
                        if (room.area.name === area.name) {
                            include = this.filterRooms(room)
                        }
                        //checks if no area is checked
                    } else if (this.zeroObjectsChecked(this.areas)) {
                        include = this.filterRooms(room)
                        //at least one area is checked, but not the current one
                    } else {
                        if (room.area.name === area.name) {
                            include = false;
                        }
                    }
                })
                return include
            });
            this.viewAdjoiningRooms()
        },
        async changeFilterElements(filterArray, arrayName, element) {

            if (element.checked) {
                filterArray.push(element)
            } else {
                // entry[0] is the key, e.g. room_categories. entry[1] is the array corresponding to the key.
                Object.entries(this.calendarFilters).forEach(entry => {
                    if (Array.isArray(entry[1]) && entry[1].length > 0 && arrayName === entry[0]) {
                        if (arrayName === 'rooms') {
                            const room = this.rooms.filter(room => element?.id === room.id)
                            if (room) {
                                room[0].checked = false
                            }
                        }
                        if (arrayName === 'areas') {
                            const area = this.areas.filter(area => element?.id === area.id)
                            if (area) {
                                area[0].checked = false
                            }
                        }
                        if (arrayName === 'roomCategories') {
                            const category = this.roomCategories.filter(category => element?.id === category.id)
                            if (category) {
                                category[0].checked = false
                            }
                        }
                        if (arrayName === 'roomAttributes') {
                            const attribute = this.roomAttributes.filter(room => element?.id === room.id)
                            if (attribute) {
                                attribute[0].checked = false
                            }
                        }
                        if (arrayName === 'eventTypes') {
                            const eventType = this.eventTypes.filter(type => element?.id === type.id)
                            if (eventType) {
                                eventType[0].checked = false
                            }
                        }

                        this.calendarFilters[entry[0]] = filterArray.filter(elem => element?.id !== elem.id)
                    }
                })
            }
            await this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
            await this.changeDisplayedRooms()
        },
    }
}
</script>

<style scoped>

</style>
