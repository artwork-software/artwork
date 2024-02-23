<template>
    <div :class="hideProjectHeader ? 'px-5' : 'mt-6 px-5  bg-lightBackgroundGray'">
        <div class="flex bg-lightBackgroundGray w-[95%]">
            <BudgetComponent :sage-not-assigned="sageNotAssigned"
                             :hide-project-header="hideProjectHeader"
                             :table="budget.table"
                             :columnCalculatedNames="budget.columnCalculatedNames"
                             :project="project"
                             :selectedCell="budget.selectedCell"
                             :selectedRow="budget.selectedRow"
                             :templates="budget.templates"
                             :selected-sum-detail="budget.selectedSumDetail"
                             :money-sources="moneySources"
                             :budget-access="projectWriteIds"
                             :project-manager="projectManagerIds"
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

export default{
    components: {
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
        'hideProjectHeader',
        'sageNotAssigned'
    ],
    emits: ['changeProjectHeaderVisualisation'],
    methods: {
        changeProjectHeaderVisualisation(boolean) {
            this.$emit('changeProjectHeaderVisualisation',boolean);
        },
    },
}
</script>
