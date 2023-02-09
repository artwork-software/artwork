<template>

    <div class="flex flex-row mt-2">
        <span class="xxsLight">{{ budget.requested_position.name }}</span><span class="xxsLight ml-3"><a :href="route('projects.show', budget.project.id) + '?openTab=budget'" class="text-indigo-700">{{ budget.project.name }}</a></span>
    </div>
    <div class="mt-2 flex" v-if="budget.changeType === 'BUDGET_VERIFICATION_REQUEST'">
        <AddButton
            @click="redirectToBudget"
            class="flex"
            text="Kalkulation prüfen" mode="modal"/>
        <AddButton
            type="secondary"
            text="Anfrage löschen"></AddButton>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";

export default {
    name: "NotificationBudgetRequest",
    components: {AddButton, Button, XIcon, Link},
    props: ['budget'],
    methods: {
        redirectToBudget(){
            location.replace(route('projects.show', this.budget.requested_position.project_id) + '?openTab=budget')
        }
    }
}
</script>

<style scoped>

</style>
