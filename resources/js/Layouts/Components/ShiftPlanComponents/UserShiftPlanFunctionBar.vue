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
        </div>
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
    name: "UserShiftPlanFunctionBar",
    mixins: [Permissions],
    components: {
        BaseFilterTag,
        ShiftPlanFilter,
        Dropdown,
        Button,
        CalendarIcon,
        ChevronDownIcon,
        ChevronLeftIcon,
        ChevronRightIcon,
        DatePickerComponent,
    },
    props: [
        'dateValue',
    ],
    emits: ['previousTimeRange','nextTimeRange'],
    data() {
        return {
            activeFilters: []
        }
    },
    methods: {

        previousTimeRange(){
            this.$emit('previousTimeRange')
        },
        nextTimeRange(){
            this.$emit('nextTimeRange')
        },
    },
}
</script>

<style scoped>
</style>
