<template>
    <div class="text-xs/[18px] text-text-subtle">
        <div v-if="component.data?.title" class="text-sm font-bold text-text mb-0.5">{{ component.data.title }}</div>
        <p v-if="!entries.length">–</p>
        <ul v-else class="space-y-0.5">
            <li v-for="entry in visibleEntries" :key="entry.id" class="truncate">
                <span class="text-text">{{ entry.display_name }}</span>
                <span v-if="entry.is_artist || entry.roles.length" class="text-text-subtle">
                    · {{ [entry.is_artist ? $t('Artist') : null, ...entry.roles].filter(Boolean).join(', ') }}
                </span>
            </li>
            <li v-if="entries.length > visibleEntries.length" class="text-text-subtle">
                {{ $t('+{count} more', { count: entries.length - visibleEntries.length }) }}
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue'

// Spalte „CRM-Kontaktliste“ der Projektübersicht: Namen mit Rollen (ProjectController::overviewCrmListEntries)
const props = defineProps({
    project: { type: Object, required: true },
    component: { type: Object, required: true },
})

const MAX_VISIBLE = 4
const entries = computed(() => props.project?.crm_contact_lists?.[props.component.component_id] ?? [])
const visibleEntries = computed(() => entries.value.slice(0, MAX_VISIBLE))
</script>
