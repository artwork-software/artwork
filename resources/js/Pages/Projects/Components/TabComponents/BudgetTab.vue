<template>
    <div class="mt-6 p-5  bg-lightBackgroundGray">
        <div class="flex bg-lightBackgroundGray w-[95%]">
            <BudgetComponent :table="budget.table" :project="project" :selectedCell="budget.selectedCell"
                             :selectedRow="budget.selectedRow" :templates="budget.templates"
                             :selected-sum-detail="budget.selectedSumDetail"
                             :money-sources="moneySources" :budget-access="access_budget"
                             :project-manager="projectManagerIds"></BudgetComponent>
        </div>
    </div>

</template>

<script>


import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";

export default{
    components: {BudgetComponent, PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError},
    props: [
        'project',
        'budget',
        'moneySources',
    ],
    data() {

    },
    methods:{
        access_budget: function () {
            let access_budget = [];
            this.project.access_budget.forEach(admin => {
                    access_budget.push(admin.id)
                }
            )
            return access_budget;
        },
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        },
    }
}
</script>

<style scoped>

</style>
