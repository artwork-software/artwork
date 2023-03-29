<template>
    <div class="w-full flex justify-between items-center mt-4 mb-2 ml-4">
        <div class="inline-flex items-center">
            <date-picker-component :dateValueArray="dateValue"></date-picker-component>
            <button class="ml-2 text-black" @click="$refs.vuecal.previous()">
                <ChevronLeftIcon class="h-5 w-5 text-primary"/>
            </button>
            <button class="ml-2 text-black" @click="$refs.vuecal.next()">
                <ChevronRightIcon class="h-5 w-5 text-primary"/>
            </button>
        </div>
        <SwitchGroup as="div" class="flex items-center">
            <Switch v-model="atAGlance" @click="changeAtAGlance(atAGlance)"
                    class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none">
                <span class="sr-only">Use setting</span>
                <span aria-hidden="true" class="pointer-events-none absolute h-full w-full rounded-md bg-white"/>
                <span aria-hidden="true"
                      :class="[atAGlance ? 'bg-indigo-600' : 'bg-gray-200', 'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out']"/>
                <span aria-hidden="true"
                      :class="[atAGlance ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-gray-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out']"/>
            </Switch>
            <SwitchLabel as="span" class="ml-3 text-sm">
                <span class="font-medium text-gray-900">Auf einen Blick</span>
            </SwitchLabel>
        </SwitchGroup>
        <div class="flex items-center">
            <div class="flex items-center">
                <img v-if="!atAGlance" src="/Svgs/IconSvgs/icon_zoom_out.svg" class="h-6 w-6 mx-2"/>
                <IndividualCalendarFilterComponent class="mt-1"/>
                <Dropdown :hide-chevron="true" :open="calendarSettingsOpen" align="right" class="text-right">
                    <template #trigger>
                                            <span class="inline-flex">
                                                <button @click="calendarSettingsOpen = !calendarSettingsOpen" type="button"
                                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                                    <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2"/>
                                                </button>
                                            </span>
                    </template>

                    <template #content>
                        <div class="w-44 p-4">
                            <div class="flex">
                                <input @click="toggle_calendarSettingsProjectStatus" v-model="$page.props.user.calendar_settings.project_status"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p
                                    class=" ml-4 my-auto text-sm text-secondary">Projektstatus</p>
                            </div>
                            <div class="flex">
                                <input @click="toggle_calendarSettingsOptions" v-model="$page.props.user.calendar_settings.options"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p
                                    class=" ml-4 my-auto text-sm text-secondary">Optionspriorisierung</p>
                            </div>
                            <div class="flex">
                                <input @click="toggle_calendarSettingsProjectManagement" v-model="$page.props.user.calendar_settings.project_management"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p
                                    class=" ml-4 my-auto text-sm text-secondary">Projektleitungen</p>
                            </div>
                            <div class="flex">
                                <input @click="toggle_calendarSettingsRepeatingEvents" v-model="$page.props.user.calendar_settings.repeating_events"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p
                                    class=" ml-4 my-auto text-sm text-secondary">Wiederholungstermin</p>
                            </div>
                            <div class="flex">
                                <input @click="toggle_calendarSettingsWorkShifts" v-model="$page.props.user.calendar_settings.work_shifts"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p
                                    class=" ml-4 my-auto text-sm text-secondary">Schichten</p>
                            </div>
                        </div>
                    </template>
                </Dropdown>
            </div>
            <button type="button" :class="'bg-buttonBlue hover:bg-buttonHover'"
                    class="flex p-2 px-4 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton  focus:outline-none">
                <PlusCircleIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                <p class="text-sm">Neue Belegung</p>
            </button>
        </div>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon, CalendarIcon} from '@heroicons/vue/outline'
import {Menu, MenuButton, MenuItems, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";


export default {
    name: "CalendarFunctionBar",
    components: {
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
        DatePickerComponent
    },
    props: ['atAGlance', 'dateValue'],
    emits: ['changeAtAGlance'],
    data() {
        return {
            atAGlance: this.atAGlance,
            calendarSettingsOpen: false,
        }
    },
    methods: {
        changeAtAGlance(atAGlance) {
            this.$emit('changeAtAGlance', atAGlance)
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
    },
}
</script>

<style scoped>

</style>
