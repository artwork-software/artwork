<template>
    <div class="mt-6 bg-lightBackgroundGray">
        <div class="mt-6 max-w-screen-2xl bg-lightBackgroundGray">
            <div v-if="calendarType && calendarType === 'daily'">
                Daily
                <CalendarComponent
                    :selected-date="selectedDate"
                    :dateValue="dateValue"
                    :eventTypes=this.eventTypes initial-view="day"
                    :events="this.events.events"
                    :rooms="this.rooms"
                    :project="project"
                    :events-without-room="eventsWithoutRoom"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                />
            </div>
            <div v-else>
                <IndividualCalendarAtGlanceComponent
                    v-if="atAGlance"
                    :dateValue="dateValue"
                    :project="project"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :rooms="rooms"
                    :eventsAtAGlance="eventsAtAGlance"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    @change-at-a-glance="changeAtAGlance"
                >
                </IndividualCalendarAtGlanceComponent>

                <IndividualCalendarComponent
                    v-else
                    :events-without-room="eventsWithoutRoom"
                    :project="project"
                    :dateValue="dateValue"
                    :atAGlance="this.atAGlance"
                    :eventTypes=this.eventTypes
                    :calendarData="calendar"
                    :rooms="rooms"
                    :days="days"
                    :filter-options="filterOptions"
                    :personal-filters="personalFilters"
                    :user_filters="user_filters"
                    @change-at-a-glance="changeAtAGlance"
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

export default {
    components: {
        IndividualCalendarComponent,
        CalendarComponent,
        IndividualCalendarAtGlanceComponent,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
    },
    emits: ['change-at-a-glance'],
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
        'eventTypes',
        'projectWriteIds',
        'projectManagerIds',
        'user_filters'
    ],
    methods: {
        changeAtAGlance() {
            this.$emit('change-at-a-glance');
        }
    }
}
</script>
