<template>
    <TrashTab
        :paginator="trashedCostUnits"
        property-name="trashedCostUnits"
        :force-all-href="route('budget-settings.account-management.trash-cost-units.forceDeleteAll')"
        :restore-href="(costUnit) => route('budget-settings.account-management.trash-cost-units.restore', { budgetManagementCostUnit: costUnit.id })"
        :force-href="(costUnit) => route('budget-settings.account-management.trash-cost-units.forceDelete', { budgetManagementCostUnit: costUnit.id })"
        :name-of="(costUnit) => String(costUnit.cost_unit_number ?? '')"
        :open-href="() => route('budget-settings.account-management')"
    >
        <template #default="{ item: costUnit }">
            <div class="truncate font-medium text-text tabular-nums">{{ costUnit.cost_unit_number }}</div>
            <div v-if="costUnit.title" class="mt-0.5 truncate text-sm text-text-muted">{{ costUnit.title }}</div>
        </template>
    </TrashTab>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import TrashLayout from '@/Layouts/TrashLayout.vue'

export default {
    layout: [AppLayout, TrashLayout],
}
</script>

<script setup>
import TrashTab from '@/Pages/Trash/Components/TrashTab.vue'
defineProps({
    trashedCostUnits: { type: Object, required: true },
})
</script>
