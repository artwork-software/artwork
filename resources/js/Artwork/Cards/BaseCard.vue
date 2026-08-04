<template>
    <div class="bg-surface border border-border-subtle rounded-lg" :class="[elevationClass, paddingClass]">
        <slot></slot>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    /**
     * Elevation der Karte:
     * - flat (Default): nur Rahmen
     * - raised: dezenter Schatten (shadow-raised)
     * - overlay: Popover-Schatten (shadow-overlay)
     */
    elevation: {
        type: String,
        default: 'flat',
        validator: (value) => ['flat', 'raised', 'overlay'].includes(value),
    },
    /**
     * Innenabstand in px: 12 (p-3) oder 16 (p-4).
     * Default 0 = kein Innenabstand (Bestandsverhalten, Layouts verschieben sich nicht).
     */
    padding: {
        type: [String, Number],
        default: 0,
    },
})

const elevationClass = computed(() => {
    switch (props.elevation) {
        case 'raised':
            return 'shadow-raised'
        case 'overlay':
            return 'shadow-overlay'
        default:
            return ''
    }
})

const paddingClass = computed(() => {
    const value = Number(props.padding)
    if (value === 12) return 'p-3'
    if (value === 16) return 'p-4'
    return ''
})
</script>

<style scoped>
</style>
