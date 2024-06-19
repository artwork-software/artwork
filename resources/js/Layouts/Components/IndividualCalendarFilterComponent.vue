<template>
    <BaseFilter onlyIcon="true">
        <div class="inline-flex border-none justify-end w-full">
            <button class="flex items-center" @click="resetCalendarFilter">
                <IconX stroke-width="1.5" class="w-3 mr-1"/>
                <label class="text-xs cursor-pointer">{{ $t('Reset') }}</label>
            </button>
            <button class="flex ml-4 items-center" @click="saving = !saving">
                <IconFileText stroke-width="1.5" class="w-3 mr-1"/>
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
                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Saved filters')}}</span>
                    <IconChevronDown stroke-width="1.5"
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-4 w-4 mt-0.5 text-white"
                    />
                </DisclosureButton>
                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                    <div v-if="saving">
                        <div class="flex">
                            <input id="saveFilter" autocomplete="off" v-model="filterName" type="text"
                                   class="w-48 shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                   placeholder="Name des Filters"/>
                            <button
                                class="rounded-full bg-artwork-buttons-create cursor-pointer px-5 py-2 align-middle flex mb-1 ml-2">
                                <label @click="saveFilter"
                                       class="cursor-pointer text-white text-xs">{{$t('Save')}}</label>
                            </button>
                        </div>
                        <div v-if="this.hasInvalidFilterName" class="pt-2 errorText">
                            {{ $t('Enter filter name') }}
                        </div>
                        <hr class="border-gray-500 mt-4 mb-4">
                    </div>
                    <button
                        class="rounded-full bg-artwork-buttons-create cursor-pointer px-5 py-2 align-middle flex mb-1"
                        v-for="filter in localPersonalFilters">
                        <label @click="applyFilter(filter)"
                               class="cursor-pointer text-white">{{ filter.name }}</label>
                        <IconX stroke-width="1.5" @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                    </button>
                    <p v-if="localPersonalFilters.length === 0" class="text-secondary py-1">{{
                            $t('No filters saved yet')
                        }}</p>
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
                    <div>
                        <div class="flex w-full mb-2">
                            <input type="checkbox" v-model="filterArray.adjoining.adjoiningNotLoud.checked"
                                   @change="reloadFilterBackend"
                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                            <p :class="[filterArray.adjoining.adjoiningNotLoud.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">{{$t('without a loud side event')}}</p>
                        </div>
                        <div class="flex w-full mb-2">
                            <input type="checkbox" v-model="filterArray.adjoining.adjoiningNoAudience.checked"
                                   @change="reloadFilterBackend"
                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                            <p :class="[filterArray.adjoining.adjoiningNoAudience.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">{{$t('without side event with audience')}}</p>
                        </div>
                        <hr class="border-gray-500 mt-2 mb-2">
                    </div>
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span
                                :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Room categories')}}</span>
                            <IconChevronDown stroke-width="1.5"
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
                            <div v-else class="text-secondary">{{$t('No categories created yet')}}</div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Areas ') }}</span>
                            <IconChevronDown stroke-width="1.5"
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
                            <div v-else class="text-secondary">{{  $t('No areas created') }}</div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Room properties')}}</span>
                            <IconChevronDown stroke-width="1.5"
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
                            <div v-else class="text-secondary">{{$t('No room properties created yet')}}
                            </div>
                        </DisclosurePanel>
                    </Disclosure>
                    <hr class="border-gray-500 mt-2 mb-2">
                    <Disclosure v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                        >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Rooms')}}</span>
                            <IconChevronDown stroke-width="1.5"
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
                            <div v-else class="text-secondary">{{$t('No rooms created yet')}}</div>
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
                                    :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{$t('Events')}}</span>
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
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Event type')}}</span>
                            <IconChevronDown stroke-width="1.5"
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
                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('Event properties')}}</span>
                            <IconChevronDown stroke-width="1.5"
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
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "IndividualCalendarFilterComponent",
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
            hasInvalidFilterName: false
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
                    this.filterArray.adjoining.adjoiningNotLoud.checked = false
                    this.filterArray.adjoining.adjoiningNoAudience.checked = false

                }
            })
        },
        async saveFilter() {
            if (this.filterName === null || this.filterName === '') {
                this.hasInvalidFilterName = true;
                return;
            }

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
            this.filterArray.rooms = this.changeChecked(this.filterArray.rooms, filter.rooms)
            this.filterArray.areas = this.changeChecked(this.filterArray.areas, filter.areas)
            this.filterArray.roomAttributes = this.changeChecked(this.filterArray.roomAttributes, filter.roomAttributes)
            this.filterArray.roomCategories = this.changeChecked(this.filterArray.roomCategories, filter.roomCategories)
            this.filterArray.eventTypes = this.changeChecked(this.filterArray.eventTypes, filter.eventTypes)
            this.filterArray.eventAttributes.isLoud.checked = filter.isLoud
            this.filterArray.eventAttributes.isNotLoud.checked = filter.isNotLoud
            this.filterArray.eventAttributes.hasAudience.checked = filter.hasAudience
            this.filterArray.eventAttributes.hasNoAudience.checked = filter.hasNoAudience
            this.filterArray.adjoining.adjoiningNotLoud.checked = filter.adjoiningNotLoud
            this.filterArray.adjoining.adjoiningNoAudience.checked = filter.adjoiningNoAudience
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
                adjoiningNoAudience: this.returnNullIfFalse(this.filterArray.adjoining.adjoiningNoAudience.checked),
                adjoiningNotLoud: this.returnNullIfFalse(this.filterArray.adjoining.adjoiningNotLoud.checked),
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

            this.filterArray.adjoining = {
                adjoiningNoAudience: {
                    checked: this.user_filters.adjoining_no_audience,
                    value: 'adjoining_no_audience',
                    name: this.$t('without side event with audience'),
                },
                adjoiningNotLoud: {
                    checked: this.user_filters.adjoining_not_loud,
                    value: 'adjoining_not_loud',
                    name: this.$t('without a loud side event'),
                },
            };

            this.filterArray.eventAttributes = {
                isLoud: {
                    checked: this.user_filters.is_loud,
                    value: 'is_loud',
                    name: this.$t('loud')
                },
                isNotLoud: {
                    checked: this.user_filters.is_not_loud,
                    value: 'is_not_loud',
                    name: this.$t('not loud'),
                },
                hasAudience: {
                    checked: this.user_filters.has_audience,
                    value: 'has_audience',
                    name: this.$t('With audience'),
                },
                hasNoAudience: {
                    checked: this.user_filters.has_no_audience,
                    value: 'has_no_audience',
                    name: this.$t('without audience'),
                },
            }
        },
        reloadChanges() {
            router.patch(route('update.user.calendar.filter', this.$page.props.user.id), {
                is_loud: this.returnNullIfFalse(this.filterArray.eventAttributes.isLoud.checked),
                is_not_loud: this.returnNullIfFalse(this.filterArray.eventAttributes.isNotLoud.checked),
                adjoining_no_audience: this.returnNullIfFalse(this.filterArray.adjoining.adjoiningNoAudience.checked),
                adjoining_not_loud: this.returnNullIfFalse(this.filterArray.adjoining.adjoiningNotLoud.checked),
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
