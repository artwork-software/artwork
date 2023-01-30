<template>
    <AppLayout>


        <pre>
        {{ budget }}
    </pre>
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";

export default {
    name: "Index",
    components: {BudgetComponent, AppLayout},
    props: ['budget']
}
</script>

<style scoped>

</style>
