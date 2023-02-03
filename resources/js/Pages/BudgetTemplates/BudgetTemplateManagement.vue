<template>
    <AppLayout>
        <div v-for="table in budget.table" class="flex my-6 border-2 border-gray-300">
            <button class="bg-buttonBlue w-6"
                    @click="table.closed = !table.closed">
                <ChevronUpIcon v-if="table.closed"
                               class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                <ChevronDownIcon v-else
                                 class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
            </button>
            <div class="ml-4 my-4">
            <BudgetComponent v-if="!table.closed" :table="table" :project="project" :selectedCell="budget.selectedCell" :selectedRow="budget.selectedRow" :templates="budget.templates"
                             :money-sources="moneySources"></BudgetComponent>
            <div v-else>
                <div class="headline2 ">
                    {{table.name}}
                </div>

            </div>
            </div>
        </div>

        <pre>
        {{ budget.table }}
    </pre>
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import {ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/solid";


export default {
    name: "BudgetTemplateManagement",
    components: {BudgetComponent, AppLayout, ChevronUpIcon, ChevronDownIcon},
    props: ['budget']
}
</script>

<style scoped>

</style>
