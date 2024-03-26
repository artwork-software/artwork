<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2">
        <div class="inline-flex items-center">
            <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="true"></date-picker-component>
            <div class="flex items-center">
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

            <div class="ml-5 flex items-center" >
                <button class="subpixel-antialiased flex items-center linkText cursor-pointer"
                        @click="openHistoryModal()">
                    <IconChevronRight stroke-width="1.5"
                        class="-mr-0.5 h-4 w-4  group-hover:text-white"
                        aria-hidden="true"/>
                    {{ $t('View history')}}
                </button>
            </div>

        </div>

        <div class="flex items-center">
            <div class="flex items-center gap-x-3">
                <IconArrowsDiagonal stroke-width="1.5" v-if="!isFullscreen" @click="enterFullscreenMode"
                      class="h-7 w-7 text-artwork-buttons-context cursor-pointer"/>
                <!-- PAUL HIER DAS NEUE FILTER COMPONENT EINBAUEN -->
                <ShiftPlanFilter
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
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
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {Inertia} from "@inertiajs/inertia";
import SecondaryButton from "@/Layouts/Components/General/Buttons/SecondaryButton.vue";
import IconLib from "@/mixins/IconLib.vue";


export default {
    name: "ShiftPlanFunctionBar",
    mixins: [Permissions, IconLib],
    components: {
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
    },
    props: [
        'dateValue',
        'isFullscreen',
        'filterOptions',
        'allShiftsCommitted',
        'personalFilters',
        'rooms',
        'user_filters'
    ],
    emits: ['enterFullscreenMode','previousTimeRange','nextTimeRange', 'openHistoryModal'],
    data() {
        return {
            //activeFilters: [],
            showConfirmCommitModal: false,
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
            Inertia.patch(route('user.shift.calendar.filter.single.value.update', {user: this.$page.props.user.id}), {
                key: key,
                value: value
            }, {
                preserveScroll: true,
            });
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



            Inertia.post('/shifts/commit', { events: filteredEvents }, {
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
