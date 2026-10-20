<template>
    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t(label) }}</div>
        <div v-if="users.length || departments.length" class="flex flex-wrap items-center gap-2">
            <div v-for="department in departments" :key="`dep-${department.id}`"
                 class="inline-flex items-center gap-x-2 rounded-full border border-border-subtle bg-surface-sunken py-1 pl-1 pr-3">
                <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-6 w-6 rounded-full object-cover" />
                <span class="text-sm font-medium">{{ department.name }}</span>
                <span class="text-xs text-text-subtle">{{ $t('Members: {0}', [department.users_count ?? 0]) }}</span>
            </div>
            <div class="flex flex-wrap items-center -space-x-1">
                <UserPopoverTooltip v-for="user in users" :key="`user-${user.id}`" :user="user" :id="user.id" height="9" width="9" />
            </div>
        </div>
        <p v-else class="text-sm text-text-subtle">{{ $t(emptyText) }}</p>
    </div>
</template>

<script setup>
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'

defineProps({
    label: { type: String, required: true },
    emptyText: { type: String, required: true },
    users: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
})
</script>
