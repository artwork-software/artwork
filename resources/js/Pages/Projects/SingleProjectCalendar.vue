<template>
    <app-layout>
    <ProjectShowHeaderComponent :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup"
                                :states="states" :project-groups="projectGroups"
                                :first-event-in-project="firstEventInProject"
                                :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience" :group-projects="groupProjects" open-tab="calendar">
        <CalendarTab :project="project" :selected-date="selectedDate" :date-value="dateValue" :events="events" :rooms="rooms" :events-without-room="eventsWithoutRoom" :filter-options="filterOptions" :personal-filters="personalFilters" :at-a-glance="atAGlance" :events-at-a-glance="eventsAtAGlance" :calendar="calendar" :days="days" :event-types="eventTypes"></CalendarTab>
    </ProjectShowHeaderComponent>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSecondSidenav
                :project="project"
                :project-members="projectMembers"
                :project-members-write-access="projectCanWriteIds"
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

export default {
    components: {
        CalendarTab,
        ProjectSidenav,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout,
        ProjectShowHeaderComponent},
    props: [
        'project',
        'eventTypes',
        'currentGroup',
        'states',
        'projectGroups',
        'firstEventInProject',
        'lastEventInProject',
        'RoomsWithAudience',
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
        'eventsWithoutRoom'

    ],
    data() {
        return {
            show: false,
            atAGlance: this.eventsAtAGlance.length > 0,
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods:{
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        },
        projectMembers: function () {

            let projectMemberArray = [];
            this.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        },
        projectCanWriteIds: function () {
            let canWriteArray = [];
            this.project.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray;
        },
    }
}
</script>


<style scoped>

</style>
