<template>
    <div class="flex flex-row mt-2">
        <span class="text-xs/[18px] text-text-subtle">{{ budget.requested_position.name }}</span>
        <span class="text-xs/[18px] text-text-subtle ml-3">
            <a :href="route('projects.tab', {project: this.budget.project.id, projectTab: this.first_project_budget_tab_id})" class="text-accent-700">
                {{ budget.project.name }}
            </a>
        </span>
    </div>
    <div class="mt-2 flex" v-if="budget.changeType === 'BUDGET_VERIFICATION_REQUEST'">
        <FormButton
            @click="redirectToBudget"
            :text="$t('Check calculation')"/>
        <FormButton
            :text="$t('Delete request')"></FormButton>
    </div>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    mixins: [Permissions],
    name: "NotificationBudgetRequest",
    components: {FormButton, Button, IconX, Link},
    props: [
        'budget',
        'first_project_budget_tab_id'
    ],
    methods: {
        redirectToBudget(){
            location.replace(
                route(
                    'projects.tab',
                    {
                        project: this.budget.requested_position.project_id,
                        projectTab: this.first_project_budget_tab_id
                    }
                )
            );
        }
    }
}
</script>

<style scoped>

</style>
