<template>
    <span class="flex items-center gap-1.5">
        {{ $t('Budget deadline') }}: {{ budgetDeadline }}
        <ToolTipComponent
            :tooltip-text="$t('This date is currently only relevant for the budget export by deadline in the project overview, to determine the point in time for which the budget is relevant.')"
            direction="right"
            icon="IconInfoCircle"
            icon-size="h-4 w-4"
        />
    </span>
</template>

<script>
import {defineComponent} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

export default defineComponent({
    components: {ToolTipComponent},
    props: [
        'project',
        'headerObject'
    ],
    computed: {
        budgetDeadline() {
            if (this.project){
                return this.project.budget_deadline ?? this.$t('No budget deadline specified');
            } else if(this.headerObject){
                return this.headerObject.project.budget_deadline ?? this.$t('No budget deadline specified');
            }
        }
    }
});
</script>
