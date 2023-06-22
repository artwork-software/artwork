<template>
    <app-layout>
        <ProjectShowHeaderComponent :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup"
                                    :states="states" :project-groups="projectGroups"
                                    :first-event-in-project="firstEventInProject"
                                    :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience"
                                    :group-projects="groupProjects" open-tab="budget">
            <BudgetTab :project="project" :budget="budget" :money-sources="moneySources"></BudgetTab>
        </ProjectShowHeaderComponent>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSidenav
                :project="project"
                :cost-center="project.cost_center"
                :copyright="project.copyright"
                :project-files="project.project_files"
                :contracts="project.contracts"
                :money-sources="projectMoneySources"
                :budget-access="access_budget"
            />
        </BaseSidenav>
    </app-layout>
</template>


<script>

import {defineComponent} from "vue";
import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ShiftTab from "@/Pages/Projects/Components/TabComponents/ShiftTab.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetTab from "@/Pages/Projects/Components/TabComponents/BudgetTab.vue";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav.vue";

export default defineComponent({
    components: {
        ProjectSidenav,
        BudgetTab, AppLayout, ProjectShiftSidenav, ShiftTab, BaseSidenav, ProjectShowHeaderComponent
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
        'budget',
        'moneySources',
        'projectMoneySources',
        'access_budget',
        'groupProjects'
    ],
    data() {
        return {
            show: false,
        }
    },
})
</script>

<style scoped>

</style>
