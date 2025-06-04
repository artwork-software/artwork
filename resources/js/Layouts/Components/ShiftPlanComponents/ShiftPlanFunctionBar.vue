<template>
    <div class="w-[98%] flex justify-between items-center mt-2 mb-2 px-5">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="true"></date-picker-component>
            <div class="flex items-center mx-4 gap-x-1 select-none">
                <IconChevronLeftPipe stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="previousTimeRange"/>
                <IconChevronLeft stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToPreviousDay"/>
                <Menu as="div" class="relative inline-block text-left">
                    <div class="flex items-center">
                        <MenuButton class="">
                            <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.auth.user.goto_mode === 'month'"/>
                            <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.auth.user.goto_mode === 'week'"/>
                            <IconCalendar stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context" v-if="$page.props.auth.user.goto_mode === 'day'"/>
                        </MenuButton>
                    </div>

                    <transition enter-active-class="transition-enter-active"
                                enter-from-class="transition-enter-from"
                                enter-to-class="transition-enter-to"
                                leave-active-class="transition-leave-active"
                                leave-from-class="transition-leave-from"
                                leave-to-class="transition-leave-to">
                        <MenuItems class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-md bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('day')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <ToolTipComponent
                                            direction="right"
                                            :tooltip-text="$t('Jump around') + ' ' + $t('Day')"
                                            icon="IconCalendar"
                                            icon-size="h-5 w-5 text-white" />
                                    </div>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('week')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <ToolTipComponent
                                            direction="right"
                                            :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')"
                                            icon="IconCalendarWeek"
                                            icon-size="h-5 w-5 text-white" />
                                    </div>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <div @click="changeUserSelectedGoTo('month')" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                        <ToolTipComponent
                                            direction="right"
                                            :tooltip-text="$t('Jump around') + ' ' + $t('Month')"
                                            icon="IconCalendarMonth"
                                            icon-size="h-5 w-5 text-white" />
                                    </div>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <IconChevronRight stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer" @click="scrollToNextDay"/>

                <IconChevronRightPipe stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"  @click="nextTimeRange"/>
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
        </div>

        <slot name="multiEditCalendar" />

        <div class="flex items-center">
            <div class="flex items-center gap-x-3">
                <slot name="moreButtons">

                </slot>
                <Menu as="div" class="relative inline-block items-center text-left">
                    <div class="flex items-center">
                        <MenuButton id="displaySettings">
                            <span class="items-center flex">
                                <button type="button"
                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <ToolTipComponent
                                        direction="bottom"
                                        :tooltip-text="$t('Display Settings')"
                                        icon="IconSettings"
                                        icon-size="h-7 w-7"
                                    />
                                </button>
                            </span>
                        </MenuButton>
                    </div>
                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0">
                        <MenuItems
                            class="w-80 absolute right-0 top-12 origin-top-right shadow-lg bg-artwork-navigation-background rounded-lg ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="w-76 p-6">
                                <div class="flex items-center py-1">
                                    <input id="cb-high-contrast"
                                           v-model="userCalendarSettings.high_contrast"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <label for="cb-high-contrast"
                                           :class="userCalendarSettings.high_contrast ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class="ml-4 my-auto text-secondary cursor-pointer">
                                        {{ $t('High contrast') }}
                                    </label>
                                </div>
                                <div class="flex items-center py-1">
                                    <input id="cb-project-artists"
                                           v-model="userCalendarSettings.show_qualifications"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <label for="cb-project-artists"
                                           :class="userCalendarSettings.show_qualifications ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class="ml-4 my-auto text-secondary cursor-pointer">
                                        {{ $t('Show qualifications') }}
                                    </label>
                                </div>
                                <div class="flex items-center py-1">
                                    <input id="cb-project-status"
                                           v-model="userCalendarSettings.shift_notes"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <label for="cb-project-status"
                                           :class="userCalendarSettings.shift_notes ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class="ml-4 my-auto text-secondary cursor-pointer">
                                        {{ $t('Show notes') }}
                                    </label>
                                </div>
                                <div class="flex items-center py-1">
                                    <input id="cb-expand-days" v-model="userCalendarSettings.expand_days"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <label for="cb-expand-days"
                                           :class="userCalendarSettings.expand_days ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class="ml-4 my-auto text-secondary cursor-pointer">
                                        {{ $t('Expand days') }}
                                    </label>
                                </div>
                                <div class="flex items-center py-1">
                                    <input id="cb-expand-days" v-model="userCalendarSettings.display_project_groups"
                                           type="checkbox"
                                           class="input-checklist"/>
                                    <label for="cb-expand-days"
                                           :class="userCalendarSettings.display_project_groups ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class="ml-4 my-auto text-secondary cursor-pointer">
                                        {{ $t('Show project group') }}
                                    </label>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button class="text-sm mx-3 mb-4" @click="saveUserCalendarSettings">
                                    {{ $t('Save') }}
                                </button>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <ToolTipComponent v-if="this.$can('can commit shifts') || this.hasAdminRole()" direction="bottom" :tooltip-text="$t('Lock all shifts')" icon="IconCalendarCheck" icon-size="h-7 w-7" @click="commitAllShifts()"/>
                <ToolTipComponent direction="bottom" :tooltip-text="$t('History')" icon="IconHistory" icon-size="h-7 w-7" @click="openHistoryModal()"/>
                <ToolTipComponent direction="bottom" :tooltip-text="$t('Full screen')" icon="IconArrowsDiagonal" icon-size="h-7 w-7" v-if="!isFullscreen" @click="enterFullscreenMode"/>
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
import {CalendarIcon, PlusCircleIcon} from '@heroicons/vue/outline'
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ShiftPlanFilter from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFilter.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import SecondaryButton from "@/Layouts/Components/General/Buttons/SecondaryButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import Input from "@/Jetstream/Input.vue";

export default {
    name: "ShiftPlanFunctionBar",
    mixins: [Permissions, IconLib],
    components: {
        Input,
        ToolTipComponent,
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
    emits: ['enterFullscreenMode', 'previousTimeRange', 'nextTimeRange', 'openHistoryModal', 'selectGoToNextMode', 'selectGoToPreviousMode'],
    data() {
        return {
            //activeFilters: [],
            showConfirmCommitModal: false,
            scrollDays: 1,
            userCalendarSettings: useForm({
                show_qualifications: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_qualifications : false,
                shift_notes: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.shift_notes : false,
                high_contrast: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.high_contrast : false,
                expand_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.expand_days : false,
                display_project_groups: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.display_project_groups : false,
            })
        }
    },
    computed: {
        activeFilters() {
            let activeFiltersArray = []
            this.filterOptions.rooms.forEach((room) => {
                if (this.user_filters.rooms?.includes(room.id)) {
                    activeFiltersArray.push(room)
                }
            })


            this.filterOptions.eventTypes.forEach((eventType) => {
                if (this.user_filters.event_types?.includes(eventType.id)) {
                    activeFiltersArray.push(eventType)
                }
            })
            return activeFiltersArray
        }
    },
    methods: {
        saveUserCalendarSettings() {
            this.userCalendarSettings.patch(route('user.calendar_settings.update', {user: usePage().props.auth.user.id}), {
                preserveScroll: true
            })
            document.getElementById('displaySettings').click();
        },
        usePage,
        changeUserSelectedGoTo(type) {
            axios.patch(route('user.calendar.go.to.stepper', {user: this.$page.props.auth.user.id}), {
                goto_mode: type,
            }).then(() => {
                this.$page.props.auth.user.goto_mode = type;
            });
        },
        removeFilter(filter) {
            if (filter.value === 'rooms') {
                this.user_filters.rooms.splice(this.user_filters.rooms.indexOf(filter.id), 1);
                this.updateFilterValue('rooms', this.user_filters.rooms.length > 0 ? this.user_filters.rooms : null)
            }
            if (filter.value === 'event_types') {
                this.user_filters.event_types.splice(this.user_filters.event_types.indexOf(filter.id), 1);
                this.updateFilterValue('event_types', this.user_filters.event_types.length > 0 ? this.user_filters.event_types : null)
            }
        },
        updateFilterValue(key, value) {
            router.patch(route('user.shift.calendar.filter.single.value.update', {user: this.$page.props.auth.user.id}), {
                key: key,
                value: value
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
        scrollToNextDay() {
            this.$emit('selectGoToNextMode')
        },

        scrollToPreviousDay() {
            this.$emit('selectGoToPreviousMode')
        },

        enterFullscreenMode() {
            this.$emit('enterFullscreenMode')
        },
        previousTimeRange() {
            this.$emit('previousTimeRange')
        },
        nextTimeRange() {
            this.$emit('nextTimeRange')
        },
        filtersChanged(activeFilters) {
            this.activeFilters = activeFilters
        },
        openHistoryModal() {
            this.$emit('openHistoryModal')
        },
        commitAllShifts() {
            let filteredEvents = [];

            // Loop through each room in the shiftPlan array
            this.rooms.forEach(room => {
                // Loop through each day in the room object
                Object.values(room).forEach(day => {
                    // Check if day has an 'events' property, and it has a 'data' property
                    if (day.events) {
                        // Add the events to the allEvents array
                        filteredEvents = filteredEvents.concat(day.events);
                    }
                });
            });


            router.post('/shifts/commit', {events: filteredEvents}, {
                onSuccess: () => {
                    this.showConfirmCommitModal = false;
                },
                preserveScroll: true,
                preserveState: true,
            })
        },
    },
}
</script>

<style scoped>
</style>
