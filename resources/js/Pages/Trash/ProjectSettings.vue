<template>
    <TrashTab
        :entries="entries"
        :searchable="false"
        :force-all-href="route('projects.settings.force.all')"
        :restore-href="(entry) => route(`${entry.model}.restore`, entry.id)"
        :force-href="(entry) => route(`${entry.model}.force`, entry.id)"
        :name-of="(entry) => entry.name"
        :key-of="(entry) => `${entry.model}-${entry.id}`"
        :open-href="() => route('project.settings')"
    >
        <template #default="{ item: entry }">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="truncate font-medium text-text">{{ entry.name }}</span>
                <span class="inline-flex items-center rounded-full border border-border-subtle px-2 py-0.5 text-[11px] font-medium text-text-subtle">
                    {{ $t(entry.type) }}
                </span>
            </div>
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
import { computed } from 'vue'

const props = defineProps({
    trashed_genres: { type: Array, default: () => [] },
    trashed_categories: { type: Array, default: () => [] },
    trashed_sectors: { type: Array, default: () => [] },
    trashed_project_states: { type: Array, default: () => [] },
    trashed_contract_types: { type: Array, default: () => [] },
    trashed_company_types: { type: Array, default: () => [] },
    trashed_currencies: { type: Array, default: () => [] },
})

// Die Routen heißen je Modell anders ({genre}, {state}, {id}); die ID wird deshalb ohne Parameternamen übergeben
const GROUPS = [
    ['trashed_genres', 'genres', 'Project category - Genre'],
    ['trashed_categories', 'categories', 'Project category - Category'],
    ['trashed_sectors', 'sectors', 'Project category - Area'],
    ['trashed_project_states', 'state', 'Project category - Project status'],
    ['trashed_contract_types', 'contract_types', 'Project category - Contract type'],
    ['trashed_company_types', 'company_types', 'Project contracts - corporate form'],
    ['trashed_currencies', 'currencies', 'Project contracts - Currency'],
]

const entries = computed(() => GROUPS.flatMap(([prop, model, type]) =>
    (props[prop] ?? []).map((item) => ({ ...item, model, type }))))
</script>
