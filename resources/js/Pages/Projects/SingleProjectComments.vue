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
                                open-tab="comment"
                                :project-tabs="tabs"
    >
        <CommentTab :projectWriteIds="projectWriteIds"
                    :projectManagerIds="projectManagerIds"
                    :project="project"
                    :is-member-of-a-department="isMemberOfADepartment"
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
import CommentTab from "@/Pages/Projects/Components/TabComponents/CommentTab.vue";
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";

export default {
    components: {
        InfoTab,
        CommentTab,
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
        'isMemberOfADepartment',
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
        'isMemberOfADepartment',
        'projectWriteIds',
        'projectManagerIds',
        'projectDeleteIds',
        'access_budget',
        'tabs'
    ],
    data() {
        return {
            show: false,
        }
    },
}
</script>
