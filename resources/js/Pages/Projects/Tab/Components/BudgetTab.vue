<template>
    <div :class="hideProjectHeader ? 'px-5' : 'mt-6 px-5  bg-lightBackgroundGray'">
        <div class="flex bg-lightBackgroundGray w-[95%]">
            <BudgetComponent :sage-not-assigned="sageNotAssigned ?? loadedProjectInformation['BudgetTab']?.sageNotAssigned"
                             :hide-project-header="hideProjectHeader "
                             :table="budget?.table ?? loadedProjectInformation['BudgetTab'].budget?.table"
                             :columnCalculatedNames="budget?.columnCalculatedNames ?? loadedProjectInformation['BudgetTab']?.budget.columnCalculatedNames"
                             :project="project ?? headerObject.project"
                             :selectedCell="budget?.selectedCell ?? loadedProjectInformation['BudgetTab']?.budget.selectedCell"
                             :selectedRow="budget?.selectedRow ?? loadedProjectInformation['BudgetTab']?.budget.selectedRow"
                             :templates="budget?.templates ?? loadedProjectInformation['BudgetTab']?.budget.templates"
                             :selected-sum-detail="budget?.selectedSumDetail ?? loadedProjectInformation['BudgetTab']?.budget.selectedSumDetail"
                             :money-sources="moneySources ?? loadedProjectInformation['BudgetTab']?.moneySources"
                             :budget-access="project.access_budget ?? headerObject?.access_budget"
                             :project-manager="project.managerUsers ?? headerObject?.managerUsers"
                             :first_project_budget_tab_id="this.first_project_budget_tab_id"
                             @changeProjectHeaderVisualisation="changeProjectHeaderVisualisation"
            />
        </div>
    </div>
</template>

<script>
import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import ProjectBudgetDeadlineComponent from "@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue";

export default{
    components: {
        ProjectBudgetDeadlineComponent,
        BudgetComponent,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
    },
    props: [
        'project',
        'budget',
        'moneySources',
        'projectWriteIds',
        'projectManagerIds',
        'sageNotAssigned',
        'loadedProjectInformation',
        'headerObject',
        'first_project_budget_tab_id'
    ],
    data(){
        return {
            hideProjectHeader: false,
        }
    },
    methods: {
        changeProjectHeaderVisualisation(boolean) {
            this.hideProjectHeader = boolean;
        },
    },
}
</script>
