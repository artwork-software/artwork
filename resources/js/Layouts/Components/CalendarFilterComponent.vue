<template>
    <!-- Calendar Filter -->
    <div>
        <Menu as="div" class="relative inline-block text-left w-80">
            <div>
                <MenuButton
                    class="mt-1 border border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >
                    <span class="float-left">Filter</span>
                    <ChevronDownIcon
                        class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                        aria-hidden="true"
                    />
                </MenuButton>
            </div>
            <transition
                enter-active-class="transition duration-50 ease-out"
                enter-from-class="transform scale-100 opacity-100"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <MenuItems
                    class="absolute right-0 mt-2 w-80 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                    <div class="inline-flex border-none w-1/5">
                        <button>
                            <FilterIcon class="w-3 mr-1 mt-0.5"/>
                        </button>
                    </div>
                    <div class="inline-flex border-none justify-end w-4/5">
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
                        <Disclosure v-slot="{ open }" v-if="saving" default-open>
                            <DisclosureButton
                                class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                            >
                                <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Gespeicherte Filter</span>
                                <ChevronDownIcon
                                    :class="open ? 'rotate-180 transform' : ''"
                                    class="h-4 w-4 mt-0.5 text-white"
                                />
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
                                <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                                <ChevronDownIcon
                                    :class="open ? 'rotate-180 transform' : ''"
                                    class="h-4 w-4 mt-0.5 text-white"
                                />
                            </DisclosureButton>
                            <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                <div v-if="currentView !== 'month'">
                                    <SwitchGroup>
                                        <div class="flex items-center">
                                            <Switch v-model="calendarFilters.showAdjoiningRooms"
                                                    :class="calendarFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                                    class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="calendarFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                            </Switch>
                                            <SwitchLabel class="ml-4 text-xs"
                                                         :class="calendarFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                                Nebenräume anzeigen
                                            </SwitchLabel>
                                        </div>
                                    </SwitchGroup>
                                    <SwitchGroup>
                                        <div class="flex items-center mt-2">
                                            <Switch v-model="calendarFilters.allDayFree"
                                                    :class="calendarFilters.allDayFree ? 'bg-white' : 'bg-darkGray'"
                                                    class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="calendarFilters.allDayFree ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                            </Switch>
                                            <SwitchLabel class="ml-4 text-xs"
                                                         :class="calendarFilters.allDayFree ? 'text-white' : 'text-secondary'">
                                                ganztägig frei
                                            </SwitchLabel>
                                        </div>
                                    </SwitchGroup>

                                    <!-- Wieder einklammern, falls genauere Unterteilung dazu kommt
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
                                        <div v-if="room_categories.length > 0" v-for="category in room_categories"
                                             class="flex w-full mb-2">
                                            <input type="checkbox" v-model="category.checked"
                                                   @change="changeFilterElements(calendarFilters.roomCategories, category)"
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
                                                   @change="changeFilterElements(calendarFilters.areas, area)"
                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[area.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                {{ area.label }}</p>
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
                                        <div v-if="room_attributes.length > 0" v-for="attribute in room_attributes"
                                             class="flex w-full mb-2">
                                            <input type="checkbox" v-model="attribute.checked"
                                                   @change="changeFilterElements(calendarFilters.roomAttributes, attribute)"
                                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                            <p :class="[attribute.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
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
                                                   @change="changeFilterElements(calendarFilters.rooms, room)"
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
                                        <div v-for="eventType in eventTypes" class="flex w-full mb-2">
                                            <input type="checkbox" v-model="eventType.checked"
                                                   @change="changeFilterElements(calendarFilters.eventTypes, eventType)"
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
                                                   @change="changeFilterBoolean(eventAttribute.value, eventAttribute)"
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
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

<script>
import {
    ChevronDownIcon,
    XIcon,
    DocumentTextIcon,
    FilterIcon
} from '@heroicons/vue/outline';
import {
    Menu,
    MenuButton,
    MenuItem, MenuItems,
    Switch,
    Disclosure,
    DisclosureButton,
    DisclosurePanel, SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import {Link} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";

export default {
    name: "CalendarFilterComponent",
    components: {
        AddButton,
        FilterIcon,
        SwitchLabel,
        SwitchGroup,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        XIcon,
        DocumentTextIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        CheckIcon,
        Switch,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Link
    },
    props: {
        calendarFilters: Object,
        room_attributes: Array,
        room_categories: Array,
        rooms: Array,
        areas: Array,
        eventTypes: Array,
        eventAttributes: Object,
        currentView: String,
        eventsSince: Date,
        eventsUntil: Date
    },
    data() {
        return {
            eventAttributes: [
                {
                    name: 'laut',
                    value: 'isLoud',
                    checked: false
                },
                {
                    name: 'nicht laut',
                    value: 'isNotLoud',
                    checked: false
                },
                {
                    name: 'ohne laute Nebenveranstaltung',
                    value: 'adjoiningNotLoud',
                    checked: false
                },
                {
                    name: 'mit Publikum',
                    value: 'hasAudience',
                    checked: false
                },
                {
                    name: 'ohne Publikum',
                    value: 'hasNoAudience',
                    checked: false
                },
                {
                    name: 'ohne Nebenveranstaltung mit Publikum',
                    value: 'adjoiningNoAudience',
                    checked: false
                }
            ],
            saving: false,
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
        }
    },
    methods: {
        changeFilterBoolean(filter, variable) {
            this.calendarFilters[`${filter}`] = variable.checked
            this.$parent.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
                calendarFilters: this.calendarFilters
            });
        },
        changeFilterElements(filterArray, element) {
            if (element.checked) {
                filterArray.push(element)
            } else {
                filterArray = filterArray.filter(elem => element.id !== elem.id)
            }
            this.$parent.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
                calendarFilters: this.calendarFilters
            });
        },
        resetCalendarFilter() {
            this.calendarFilters.rooms = []
            this.calendarFilters.areas = []
            this.calendarFilters.eventTypes = []
            this.calendarFilters.roomAttributes = []
            this.calendarFilters.roomCategories = []
            this.calendarFilters.isLoud = null
            this.calendarFilters.isNotLoud = null
            this.calendarFilters.hasAudience = null
            this.calendarFilters.hasNoAudience = null
            this.calendarFilters.adjoiningNoAudience = null
            this.calendarFilters.adjoiningNotLoud = null
            this.calendarFilters.allDayFree = null
            this.calendarFilters.showAdjoiningRooms = null

            this.$parent.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil
            });
        }
    }
}
</script>

<style scoped>

</style>
