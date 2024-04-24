<template>
    <div class="mt-6 bg-lightBackgroundGray">
        <div class="mt-6 bg-lightBackgroundGray">
            <div v-if="calendarType && calendarType === 'daily'">
                Daily
                <CalendarComponent
                    :selected-date="selectedDate ?? loadedProjectInformation['CalendarTab'].selected_date"
                    :dateValue="dateValue ?? loadedProjectInformation['CalendarTab'].date_value"
                    :eventTypes="this.eventTypes ?? headerObject.eventTypes " initial-view="day"
                    :events="this.events.events ?? loadedProjectInformation['CalendarTab'].events.events"
                    :rooms="this.rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                    :project="project ?? headerObject.project"
                    :events-without-room="eventsWithoutRoom ?? headerObject.eventsWithoutRoom"
                    :filter-options="filterOptions ?? loadedProjectInformation['CalendarTab'].filter_options"
                    :personal-filters="personalFilters ?? loadedProjectInformation['CalendarTab'].personal_filters"
                    :user_filters="user_filters ?? loadedProjectInformation['CalendarTab'].user_filters"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                />
            </div>
            <div v-else>
                <IndividualCalendarAtGlanceComponent
                    v-if="atAGlance"
                    :dateValue="dateValue ?? loadedProjectInformation['CalendarTab'].date_value"
                    :project="project ?? headerObject.project"
                    :atAGlance="this.atAGlance ?? loadedProjectInformation['CalendarTab'].events_at_a_glance.length > 0"
                    :eventTypes="this.eventTypes ?? headerObject.eventTypes"
                    :rooms="rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                    :eventsAtAGlance="eventsAtAGlance ?? loadedProjectInformation['CalendarTab'].events_at_a_glance"
                    :filter-options="filterOptions ?? loadedProjectInformation['CalendarTab'].filter_options"
                    :personal-filters="personalFilters ?? loadedProjectInformation['CalendarTab'].personal_filters"
                    :user_filters="user_filters ?? loadedProjectInformation['CalendarTab'].user_filters"
                    @change-at-a-glance="changeAtAGlance"
                    :first_project_tab_id="this.first_project_tab_id"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                >
                </IndividualCalendarAtGlanceComponent>

                <IndividualCalendarComponent
                    v-else
                    :events-without-room="eventsWithoutRoom ?? headerObject.eventsWithoutRoom"
                    :project="project ?? headerObject.project"
                    :dateValue="dateValue ?? loadedProjectInformation['CalendarTab'].date_value"
                    :atAGlance="this.atAGlance"
                    :eventTypes="this.eventTypes ?? headerObject.eventTypes"
                    :calendarData="calendar ?? loadedProjectInformation['CalendarTab'].calendar"
                    :rooms="rooms ?? loadedProjectInformation['CalendarTab'].rooms"
                    :days="days ?? loadedProjectInformation['CalendarTab'].days"
                    :filter-options="filterOptions ?? loadedProjectInformation['CalendarTab'].filter_options"
                    :personal-filters="personalFilters ?? loadedProjectInformation['CalendarTab'].personal_filters"
                    :user_filters="user_filters ?? loadedProjectInformation['CalendarTab'].user_filters"
                    @change-at-a-glance="changeAtAGlance"
                    :first_project_tab_id="this.first_project_tab_id"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
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
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
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
        'eventTypes',
        'projectWriteIds',
        'projectManagerIds',
        'user_filters',
        'loadedProjectInformation',
        'headerObject',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    data() {
        return {
            atAGlance: this.eventsAtAGlance?.length > 0 ?? this.loadedProjectInformation['CalendarTab']?.eventsAtAGlance?.length > 0,
        }
    },
    methods: {
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
            Inertia.reload({
                data: {
                    atAGlance: this.atAGlance,
                },
                only: ['calendar', 'eventsAtAGlance']
            })
        }
    }
}
</script>
