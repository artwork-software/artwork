<template>
    <AppLayout>

        <BudgetComponent :budget="budget" :project="project"
                         :money-sources="moneySources"></BudgetComponent>
        <pre>
        {{ budget }}
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
