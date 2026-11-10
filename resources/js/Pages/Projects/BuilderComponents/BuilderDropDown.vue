<template>
    <div class="text-xs/[18px] text-text-subtle">
        <div v-if="component.data?.label" class="text-sm font-bold text-text mb-0.5">{{ component.data.label }}</div>
        <p class="line-clamp-3 whitespace-pre-line">{{ displayText }}</p>
    </div>
</template>
<script setup>
import { computed } from 'vue'
import { selectedDisplayText } from '@/Helper/ComponentSelection.js'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: true,
    },
})

// Auch für die Mehrfachauswahl (CheckboxList) genutzt: Werte liegen unter dem eigenen Typ, Listen kommagetrennt
const displayText = computed(() => {
    const entry = props.project?.[props.component.type]?.[props.component.component_id]
    return selectedDisplayText(entry?.data?.selected ?? entry?.selected)
})
</script>
<style scoped>
</style>
