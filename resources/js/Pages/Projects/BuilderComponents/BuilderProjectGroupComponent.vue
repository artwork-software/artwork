<template>
    <div class="min-w-0">
        <div
            v-if="groupNames"
            v-tooltip.top="{ value: groupNames, appendTo: 'body', class: 'aw-tooltip' }"
            class="flex items-center gap-1.5 text-sm text-text min-w-0"
        >
            <component :is="IconFolder" class="size-4 shrink-0 text-text-muted" aria-hidden="true" />
            <span class="truncate">{{ groupNames }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { IconFolder } from '@tabler/icons-vue'

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

// Projektübersicht liefert die Gruppen, zu denen das Projekt gehört, als project.group ([{ id, name }])
const groupNames = computed(() => (props.project?.group ?? [])
    .map((group) => group?.name)
    .filter(Boolean)
    .join(', '))
</script>
