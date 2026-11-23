<template>
    <TrashTab
        :paginator="trashedAccounts"
        property-name="trashedAccounts"
        :force-all-href="route('budget-settings.account-management.trash-accounts.forceDeleteAll')"
        :restore-href="(account) => route('budget-settings.account-management.trash-accounts.restore', { budgetManagementAccount: account.id })"
        :force-href="(account) => route('budget-settings.account-management.trash-accounts.forceDelete', { budgetManagementAccount: account.id })"
        :name-of="(account) => String(account.account_number ?? '')"
        :open-href="() => route('budget-settings.account-management')"
    >
        <template #default="{ item: account }">
            <div class="truncate font-medium text-text tabular-nums">{{ account.account_number }}</div>
            <div v-if="account.title" class="mt-0.5 truncate text-sm text-text-muted">{{ account.title }}</div>
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
    trashedAccounts: { type: Object, required: true },
})
</script>
