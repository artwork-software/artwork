<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2 ml-4">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :dateValueArray="dateValue"></date-picker-component>
            <div>
                <div>
                    <button  class="ml-2 -mt-2 text-black" @click="previousTimeRange">
                        <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                    </button>
                    <button class="ml-2 -mt-2 text-black" @click="nextTimeRange">
                        <ChevronRightIcon class="h-5 w-5 text-primary"/>
                    </button>
                </div>
            </div>
            <div class="flex items-center">
                <!-- Hier Button "Alle Schichten festschreiben" -->
            </div>

        </div>

        <div class="flex items-center">
            <div class="flex items-center">
                <img v-if="!isFullscreen" @click="enterFullscreenMode"
                     src="/Svgs/IconSvgs/icon_zoom_out.svg" class="h-6 w-6 mx-2 cursor-pointer"/>
                <!-- PAUL HIER DAS NEUE FILTER COMPONENT EINBAUEN -->
                <ShiftPlanFilter
                    class="mt-1"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    @filters-changed="filtersChanged"
                />

            </div>
        </div>
    </div>
    <div class="mb-1 ml-4 flex items-center w-full">
        <BaseFilterTag type="calendar" v-for="activeFilter in activeFilters" :filter="activeFilter.name" />
    </div>
</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon, CalendarIcon} from '@heroicons/vue/outline'
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/mixins/Permissions.vue";
import ShiftPlanFilter from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFilter.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";


export default {
    name: "CalendarFunctionBar",
    mixins: [Permissions],
    components: {
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
    },
    props: [
        'dateValue',
        'isFullscreen',
        'filterOptions',
        'allShiftsCommitted',
        'personalFilters'
    ],
    emits: ['enterFullscreenMode','previousTimeRange','nextTimeRange','commitAllShifts'],
    data() {
        return {
            activeFilters: []
        }
    },
    methods: {
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
        commitAllShifts(){
            this.$emit('commitAllShifts')
        },
    },
}
</script>

<style scoped>
</style>
