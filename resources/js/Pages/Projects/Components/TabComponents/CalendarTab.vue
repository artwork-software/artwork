<template>
    <div class="mt-6 p-5  bg-lightBackgroundGray">
        <div class="px-5 mt-6 max-w-screen-2xl bg-lightBackgroundGray">
            <div v-if="calendarType && calendarType === 'daily'">
                <CalendarComponent
                    :selected-date="selectedDate"
                    :dateValue="dateValue"
                    :eventTypes=this.eventTypes initial-view="day"
                    :events="this.events.events"
                    :rooms="this.rooms"
                    :events-without-room="eventsWithoutRoom"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                />
            </div>
            <div v-else>
                <IndividualCalendarAtGlanceComponent
                    :dateValue="dateValue"
                    v-if="atAGlance"
                    :project="project"
                    @change-at-a-glance="changeAtAGlance"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :rooms="rooms"
                    :eventsAtAGlance="eventsAtAGlance"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                >
                </IndividualCalendarAtGlanceComponent>

                <IndividualCalendarComponent
                    :events-without-room="eventsWithoutRoom"
                    :project="project"
                    :dateValue="dateValue"
                    v-else
                    @change-at-a-glance="changeAtAGlance"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :calendarData="calendar"
                    :rooms="rooms"
                    :days="days"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                />
            </div>
        </div>
    </div>
</template>

<script>


import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import {Inertia} from "@inertiajs/inertia";

export default {
    components: {
        IndividualCalendarComponent,
        CalendarComponent,
        IndividualCalendarAtGlanceComponent,
        PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError
    },
    props: [
        'project',
        'calendarType',
        'selectedDate',
        'dateValue',
        'events',
        'rooms',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'atAGlance',
        'eventsAtAGlance',
        'calendar',
        'days',
        'eventTypes'
    ],
    data() {

    },
    methods: {
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
            if (this.atAGlance) {
                Inertia.reload({
                    data: {
                        atAGlance: this.atAGlance,
                    },
                    only: ['calendar']
                })
            }
        }
    }
}
</script>

<style scoped>

</style>
