<template>
    <div class="w-[98%] flex justify-between items-center mt-4 mb-2">
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
            <div class="flex items-center" v-if="this.$can('can commit shifts') || this.hasAdminRole()">
                <AddButton text="Alle Schichten festsetzen" type="secondary" @click="showConfirmCommitModal = true" />
            </div>

            <div class="ml-5 flex items-center" >
                <button class="subpixel-antialiased flex items-center linkText cursor-pointer"
                        @click="openHistoryModal()">
                    <ChevronRightIcon
                        class="-mr-0.5 h-4 w-4  group-hover:text-white"
                        aria-hidden="true"/>
                    Verlauf ansehen
                </button>
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
                    :user_filters="user_filters"
                />

            </div>
        </div>
    </div>
    <div class="mb-1 ml-4 flex items-center w-full">
        <BaseFilterTag type="calendar" v-for="activeFilter in activeFilters" :filter="activeFilter.name" />
    </div>


    <ConfirmDeleteModal
        v-if="showConfirmCommitModal"
        @closed="showConfirmCommitModal = false"
        @delete="commitAllShifts"
        title="Dienstplan festschreiben"
        description="Bist du sicher, dass du den Dienstplan festschreiben möchtest?"
        button="Festschreiben"
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
import AddButton from "@/Layouts/Components/AddButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {Inertia} from "@inertiajs/inertia";


export default {
    name: "ShiftPlanFunctionBar",
    mixins: [Permissions],
    components: {
        ConfirmDeleteModal,
        AddButton,
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
            activeFilters: [],
            showConfirmCommitModal: false,
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
