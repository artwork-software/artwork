<template>
    <app-layout>
        <ProjectShowHeaderComponent v-if="!hideProjectHeader"
                                    :project-delete-ids="projectDeleteIds"
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
                                    open-tab="budget">
            <BudgetTab @changeProjectHeaderVisualisation="changeProjectHeaderVisualisation"
                       :hideProjectHeader="hideProjectHeader"
                       :projectWriteIds="projectWriteIds"
                       :projectManagerIds="projectManagerIds"
                       :project="project"
                       :budget="budget"
                       :money-sources="moneySources"
            />
        </ProjectShowHeaderComponent>
        <BudgetTab @changeProjectHeaderVisualisation="changeProjectHeaderVisualisation" :hideProjectHeader="hideProjectHeader" :projectWriteIds="projectWriteIds" :projectManagerIds="projectManagerIds" :project="project" :budget="budget" :money-sources="moneySources" v-else ></BudgetTab>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSidenav
                :project="project"
                :cost-center="project.cost_center"
                :copyright="project.copyright"
                :projectManagerIds="projectManagerIds"
                :project-files="project.project_files"
                :contracts="project.contracts"
                :money-sources="projectMoneySources"
                :access-budget="access_budget"
                :currencies="currencies"
                :contract-types="contractTypes"
                :company-types="companyTypes"
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
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";

export default defineComponent({
    components: {
        InfoTab,
        ProjectSidenav,
        BudgetTab,
        AppLayout,
        ProjectShiftSidenav,
        ShiftTab,
        BaseSidenav,
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
        'budget',
        'moneySources',
        'projectMoneySources',
        'access_budget',
        'groupProjects',
        'projectWriteIds',
        'projectManagerIds',
        'projectDeleteIds',
        'currencies',
        'contractTypes',
        'companyTypes'
    ],
    data() {
        return {
            show: false,
            hideProjectHeader: false
        }
    },
    methods: {
        changeProjectHeaderVisualisation(boolean) {
            this.hideProjectHeader = boolean;
        },
    },
})
</script>
