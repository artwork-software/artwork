<template>
    <AppLayout>
        <BudgetComponent :table="budget.table[0]" :project="project" :selectedCell="budget.selectedCell" :selectedRow="budget.selectedRow" :templates="budget.templates"
                         :money-sources="moneySources"></BudgetComponent>

        <pre>
        {{ budget.table }}
    </pre>
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";

export default {
    name: "BudgetTemplateManagement",
    components: {BudgetComponent, AppLayout},
    props: ['budget']
}
</script>

<style scoped>

</style>
