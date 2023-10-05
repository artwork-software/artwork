<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2 ml-14">
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
                    :type="project ? 'project' : 'individual'"
                    @filters-changed="filtersChanged"
                />


                <Menu as="div" class="relative inline-block flex items-center text-left">
                    <div class="">
                        <MenuButton>
                            <span class="inline-flex">
                                <button
                                        type="button"
                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2"/>
                                </button>
                                <span v-if="$page.props.user.calendar_settings.project_status || $page.props.user.calendar_settings.options || $page.props.user.calendar_settings.project_management || $page.props.user.calendar_settings.repeating_events || $page.props.user.calendar_settings.work_shifts"
                                      class="rounded-full border-2 border-error w-2 h-2 bg-error absolute ml-6 ring-white ring-1">
                                </span>
                            </span>
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
                        <MenuItems class="w-80 absolute right-0 top-12 origin-top-right rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="w-44 p-6">
                                <div class="flex py-1" v-if="!project">
                                    <input v-model="userCalendarSettings.project_status"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">Projektstatus</p>
                                </div>
                                <div class="flex py-1">
                                    <input v-model="userCalendarSettings.options"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">Optionspriorisierung</p>
                                </div>
                                <div class="flex py-1" v-if="!project">
                                    <input v-model="userCalendarSettings.project_management"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.project_management ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">Projektleitungen</p>
                                </div>
                                <div class="flex py-1">
                                    <input v-model="userCalendarSettings.repeating_events"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.repeating_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">Wiederholungstermin</p>
                                </div>
                                <div class="flex py-1" v-if="$page.props.can.ma_manager || $page.props.can.shift_planner">
                                    <input v-model="userCalendarSettings.work_shifts"
                                           type="checkbox"
                                           class="checkBoxOnDark"/>
                                    <p :class="userCalendarSettings.work_shifts ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                       class=" ml-4 my-auto text-secondary">Schichten</p>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button class="text-sm mx-3 mb-4" @click="saveUserCalendarSettings">Speichern</button>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
            <button @click="openEventComponent()" type="button" :class="'bg-buttonBlue hover:bg-buttonHover'"
                    class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton  focus:outline-none">
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
import {useForm} from "@inertiajs/inertia-vue3";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";


export default {
    name: "CalendarFunctionBar",
    mixins: [Permissions],
    components: {
        BaseFilter,
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
            activeFilters: [],
            userCalendarSettings: useForm({
                project_status: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.project_status : false,
                options: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.options : false,
                project_management: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.project_management : false,
                repeating_events: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.repeating_events : false,
                work_shifts: this.$page.props.user.calendar_settings ? this.$page.props.user.calendar_settings.work_shifts : false
            }),
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
        },
        saveUserCalendarSettings() {
            this.userCalendarSettings.patch(route('user.calendar_settings.update', {user: this.$page.props.user.id}))
        }

    },
}
</script>

<style scoped>
</style>
