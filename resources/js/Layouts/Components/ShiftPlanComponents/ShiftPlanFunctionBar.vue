<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="true"></date-picker-component>
            <div class="flex items-center mx-4 gap-x-1">
                <IconChevronLeft stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToPreviousDay"/>
                <Menu as="div" class="relative inline-block text-left">
                    <div class="flex items-center">
                        <MenuButton class="">
                            <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'month'"/>
                            <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'week'"/>
                            <IconCalendar stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.user.goto_mode === 'day'"/>
                        </MenuButton>
                    </div>

                    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                        <MenuItems class="absolute right-0 z-10 mt-2 w-fit origin-top-right rounded-md bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('day')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <IconCalendar stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    </div>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('week')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    </div>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('month')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-white"/>
                                    </div>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <IconChevronRight stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToNextDay"/>
            </div>
            <div class="items-center hidden">
                <div class="flex items-center">
                    <button  class="ml-2 text-black" @click="previousTimeRange">
                        <IconChevronLeft stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                    </button>
                    <button class="ml-2  text-black" @click="nextTimeRange">
                        <IconChevronRight stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                    </button>
                </div>
            </div>
            <div class="flex items-center" v-if="this.$can('can commit shifts') || this.hasAdminRole()">
                <SecondaryButton :text="$t('Lock all shifts')" @click="showConfirmCommitModal = true" />
            </div>
        </div>

        <div class="flex items-center">
            <div class="flex items-center gap-x-3">
                <IconHistory @click="openHistoryModal()" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"/>
                <IconArrowsDiagonal stroke-width="1.5" v-if="!isFullscreen" @click="enterFullscreenMode"
                      class="h-7 w-7 text-artwork-buttons-context cursor-pointer"/>
                <!-- PAUL HIER DAS NEUE FILTER COMPONENT EINBAUEN -->
                <ShiftPlanFilter
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    :crafts="crafts"
                />

            </div>
        </div>
    </div>
    <div class="mb-1 ml-4 flex items-center w-full">
        <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter" @removeFilter="removeFilter" />
    </div>


    <ConfirmDeleteModal
        v-if="showConfirmCommitModal"
        @closed="showConfirmCommitModal = false"
        @delete="commitAllShifts"
        :title="$t('Fixed Shiftplan')"
        :description="$t('Are you sure you want to set the shift plan?')"
        :button="$t('Fixing')"
    />
</template>

<script>

import Button from "@/Jetstream/Button.vue";
import {PlusCircleIcon, CalendarIcon} from '@heroicons/vue/outline'
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ShiftPlanFilter from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFilter.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import SecondaryButton from "@/Layouts/Components/General/Buttons/SecondaryButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue'
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
export default {
    name: "ShiftPlanFunctionBar",
    mixins: [Permissions, IconLib],
    components: {
        BaseButton,
        AddButtonSmall,
        SecondaryButton,
        ConfirmDeleteModal,
        BaseFilterTag,
        ShiftPlanFilter,
        Dropdown,
        Button,
        PlusCircleIcon,
        CalendarIcon,
        ChevronDownIcon,
        IndividualCalendarFilterComponent,
        ChevronLeftIcon,
        ChevronRightIcon,
        SwitchGroup,
        SwitchLabel,
        Switch,
        DatePickerComponent,
        Combobox,
        ComboboxButton,
        ComboboxInput,
        ComboboxLabel,
        ComboboxOption,
        ComboboxOptions,
        Menu, MenuButton, MenuItem, MenuItems
    },
    props: [
        'dateValue',
        'isFullscreen',
        'filterOptions',
        'allShiftsCommitted',
        'personalFilters',
        'rooms',
        'user_filters',
        'crafts'
    ],
    emits: ['enterFullscreenMode','previousTimeRange','nextTimeRange', 'openHistoryModal', 'selectGoToNextMode', 'selectGoToPreviousMode'],
    data() {
        return {
            //activeFilters: [],
            showConfirmCommitModal: false,
            scrollDays: 1,
        }
    },
    computed: {
        activeFilters(){
            let activeFiltersArray = []
            this.filterOptions.rooms.forEach((room) => {
                if(this.user_filters.rooms?.includes(room.id)){
                    activeFiltersArray.push(room)
                }
            })


            this.filterOptions.eventTypes.forEach((eventType) => {
                if(this.user_filters.event_types?.includes(eventType.id)){
                    activeFiltersArray.push(eventType)
                }
            })
            return activeFiltersArray
        }
    },
    methods: {
        changeUserSelectedGoTo(type){
            router.patch(route('user.calendar.go.to.stepper', {user: this.$page.props.user.id}), {
                goto_mode: type,
            }, {
                preserveScroll: true,
            });
        },
        removeFilter(filter){
            if(filter.value === 'rooms'){
                this.user_filters.rooms.splice(this.user_filters.rooms.indexOf(filter.id), 1);
                this.updateFilterValue('rooms', this.user_filters.rooms.length > 0 ? this.user_filters.rooms : null)
            }
            if(filter.value === 'event_types'){
                this.user_filters.event_types.splice(this.user_filters.event_types.indexOf(filter.id), 1);
                this.updateFilterValue('event_types', this.user_filters.event_types.length > 0 ? this.user_filters.event_types : null)
            }
        },
        updateFilterValue(key, value){
            router.patch(route('user.shift.calendar.filter.single.value.update', {user: this.$page.props.user.id}), {
                key: key,
                value: value
            }, {
                preserveScroll: true,
            });
        },
        scrollToNextDay(){
            this.$emit('selectGoToNextMode')
        },

        scrollToPreviousDay(){
            this.$emit('selectGoToPreviousMode')
        },

        enterFullscreenMode() {
            this.$emit('enterFullscreenMode')
        },
        previousTimeRange(){
            this.$emit('previousTimeRange')
        },
        nextTimeRange(){
            this.$emit('nextTimeRange')
        },
        filtersChanged(activeFilters) {
            this.activeFilters = activeFilters
        },
        openHistoryModal() {
            this.$emit('openHistoryModal')
        },
        commitAllShifts(){
            let filteredEvents = [];

            // Loop through each room in the shiftPlan array
            this.rooms.forEach(room => {
                // Loop through each day in the room object
                Object.values(room).forEach(day => {
                    // Check if day has an 'events' property, and it has a 'data' property
                    if(day.events && day.events.data) {
                        // Add the events to the allEvents array
                        filteredEvents = filteredEvents.concat(day.events.data);
                    }
                });
            });



            router.post('/shifts/commit', { events: filteredEvents }, {
                onSuccess: () => {
                    this.showConfirmCommitModal = false;
                },
                preserveScroll: true,
                preserveState: true,
            })

            /*axios.post('/shifts/commit', { events: filteredEvents })
                .then(() => {
                    console.log("All shifts committed");
                    this.showConfirmCommitModal = false;
                })
                .catch(error => {
                    console.log(error)
                })*/
        },
    },
}
</script>

<style scoped>
</style>
