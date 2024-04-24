<template>
    <BaseFilter onlyIcon="true">
        <div class="inline-flex border-none justify-end w-full">
            <button class="flex" @click="resetCalendarFilter">
                <IconX stroke-width="1.5" class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs cursor-pointer">{{ $t('Reset')}}</label>
            </button>
            <button class="flex ml-4" @click="saving = !saving">
                <IconFileText stroke-width="1.5" class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs cursor-pointer">{{ $t('Save')}}</label>
            </button>
        </div>

        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">

            <!-- Save Filter Section -->
            <Disclosure v-slot="{ open }" default-open>
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                >
                    <span
                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Saved filters')}}</span>
                    <IconChevronDown stroke-width="1.5"
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <div v-if="saving">
                        <div class="flex">
                            <input id="saveFilter" autocomplete="off" v-model="filterName" type="text"
                                   class="shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                   :placeholder="$t('Name of the filter')"/>
                            <button
                                class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1 ml-2">
                                <label @click="saveFilter"
                                       class="cursor-pointer text-white text-xs">{{ $t('Save')}}</label>
                            </button>
                        </div>
                        <hr class="border-gray-500 mt-4 mb-4">
                    </div>
                    <button
                        class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1"
                        v-for="filter in localPersonalFilters">
                        <label @click="applyFilter(filter)"
                               class="cursor-pointer text-white">{{ filter.name }}</label>
                        <IconX stroke-width="1.5" @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                    </button>
                    <p v-if="localPersonalFilters.length === 0" class="text-secondary py-1">{{ $t('No filters saved yet')}}</p>
                </DisclosurePanel>
            </Disclosure>

            <!-- Room Filter Section -->
            <Disclosure v-slot="{ open }" v-if="showRoomFilters">
                <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Rooms')}}</span>
                    <IconChevronDown stroke-width="1.5"
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-if="filterArray.rooms.length > 0" v-for="room in filterArray.rooms"
                                 class="flex w-full mb-2">
                                <input type="checkbox" v-model="room.checked" @change="addRoomsToFilter(room)"
                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                <p :class="[room.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                    {{ room.label }}</p>
                            </div>
                            <div v-else class="text-secondary">{{ $t('No rooms created yet')}}</div>
                        </DisclosurePanel>
                    </Disclosure>

            <hr class="border-secondary rounded-full border-2 mt-2 mb-2">

            <!-- Event Filter Section -->
            <Disclosure v-slot="{ open }">
                <DisclosureButton
                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                >
                                <span
                                    :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Events')}}</span>
                    <IconChevronDown stroke-width="1.5"
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
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Event type')}}</span>
                            <IconChevronDown stroke-width="1.5"
                                :class="open ? 'rotate-180 transform' : ''"
                                class="h-4 w-4 mt-0.5 text-white"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                            <div v-for="eventType in filterArray.eventTypes" class="flex w-full mb-2">
                                <input type="checkbox" v-model="eventType.checked" @change="addEventTypesToFilter(eventType)"
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
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "ShiftPlanFilter",
    mixins: [Permissions, IconLib],
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
        'user_filters'
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
                eventTypes: [],
            },
            saving: false,
        }
    },
    methods: {
        initFilter(){
            this.filterArray.rooms = this.filterOptions.rooms
            this.filterArray.eventTypes = this.filterOptions.eventTypes
            this.setCheckedFalse(this.filterArray.rooms)
            this.setCheckedFalse(this.filterArray.eventTypes)

            this.filterArray.eventTypes.forEach((eventType) => {
                if(this.user_filters.event_types?.includes(eventType.id)){
                    eventType.checked = true
                    eventType.value = 'event_types'
                } else {
                    eventType.checked = false
                    eventType.value = 'event_types'
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
        },
        setCheckedFalse(array) {
            array.forEach(item => item.checked = false)
        },
        addEventTypesToFilter(eventType) {
            if (this.eventTypeIds.includes(eventType.id)) {
                this.eventTypeIds.splice(this.eventTypeIds.indexOf(eventType.id), 1)
            } else {
                this.eventTypeIds.push(eventType.id)
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
        resetCalendarFilter() {
            this.$inertia.delete(route('reset.user.shift.calendar.filter', this.$page.props.user.id), {
                onSuccess: () => {
                    this.filterArray.rooms.forEach(room => room.checked = false)
                    this.filterArray.eventTypes.forEach(eventType => eventType.checked = false)
                }
            })
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
            this.filterArray.eventTypes = this.changeChecked(this.filterArray.eventTypes, filter.eventTypes)
            this.reloadChanges()
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
                roomIds: this.arrayToIds(this.filterArray.rooms),
                eventTypeIds: this.arrayToIds(this.filterArray.eventTypes),
            }
        },
        reloadChanges() {
            Inertia.patch(route('update.user.shift.calendar.filter', this.$page.props.user.id), {
                rooms: this.arrayToIds(this.filterArray.rooms),
                event_types: this.arrayToIds(this.filterArray.eventTypes),
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

            this.filterArray.eventTypes.forEach(eventType => {
                if(eventType.checked) activeFiltersArray.push(eventType)
            })

            return activeFiltersArray
        }
    },
    watch: {
        filterArray: {
            handler() {
                //this.reloadChanges()
                //this.$emit('filtersChanged', this.activeFilters)
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
