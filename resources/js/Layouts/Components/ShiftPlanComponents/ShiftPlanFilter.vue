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
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[eventType.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ eventType.name }}</p>
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
    name: "ShiftPlanFilter",
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
    ],
    mounted() {
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
            },
            saving: false,
        }
    },
    methods: {
        setCheckedFalse(array) {
            array.forEach(item => item.checked = false)
        },
        resetCalendarFilter() {
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
        },
        async saveFilter() {
            const filterIds = this.getFilterFields();
            await axios.post('/shifts/filters', {name: this.filterName, calendarFilters: filterIds}).then(() => {
                this.filterName = ""
            })
            await axios.get('/shifts/filters')
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
        },
        async deleteFilter(id) {
            await axios.delete(`/shifts/filters/${id}`)
            await axios.get('/shifts/filters')
                .then(response => {
                    this.localPersonalFilters = response.data
                })
        },
        returnNullIfFalse(variable) {
            if (!variable) {
                return null
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
        getRoute(pathName) {
            switch (pathName) {
                case 'dashboard':
                    return route('dashboard')
                case 'events':
                    return route('events')
                case 'projects':
                    return route('projects.show.shift', { project: window.location.pathname.split('/')[2]})
            }
        },
        reloadChanges() {
            const pageRoute = this.getRoute(window.location.pathname.split('/')[1])
            console.log("reloading in shift filter")
            Inertia.reload( {
                data: {
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
                    roomCategoryIds: this.arrayToIds(this.filterArray.roomCategories),
                    atAGlance: this.atAGlance
                },
                preserveState: true
            })
        }
    },
    computed: {
        showRoomFilters: function() {
            const pathName = window.location.pathname.split('/')[1]

            return pathName !== "rooms";
        },
        activeFilters: function() {
            let activeFiltersArray = []

            this.filterArray.rooms.forEach(room => {
                if(room.checked) activeFiltersArray.push(room)
            })

            this.filterArray.areas.forEach(area => {
                if(area.checked) activeFiltersArray.push(area)
            })

            this.filterArray.eventTypes.forEach(eventType => {
                if(eventType.checked) activeFiltersArray.push(eventType)
            })

            this.filterArray.roomCategories.forEach(category => {
                if(category.checked) activeFiltersArray.push(category)
            })

            this.filterArray.roomAttributes.forEach(attribute => {
                if(attribute.checked) activeFiltersArray.push(attribute)
            })

            if(this.filterArray.eventAttributes.isLoud.checked)
                activeFiltersArray.push({name: "Laute Termine"})

            if(this.filterArray.eventAttributes.isNotLoud.checked)
                activeFiltersArray.push({name: "Ohne laute Termine"})

            if(this.filterArray.eventAttributes.adjoiningNoAudience.checked)
                activeFiltersArray.push({name: "Ohne Nebenveranstaltung mit Publikum"})

            if(this.filterArray.eventAttributes.adjoiningNotLoud.checked)
                activeFiltersArray.push({name: "Ohne laute Nebenveranstaltung"})

            if(this.filterArray.eventAttributes.hasAudience.checked)
                activeFiltersArray.push({name: "Mit Publikum"})

            if(this.filterArray.eventAttributes.hasNoAudience.checked)
                activeFiltersArray.push({name: "Ohne Publikum"})

            if(this.filterArray.roomFilters.showAdjoiningRooms)
                activeFiltersArray.push({name: "Nebenräume anzeigen"})

            return activeFiltersArray
        }
    },
    watch: {
        filterArray: {
            handler() {
                this.reloadChanges()
                this.$emit('filtersChanged', this.activeFilters)
            },
            deep: true
        },
        atAGlance: {
            handler() {
                this.reloadChanges()
            }
        }
    }
}
</script>

<style scoped>

</style>
