<template>
    <BaseFilter onlyIcon="true">
        <div class="inline-flex border-none justify-end w-full">
            <button class="flex" @click="resetCalendarFilter">
                <XIcon class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs cursor-pointer">Zurücksetzen</label>
            </button>
            <button class="flex ml-4" @click="saving = !saving">
                <DocumentTextIcon class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs cursor-pointer">Speichern</label>
            </button>
        </div>
        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">

            <!-- Save Filter Section -->
            <Disclosure v-slot="{ open }" default-open>
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                >
                    <span
                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Gespeicherte Filter</span>
                    <ChevronDownIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <div v-if="saving">
                        <div class="flex">
                            <input id="saveFilter" autocomplete="off" v-model="filterName" type="text"
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
                        v-for="filter in localPersonalFilters">
                        <label @click="applyFilter(filter)"
                               class="cursor-pointer text-white">{{ filter.name }}</label>
                        <XIcon @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                    </button>
                    <p v-if="localPersonalFilters.length === 0" class="text-secondary py-1">Noch keine Filter
                        gespeichert</p>
                </DisclosurePanel>
            </Disclosure>

            <!-- Room Filter Section -->
            <Disclosure v-slot="{ open }" v-if="showRoomFilters">
                <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
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
                        <!-- TODO: STILL NEEDS TO BE IMPLEMENTED IN THE BACKEND
                        <SwitchGroup>
                            <div class="flex items-center">
                                <Switch v-model="filterArray.roomFilters.showAdjoiningRooms"
                                        :class="filterArray.roomFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="filterArray.roomFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                </Switch>
                                <SwitchLabel class="ml-4 text-xs"
                                             :class="filterArray.roomFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                    Nebenräume anzeigen
                                </SwitchLabel>
                            </div>
                        </SwitchGroup>
                        <SwitchGroup class="mb-1">
                            <div class="flex items-center mt-2">
                                <Switch v-model="filterArray.roomFilters.allDayFree"
                                        :class="filterArray.roomFilters.allDayFree ? 'bg-white' : 'bg-darkGray'"
                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="filterArray.roomFilters.allDayFree ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                </Switch>
                                <SwitchLabel class="ml-4 text-xs"
                                             :class="filterArray.roomFilters.allDayFree ? 'text-white' : 'text-secondary'">
                                    ganztägig frei
                                </SwitchLabel>
                            </div>
                        </SwitchGroup>
                        -->
                        <div v-if="type !== 'project'">
                            <div class="flex w-full mb-2">
                                <input type="checkbox" v-model="filterArray.eventAttributes.adjoiningNotLoud.checked"
                                       @change="reloadFilterBackend"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[filterArray.eventAttributes.adjoiningNotLoud.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">Ohne laute
                                    Nebenveranstaltung</p>
                            </div>
                            <div class="flex w-full mb-2">
                                <input type="checkbox" v-model="filterArray.eventAttributes.adjoiningNoAudience.checked"
                                       @change="reloadFilterBackend"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[filterArray.eventAttributes.adjoiningNoAudience.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">Ohne Nebenveranstaltung
                                    mit Publikum</p>
                            </div>
                            <!-- temporarily not included

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
                            <hr class="border-gray-500 mt-2 mb-2">
                        </div>


                    </div>
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span
                                :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Raumkategorien</span>
                            <ChevronDownIcon
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="filterArray.roomCategories.length > 0"
                                 v-for="category in filterArray.roomCategories"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="category.checked"
                                       @change="addRoomCategoryToFilter(category)"
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
                            <div v-if="filterArray.areas.length > 0" v-for="area in filterArray.areas"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="area.checked"
                                       @change="addAreasToFilter(area)"
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
                            <div v-if="filterArray.roomAttributes.length > 0"
                                 v-for="attribute in filterArray.roomAttributes"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="attribute.checked"
                                       @change="addRoomAttributeToFilter(attribute)"
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
                            <div v-if="filterArray.rooms.length > 0" v-for="room in filterArray.rooms"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="room.checked"
                                       @change="addRoomsToFilter(room)"
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
                            <div v-for="eventType in filterArray.eventTypes" class="flex w-full mb-2">
                                <input type="checkbox" v-model="eventType.checked"
                                       @change="addEventTypesToFilter(eventType)"
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
                            <div v-for="eventAttribute in filterArray.eventAttributes" class="flex w-full mb-2">
                                <input type="checkbox" v-model="eventAttribute.checked"
                                       @change="reloadFilterBackend"
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
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Menu,
    MenuButton,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel,
} from "@headlessui/vue";

import {ChevronDownIcon, DocumentTextIcon,} from '@heroicons/vue/outline';
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {XIcon} from "@heroicons/vue/solid";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "IndividualCalendarFilterComponent",
    mixins: [Permissions],
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
    props: [
        'useIcon',
        'filterOptions',
        'personalFilters',
        'atAGlance',
        'type',
        'user_filters',
        'externUpdated',
    ],
    mounted() {
        this.initFilter()
    },
    updated() {
        this.initFilter()
    },
    data() {
        return {
            localPersonalFilters: this.personalFilters,
            filterName: '',
            eventsSince: null,
            eventsUntil: null,
            deletingEvent: false,
            eventComponentIsVisible: false,
            createEventComponentIsVisible: false,
            roomCategoryIds: [],
            roomAttributeIds: [],
            eventTypeIds: [],
            areaIds: [],
            roomIds: [],
            roomCategories: [],
            filterArray: {
                rooms: [],
                areas: [],
                eventTypes: [],
                roomAttributes: [],
                roomCategories: [],
                showFreeRooms: false,
                roomFilters: {
                    showAdjoiningRooms: false,
                    allDayFree: false
                },
                eventAttributes: {},
            },
            saving: false,
        }
    },
    methods: {
        reloadFilterBackend() {
            this.reloadChanges()
        },
        addRoomCategoryToFilter(category) {
            if (this.roomCategoryIds.includes(Number(category.id))) {
                this.roomCategoryIds.splice(this.roomCategoryIds.indexOf(category.id), 1)
            } else {
                this.roomCategoryIds.push(category.id)
            }

            this.reloadChanges()
        },
        addRoomAttributeToFilter(attribute) {
            if (this.roomAttributeIds.includes(attribute.id)) {
                this.roomAttributeIds.splice(this.roomAttributeIds.indexOf(attribute.id), 1)
            } else {
                this.roomAttributeIds.push(attribute.id)
            }
            this.reloadChanges()
        },
        addEventTypesToFilter(eventType) {
            if (this.eventTypeIds.includes(eventType.id)) {
                this.eventTypeIds.splice(this.eventTypeIds.indexOf(eventType.id), 1)
            } else {
                this.eventTypeIds.push(eventType.id)
            }
            this.reloadChanges()
        },
        addAreasToFilter(area) {
            if (this.areaIds.includes(area.id)) {
                this.areaIds.splice(this.areaIds.indexOf(area.id), 1)
            } else {
                this.areaIds.push(area.id)
            }
            this.reloadChanges()
        },

        addRoomsToFilter(room) {
            if (this.roomIds.includes(room.id)) {
                this.roomIds.splice(this.roomIds.indexOf(room.id), 1)
            } else {
                this.roomIds.push(room.id)
            }
            this.reloadChanges()
        },
        setCheckedFalse(array) {
            array.forEach(item => item.checked = false)
        },
        resetCalendarFilter() {
            this.$inertia.delete(route('reset.user.calendar.filter', this.$page.props.user.id), {
                onSuccess: () => {
                    this.filterArray.rooms.forEach(room => room.checked = false)
                    this.filterArray.areas.forEach(area => area.checked = false)
                    this.filterArray.roomAttributes.forEach(attribute => attribute.checked = false)
                    this.filterArray.roomCategories.forEach(category => category.checked = false)
                    this.filterArray.eventTypes.forEach(eventType => eventType.checked = false)
                    this.filterArray.eventAttributes.isLoud.checked = false
                    this.filterArray.eventAttributes.isNotLoud.checked = false
                    this.filterArray.eventAttributes.hasAudience.checked = false
                    this.filterArray.eventAttributes.hasNoAudience.checked = false
                    this.filterArray.eventAttributes.adjoiningNotLoud.checked = false
                    this.filterArray.eventAttributes.adjoiningNoAudience.checked = false

                }
            })
        },
        async saveFilter() {
            const filterIds = this.getFilterFields();
            await axios.post('/filters', {name: this.filterName, calendarFilters: filterIds}).then(() => {
                this.filterName = ""
            })
            await axios.get('/filters')
                .then(response => {
                    this.localPersonalFilters = response.data;
                })
        },
        changeChecked(elementsToChange, checkedElements) {
            elementsToChange.forEach(item => {
                checkedElements.forEach(checkedItem => {
                    if (item.id === checkedItem.id) {
                        item.checked = true
                    }
                })
            })
            return elementsToChange
        },
        applyFilter(filter) {
            console.log(filter);
            this.filterArray.rooms = this.changeChecked(this.filterArray.rooms, filter.rooms)
            this.filterArray.areas = this.changeChecked(this.filterArray.areas, filter.areas)
            this.filterArray.roomAttributes = this.changeChecked(this.filterArray.roomAttributes, filter.roomAttributes)
            this.filterArray.roomCategories = this.changeChecked(this.filterArray.roomCategories, filter.roomCategories)
            this.filterArray.eventTypes = this.changeChecked(this.filterArray.eventTypes, filter.eventTypes)
            this.filterArray.eventAttributes.isLoud.checked = filter.isLoud
            this.filterArray.eventAttributes.isNotLoud.checked = filter.isNotLoud
            this.filterArray.eventAttributes.hasAudience.checked = filter.hasAudience
            this.filterArray.eventAttributes.hasNoAudience.checked = filter.hasNoAudience
            this.filterArray.eventAttributes.adjoiningNotLoud.checked = filter.adjoiningNotLoud
            this.filterArray.eventAttributes.adjoiningNoAudience.checked = filter.adjoiningNoAudience
            this.filterArray.roomFilters.showAdjoiningRooms = filter.showAdjoiningRooms
            this.filterArray.roomFilters.allDayFree = filter.allDayFree
            this.reloadChanges();
        },
        async deleteFilter(id) {
            await axios.delete(`/filters/${id}`)
            await axios.get('/filters')
                .then(response => {
                    this.localPersonalFilters = response.data
                })
        },
        returnNullIfFalse(variable) {
            if (!variable) {
                return false
            }
            return variable
        },
        arrayToIds(array) {
            const filteredArray = array.filter(item => item.checked === true)

            if (filteredArray.length === 0) {
                return null
            }

            return filteredArray.map(elem => elem.id)
        },
        getFilterFields() {
            return {
                isLoud: this.returnNullIfFalse(this.filterArray.eventAttributes.isLoud.checked),
                isNotLoud: this.returnNullIfFalse(this.filterArray.eventAttributes.isNotLoud.checked),
                adjoiningNoAudience: this.returnNullIfFalse(this.filterArray.eventAttributes.adjoiningNoAudience.checked),
                adjoiningNotLoud: this.returnNullIfFalse(this.filterArray.eventAttributes.adjoiningNotLoud.checked),
                hasAudience: this.returnNullIfFalse(this.filterArray.eventAttributes.hasAudience.checked),
                hasNoAudience: this.returnNullIfFalse(this.filterArray.eventAttributes.hasNoAudience.checked),
                showAdjoiningRooms: this.returnNullIfFalse(this.filterArray.roomFilters.showAdjoiningRooms),
                allDayFree: this.returnNullIfFalse(this.filterArray.roomFilters.allDayFree),
                roomIds: this.arrayToIds(this.filterArray.rooms),
                areaIds: this.arrayToIds(this.filterArray.areas),
                eventTypeIds: this.arrayToIds(this.filterArray.eventTypes),
                roomAttributeIds: this.arrayToIds(this.filterArray.roomAttributes),
                roomCategoryIds: this.arrayToIds(this.filterArray.roomCategories)
            }
        },
        initFilter(){
            this.filterArray.rooms = this.filterOptions.rooms
            this.filterArray.areas = this.filterOptions.areas
            this.filterArray.roomCategories = this.filterOptions.roomCategories
            this.filterArray.roomAttributes = this.filterOptions.roomAttributes
            this.filterArray.eventTypes = this.filterOptions.eventTypes
            this.setCheckedFalse(this.filterArray.rooms)
            this.setCheckedFalse(this.filterArray.areas)
            this.setCheckedFalse(this.filterArray.roomCategories)
            this.setCheckedFalse(this.filterArray.roomAttributes)
            this.setCheckedFalse(this.filterArray.eventTypes)


            this.filterArray.roomCategories.forEach((category) => {
                if(this.user_filters.room_categories?.includes(category.id)){
                    category.checked = true;
                    category.value = 'room_categories'
                } else {
                    category.checked = false
                    category.value = 'room_categories'
                }
            })

            this.filterArray.roomAttributes.forEach((attribute) => {
                if(this.user_filters.room_attributes?.includes(attribute.id)){
                    attribute.checked = true
                    attribute.value = 'room_attributes'
                } else {
                    attribute.checked = false
                    attribute.value = 'room_attributes'
                }
            })

            this.filterArray.eventTypes.forEach((eventType) => {
                if(this.user_filters.event_types?.includes(eventType.id)){
                    eventType.checked = true
                    eventType.value = 'event_types'
                } else {
                    eventType.checked = false
                    eventType.value = 'event_types'
                }
            })

            this.filterArray.areas.forEach((area) => {
                if(this.user_filters.areas?.includes(area.id)){
                    area.checked = true
                    area.value = 'areas'
                } else {
                    area.checked = false
                    area.value = 'areas'
                }
            })

            this.filterArray.rooms.forEach((room) => {
                if(this.user_filters.rooms?.includes(room.id)){
                    room.checked = true
                    room.value = 'rooms'
                } else {
                    room.checked = false
                    room.value = 'rooms'
                }
            })

            this.filterArray.eventAttributes = {
                isLoud: {
                    checked: this.user_filters.is_loud,
                    value: 'is_loud',
                    name: 'Laut'
                },
                isNotLoud: {
                    checked: this.user_filters.is_not_loud,
                    value: 'is_not_loud',
                    name: 'nicht laut',
                },
                adjoiningNoAudience: {
                    checked: this.user_filters.adjoining_no_audience,
                    value: 'adjoining_no_audience',
                    name: 'ohne Nebenveranstaltung mit Publikum',
                },
                adjoiningNotLoud: {
                    checked: this.user_filters.adjoining_not_loud,
                    value: 'adjoining_not_loud',
                    name: 'ohne laute Nebenveranstaltung',
                },
                hasAudience: {
                    checked: this.user_filters.has_audience,
                    value: 'has_audience',
                    name: 'Mit Publikum',
                },
                hasNoAudience: {
                    checked: this.user_filters.has_no_audience,
                    value: 'has_no_audience',
                    name: 'ohne Publikum',
                },
            }
        },
        getRoute(pathName) {
            switch (pathName) {
                case 'dashboard':
                    return route('dashboard')
                case 'events':
                    return route('events')
                case 'projects':
                    return route('projects.show.calendar', {project: window.location.pathname.split('/')[2]})
            }
        },
        reloadChanges() {
            Inertia.patch(route('update.user.calendar.filter', this.$page.props.user.id), {
                is_loud: this.returnNullIfFalse(this.filterArray.eventAttributes.isLoud.checked),
                is_not_loud: this.returnNullIfFalse(this.filterArray.eventAttributes.isNotLoud.checked),
                adjoining_no_audience: this.returnNullIfFalse(this.filterArray.eventAttributes.adjoiningNoAudience.checked),
                adjoining_not_loud: this.returnNullIfFalse(this.filterArray.eventAttributes.adjoiningNotLoud.checked),
                has_audience: this.returnNullIfFalse(this.filterArray.eventAttributes.hasAudience.checked),
                has_no_audience: this.returnNullIfFalse(this.filterArray.eventAttributes.hasNoAudience.checked),
                show_adjoining_rooms: this.returnNullIfFalse(this.filterArray.roomFilters.showAdjoiningRooms),
                all_day_free: this.returnNullIfFalse(this.filterArray.roomFilters.allDayFree),
                rooms: this.arrayToIds(this.filterArray.rooms),
                areas: this.arrayToIds(this.filterArray.areas),
                event_types: this.arrayToIds(this.filterArray.eventTypes),
                room_attributes: this.arrayToIds(this.filterArray.roomAttributes),
                room_categories: this.arrayToIds(this.filterArray.roomCategories)
            }, {
                preserveScroll: true,
                preserveState: true,

            })
        }
    },
    computed: {
        showRoomFilters: function () {
            const pathName = window.location.pathname.split('/')[1]

            return pathName !== "rooms";
        },
        activeFilters: function () {
            let activeFiltersArray = []

            this.filterArray.rooms.forEach(room => {
                if (room.checked) activeFiltersArray.push(room)
            })

            this.filterArray.areas.forEach(area => {
                if (area.checked) activeFiltersArray.push(area)
            })

            this.filterArray.eventTypes.forEach(eventType => {
                if (eventType.checked) activeFiltersArray.push(eventType)
            })

            this.filterArray.roomCategories.forEach(category => {
                if (category.checked) activeFiltersArray.push(category)
            })

            this.filterArray.roomAttributes.forEach(attribute => {
                if (attribute.checked) activeFiltersArray.push(attribute)
            })

            if (this.filterArray.eventAttributes.isLoud.checked)
                activeFiltersArray.push({name: "Laute Termine", value: 'isLoud', user_filter_key: 'is_loud'})

            if (this.filterArray.eventAttributes.isNotLoud.checked)
                activeFiltersArray.push({name: "Ohne laute Termine", value: 'isNotLoud', user_filter_key: 'is_not_loud'})

            if (this.filterArray.eventAttributes.adjoiningNoAudience.checked)
                activeFiltersArray.push({name: "Ohne Nebenveranstaltung mit Publikum", value: 'adjoiningNoAudience', user_filter_key: 'adjoining_no_audience' })

            if (this.filterArray.eventAttributes.adjoiningNotLoud.checked)
                activeFiltersArray.push({name: "Ohne laute Nebenveranstaltung", value: 'adjoiningNotLoud', user_filter_key: 'adjoining_not_loud'})

            if (this.filterArray.eventAttributes.hasAudience.checked)
                activeFiltersArray.push({name: "Mit Publikum", value: 'hasAudience', user_filter_key: 'has_audience'})

            if (this.filterArray.eventAttributes.hasNoAudience.checked)
                activeFiltersArray.push({name: "Ohne Publikum", value: 'hasNoAudience', user_filter_key: 'has_no_audience'})

            if (this.filterArray.roomFilters.showAdjoiningRooms)
                activeFiltersArray.push({name: "Nebenräume anzeigen", value: 'showAdjoiningRooms', user_filter_key: 'show_adjoining_rooms'})

            return activeFiltersArray
        }
    },
    watch: {
        atAGlance: {
            handler() {
                this.reloadChanges()
            }
        },

    }
}
</script>

<style scoped>

</style>
