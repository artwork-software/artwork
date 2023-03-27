<template>
    <div class="w-full flex justify-between items-center mt-4 mb-2 ml-4">
        <div class="inline-flex items-center">
            <date-picker-component @change-calendar-type="changeCalendarType" :dateValue="dateValue"></date-picker-component>
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
                <img src="/Svgs/IconSvgs/icon_settings.svg" class="h-6 w-6 mx-2"/>
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


export default {
    name: "CalendarFunctionBar",
    components: {
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
    emits: ['changeAtAGlance','changeCalendarType'],
    data() {
        return {
            atAGlance: this.atAGlance,
        }
    },
    methods: {
        changeAtAGlance(atAGlance) {
            this.$emit('changeAtAGlance', atAGlance)
        },
        changeCalendarType(){
            this.$emit('changeCalendarType');
        }
    },
}
</script>

<style scoped>

</style>
