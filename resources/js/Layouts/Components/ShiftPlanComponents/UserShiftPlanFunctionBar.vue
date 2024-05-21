<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2 ml-4">
        <div>
            <div class="inline-flex items-center">
                <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="true"></date-picker-component>
                <div class="flex items-center">
                    <div class="flex items-center">
                        <button class="ml-2 text-black" @click="previousTimeRange">
                            <IconChevronLeft stroke-width="1.5" class="h-5 w-5 text-primary"/>
                        </button>
                        <button class="ml-2 text-black" @click="nextTimeRange">
                            <IconChevronRight stroke-width="1.5" class="h-5 w-5 text-primary"/>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center">
            <div @click="showCalendarAboSettingModal = true" class="flex items-center gap-x-1 text-sm hover:text-artwork-buttons-hover duration-150 transition-all ease-in-out cursor-pointer">
                <IconCalendarStar class="h-5 w-5"/>
                Kalender Abonnieren
            </div>
        </div>
        <div v-if="type !== 'freelancer' && type !== 'service_provider'">
            {{ $t('Planned/target') }}: {{ totalPlannedWorkingHours.toFixed(1) }} / {{ totalHoursExpectedWork }}
        </div>
        <div v-if="type === 'freelancer' || type === 'service_provider'">
            {{ $t('Planned') }}: {{ totalPlannedWorkingHours?.toFixed(1) }}
        </div>
    </div>



    <CalendarAboSettingModal v-if="showCalendarAboSettingModal" @close="closeCalendarAboSettingModal" :eventTypes="eventTypes"/>

    <CalendarAboInfoModal v-if="showCalendarAboInfoModal" @close="showCalendarAboInfoModal = false" />
</template>

<script>
import Button from "@/Jetstream/Button";
import {CalendarIcon} from '@heroicons/vue/outline'
import {ChevronDownIcon, ChevronLeftIcon, ChevronRightIcon} from "@heroicons/vue/solid";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ShiftPlanFilter from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFilter.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import IconLib from "@/Mixins/IconLib.vue";
import CalendarAboSettingModal from "@/Pages/Shifts/Components/CalendarAboSettingModal.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";


export default {
    name: "UserShiftPlanFunctionBar",
    mixins: [Permissions, IconLib],
    components: {
        CalendarAboInfoModal,
        CalendarAboSettingModal,
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
        'weeklyWorkingHours',
        'type',
        'totalPlannedWorkingHours',
        'eventTypes'
    ],
    emits: ['previousTimeRange', 'nextTimeRange'],
    data() {
        return {
            activeFilters: [],
            showCalendarAboSettingModal: false,
            showCalendarAboInfoModal: false,
        }
    },
    computed: {
        totalHoursExpectedWork() {
            const startDate = new Date(this.dateValue[0]);
            const endDate = new Date(this.dateValue[1]);

            // Calculate the time difference in milliseconds between the two dates
            const timeDifference = endDate - startDate;

            // Calculate the total number of days in the date range (add 1 to include both start and end dates)
            const totalDays = Math.ceil(timeDifference / (1000 * 60 * 60 * 24)) + 1;

            // Calculate the average number of hours worked per day
            const hoursPerDay = this.weeklyWorkingHours / 7;

            // Calculate the total number of hours that need to be worked
            return (totalDays * hoursPerDay).toFixed(1);
        },
    },
    methods: {

        previousTimeRange() {
            this.$emit('previousTimeRange')
        },
        nextTimeRange() {
            this.$emit('nextTimeRange')
        },
        closeCalendarAboSettingModal(){
            this.showCalendarAboSettingModal = false;
            this.showCalendarAboInfoModal = true;
        }
    },
}
</script>

<style scoped>
</style>
