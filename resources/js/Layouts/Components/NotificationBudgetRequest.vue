<template>

    <div class="flex flex-row mt-2">
        <span class="xxsLight">{{ budget.requested_position.name }}</span><span class="xxsLight ml-3"><a :href="route('projects.show.budget', budget.project.id)" class="text-indigo-700">{{ budget.project.name }}</a></span>
    </div>
    <div class="mt-2 flex" v-if="budget.changeType === 'BUDGET_VERIFICATION_REQUEST'">
        <AddButton
            @click="redirectToBudget"
            class="flex"
            :text="$t('Check calculation')" mode="modal"/>
        <AddButton
            type="secondary"
            :text="$t('Delete request')"></AddButton>
    </div>
</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {XIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NotificationBudgetRequest",
    components: {AddButton, Button, XIcon, Link},
    props: ['budget'],
    methods: {
        redirectToBudget(){
            location.replace(route('projects.show.budget', this.budget.requested_position.project_id))
        }
    }
}
</script>

<style scoped>

</style>
