<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2 flex items-center gap-1.5">
            {{ $t('Budget deadline') }}
            <ToolTipComponent
                :tooltip-text="$t('This date is currently only relevant for the budget export by deadline in the project overview, to determine the point in time for which the budget is relevant.')"
                direction="right"
                icon="IconInfoCircle"
                icon-size="h-3.5 w-3.5"
            />
        </h3>
        <div class="flex items-center gap-x-1 text-sm" v-if="project.budget_deadline">
            <component :is="IconClock" class="h-4 w-4" stroke-width="2"/>
            <span class="font-bold">{{ project.budget_deadline }}</span>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
import {IconClock} from "@tabler/icons-vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: false,
    },
})
</script>
<style scoped>
</style>
