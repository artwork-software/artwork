<template>
    <app-layout>
    <ProjectShowHeaderComponent :project-delete-ids="projectDeleteIds"
                                :projectWriteIds="projectWriteIds"
                                :projectManagerIds="projectManagerIds"
                                :project="project"
                                :eventTypes="eventTypes"
                                :currentGroup="currentGroup"
                                :states="states"
                                :project-groups="projectGroups"
                                :first-event-in-project="firstEventInProject"
                                :last-event-in-project="lastEventInProject"
                                :rooms-with-audience="roomsWithAudience"
                                :group-projects="groupProjects"
                                :access_budget="access_budget"
                                open-tab="calendar"
                                :project-tabs="tabs"
    >
        <CalendarTab :projectWriteIds="projectWriteIds"
                     :user_filters="user_filters"
                     :projectManagerIds="projectManagerIds"
                     :project="project"
                     :selected-date="selectedDate"
                     :date-value="dateValue"
                     :events="events"
                     :rooms="rooms"
                     :events-without-room="eventsWithoutRoom"
                     :filter-options="filterOptions"
                     :personal-filters="personalFilters"
                     :at-a-glance="atAGlance"
                     :events-at-a-glance="eventsAtAGlance"
                     :calendar="calendar"
                     :days="days"
                     :event-types="eventTypes"
                     @change-at-a-glance="changeAtAGlance"
        />
    </ProjectShowHeaderComponent>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSecondSidenav
                :project="project"
                :project-members="this.project.users"
                :project-members-write-access="projectWriteIds"
                :project-categories="projectCategories"
                :project-genres="projectGenres"
                :project-sectors="projectSectors"
                :project-category-ids="projectCategoryIds"
                :project-genre-ids="projectGenreIds"
                :project-sector-ids="projectSectorIds"
                :categories="categories"
                :sectors="sectors"
                :genres="genres"
                :project-manager-ids="projectManagerIds"

            />
        </BaseSidenav>
    </app-layout>
</template>

<script>
import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav.vue";
import CalendarTab from "@/Pages/Projects/Components/TabComponents/CalendarTab.vue";
import {Inertia} from "@inertiajs/inertia";
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";

export default {
    components: {
        InfoTab,
        CalendarTab,
        ProjectSidenav,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout,
        ProjectShowHeaderComponent
    },
    props: [
        'project',
        'eventTypes',
        'currentGroup',
        'states',
        'projectGroups',
        'firstEventInProject',
        'lastEventInProject',
        'roomsWithAudience',
        'groupProjects',
        'projectCategories',
        'projectGenres',
        'projectSectors',
        'projectCategoryIds',
        'projectGenreIds',
        'projectSectorIds',
        'categories',
        'sectors',
        'genres',
        'projectState',
        'eventsAtAGlance',
        'selectedDate',
        'calendar',
        'dateValue',
        'days',
        'rooms',
        'events',
        'filterOptions',
        'personalFilters',
        'eventsWithoutRoom',
        'projectManagerIds',
        'projectWriteIds',
        'projectDeleteIds',
        'user_filters',
        'access_budget',
        'tabs'
    ],
    data() {
        return {
            show: false,
            atAGlance: this.eventsAtAGlance.length > 0,
        }
    },
    methods:{
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
