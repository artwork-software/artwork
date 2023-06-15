<template>
    <app-layout>
    <ProjectShowHeaderComponent :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup"
                                :states="states" :project-groups="projectGroups"
                                :first-event-in-project="firstEventInProject"
                                :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience" :group-projects="groupProjects">
        <ChecklistTab :project="project" :opened_checklists="opened_checklists" :checklist_templates="checklist_templates" :project-can-write-ids="projectCanWriteIds" :project-manager-ids="projectManagerIds"></ChecklistTab>
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
import ChecklistTab from "@/Pages/Projects/Components/TabComponents/ChecklistTab.vue";

export default {
    components: {
        ChecklistTab,
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
        'RoomsWithAudience',
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
        'opened_checklists',
        'checklist_templates',

    ],
    data() {
        return {
            show: false,
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
