<template>
    <div v-if="requirements.length" class="flex flex-wrap gap-1.5">
        <component
            :is="chip.clickable ? 'button' : 'span'"
            v-for="chip in requirements"
            :key="chip.type + chip.value"
            :type="chip.clickable ? 'button' : undefined"
            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
            :class="chipClass(chip)"
            :title="chip.clickable ? $t('Jump to permission') : undefined"
            @click="chip.clickable ? $emit('jump', chip.value) : null"
        >
            <span class="size-1.5 rounded-full" :class="dotClass(chip)"></span>
            <span>{{ $t(chipLabel(chip)) }}</span>
        </component>
    </div>
</template>

<script setup>
/**
 * Voraussetzungs-Chips eines Rechts: grün = erfüllt, gelb = fehlt, gestrichelt = nicht prüfbar
 * (projektabhängig). Rechte-Chips sind klickbar und springen zur Zeile.
 */
const props = defineProps({
    /** Ergebnis von usePermissionCatalog().requirementsFor(def) */
    requirements: { type: Array, default: () => [] },
    /** name => Titel, für Rechte-Chips */
    titles: { type: Object, default: () => ({}) },
})
defineEmits(['jump'])

const chipLabel = (chip) => (chip.type === 'permission' ? (props.titles[chip.value] ?? chip.value) : chip.label)

const chipClass = (chip) => {
    if (chip.type === 'project_team' || chip.status === 'unknown') {
        return 'border-dashed border-border text-text-muted bg-transparent'
    }
    if (chip.status === 'ok') return 'border-success-border text-success bg-success-surface'
    return 'border-warning-border text-warning bg-warning-surface'
}
const dotClass = (chip) => {
    if (chip.type === 'project_team' || chip.status === 'unknown') return 'bg-text-subtle'
    return chip.status === 'ok' ? 'bg-success' : 'bg-warning'
}
</script>
