<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2 ml-4">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :project="project" :dateValueArray="dateValue"></date-picker-component>
            <div v-if="!project">
                <div v-if="dateValue && dateValue[0] === dateValue[1]">
                    <button  class="ml-2 -mt-2 text-black" @click="previousDay">
                        <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 -mt-2 text-black" @click="nextDay">
                        <ChevronRightIcon class="h-5 w-5 text-primary"/>
                    </button>
                </div>
                <div v-else>
                    <button  class="ml-2 -mt-2 text-black" @click="previousTimeRange">
                        <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 -mt-2 text-black" @click="nextTimeRange">
                        <ChevronRightIcon class="h-5 w-5 text-primary"/>
                    </button>
                </div>
            </div>
            <div v-if="dateValue[0] !== dateValue[1]" class="flex items-center">
              <SwitchGroup v-if="!roomMode" as="div" class="flex items-center ml-2">
                <Switch v-model="atAGlance" @click="changeAtAGlance()"
                        class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none">
                    <span class="sr-only">Use setting</span>
                    <span aria-hidden="true" :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'" class="pointer-events-none absolute h-full w-full rounded-md"/>
                    <span aria-hidden="true"
                          :class="[atAGlance ? 'bg-indigo-600' : 'bg-gray-200', 'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out']"/>
                    <span aria-hidden="true"
                          :class="[atAGlance ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out']"/>
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="font-medium text-gray-900">Auf einen Blick</span>
                </SwitchLabel>
            </SwitchGroup>
                <SwitchGroup v-if="!roomMode"  as="div" class="flex items-center ml-3">
                    <Switch v-model="multiEdit" @click="changeMultiEdit(multiEdit)"
                            class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none">
                        <span class="sr-only">Use setting</span>
                        <span aria-hidden="true" :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'" class="pointer-events-none absolute h-full w-full rounded-md"/>
                        <span aria-hidden="true"
                              :class="[multiEdit ? 'bg-indigo-600' : 'bg-gray-200', 'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out']"/>
                        <span aria-hidden="true"
                              :class="[multiEdit ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                        <span class="font-medium text-gray-900">Multiedit</span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>

        </div>

        <div class="flex items-center">
            <div class="flex items-center">
                <ZoomInIcon @click="incrementZoomFactor" :disabled="zoomFactor <= 0.2" v-if="!atAGlance && isFullscreen"
                            class="h-7 w-7 mx-2 cursor-pointer"></ZoomInIcon>
                <ZoomOutIcon @click="decrementZoomFactor" :disabled="zoomFactor >= 1.4"
                             v-if="!atAGlance && isFullscreen" class="h-7 w-7 mx-2 cursor-pointer"></ZoomOutIcon>
                <img v-if="!atAGlance && !isFullscreen" @click="enterFullscreenMode"
                     src="/Svgs/IconSvgs/icon_zoom_out.svg" class="h-6 w-6 mx-2 cursor-pointer"/>
                <IndividualCalendarFilterComponent
                    class="mt-1"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :at-a-glance="atAGlance"
                    @filters-changed="filtersChanged"
                />

                <!-- Calendar Settings Dropdown -->
                <Dropdown :hide-chevron="true" :open="calendarSettingsOpen" align="right" content-classes="" width="64"
                          class="text-right">
                    <template #trigger>
                                            <span class="inline-flex">
                                                <button @click="calendarSettingsOpen = !calendarSettingsOpen"
                                                        type="button"
                                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                                    <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2"/>
                                                </button>
                                                <span v-if="$page.props.user.calendar_settings.project_status || $page.props.user.calendar_settings.options || $page.props.user.calendar_settings.project_management || $page.props.user.calendar_settings.repeating_events || $page.props.user.calendar_settings.work_shifts"
                                                      class="rounded-full border-2 border-error w-2 h-2 bg-error absolute ml-6 ring-white ring-1">
                                                </span>
                                            </span>
                    </template>

                    <template #content>
                        <div class="w-44 p-6">
                            <div class="flex py-1">
                                <input @click="toggle_calendarSettingsProjectStatus"
                                       v-model="$page.props.user.calendar_settings.project_status"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="$page.props.user.calendar_settings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                   class=" ml-4 my-auto text-secondary">Projektstatus</p>
                            </div>
                            <div class="flex py-1">
                                <input @click="toggle_calendarSettingsOptions"
                                       v-model="$page.props.user.calendar_settings.options"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="$page.props.user.calendar_settings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                   class=" ml-4 my-auto text-secondary">Optionspriorisierung</p>
                            </div>
                            <div class="flex py-1">
                                <input @click="toggle_calendarSettingsProjectManagement"
                                       v-model="$page.props.user.calendar_settings.project_management"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="$page.props.user.calendar_settings.project_management ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                   class=" ml-4 my-auto text-secondary">Projektleitungen</p>
                            </div>
                            <div class="flex py-1">
                                <input @click="toggle_calendarSettingsRepeatingEvents"
                                       v-model="$page.props.user.calendar_settings.repeating_events"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="$page.props.user.calendar_settings.repeating_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                   class=" ml-4 my-auto text-secondary">Wiederholungstermin</p>
                            </div>
                            <div class="flex py-1">
                                <input @click="toggle_calendarSettingsWorkShifts"
                                       v-model="$page.props.user.calendar_settings.work_shifts"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="$page.props.user.calendar_settings.work_shifts ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                   class=" ml-4 my-auto text-secondary">Schichten</p>
                            </div>
                        </div>
                    </template>
                </Dropdown>
            </div>
            <button @click="openEventComponent()" type="button" :class="'bg-buttonBlue hover:bg-buttonHover'"
                    class="flex p-2 px-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton  focus:outline-none">
                <PlusCircleIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                <p class="text-sm">Neue Belegung</p>
            </button>
        </div>
    </div>
    <div class="mb-1 ml-4 flex items-center w-full">
        <BaseFilterTag type="calendar" v-for="activeFilter in activeFilters" :filter="activeFilter.name" />
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon, CalendarIcon, ZoomInIcon, ZoomOutIcon} from '@heroicons/vue/outline'
import {Menu, MenuButton, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import Permissions from "@/mixins/Permissions.vue";


export default {
    name: "CalendarFunctionBar",
    mixins: [Permissions],
    components: {
        BaseFilterTag,
        Dropdown,
        Menu,
        MenuItems,
        MenuButton,
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
        ZoomInIcon,
        ZoomOutIcon
    },
    props: [
        'atAGlance',
        'dateValue',
        'isFullscreen',
        'zoomFactor',
        'project',
        'roomMode',
        'filterOptions',
        'personalFilters'
    ],
    emits: ['changeAtAGlance', 'changeMultiEdit', 'enterFullscreenMode', 'incrementZoomFactor', 'decrementZoomFactor','nextDay','previousDay','openEventComponent','previousTimeRange','nextTimeRange'],
    data() {
        return {
            atAGlance: this.atAGlance,
            calendarSettingsOpen: false,
            multiEdit: false,
            activeFilters: []
        }
    },
    methods: {
        changeAtAGlance() {
            this.$emit('changeAtAGlance')
        },
        changeMultiEdit(multiEdit) {
            this.$emit('changeMultiEdit', !multiEdit)
        },
        enterFullscreenMode() {
            this.$emit('enterFullscreenMode')
        },
        toggle_calendarSettingsProjectStatus() {
            this.$inertia.post(route('toggle.calendar_settings_project_status'))
        },
        toggle_calendarSettingsOptions() {
            this.$inertia.post(route('toggle.calendar_settings_options'))
        },
        toggle_calendarSettingsProjectManagement() {
            this.$inertia.post(route('toggle.calendar_settings_project_management'))
        },
        toggle_calendarSettingsRepeatingEvents() {
            this.$inertia.post(route('toggle.calendar_settings_repeating_events'))
        },
        toggle_calendarSettingsWorkShifts() {
            this.$inertia.post(route('toggle.calendar_settings_work_shifts'))
        },
        incrementZoomFactor() {
            this.$emit('incrementZoomFactor')
        },
        decrementZoomFactor() {
            this.$emit('decrementZoomFactor')
        },
        nextDay(){
            this.$emit('nextDay')
        },
        previousDay(){
            this.$emit('previousDay')
        },
        openEventComponent(){
            this.$emit('openEventComponent')
        },
        previousTimeRange(){
            this.$emit('previousTimeRange')
        },
        nextTimeRange(){
            this.$emit('nextTimeRange')
        },
        filtersChanged(activeFilters) {
            this.activeFilters = activeFilters
        }

    },
}
</script>

<style scoped>
</style>
