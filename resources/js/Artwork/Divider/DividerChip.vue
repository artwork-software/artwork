<!-- resources/js/Components/Shared/DividerChip.vue -->
<template>
    <div :class="containerClass">
        <!-- left line -->
        <div :class="leftLineClass"></div>

        <!-- pill -->
        <div :class="pillClassComputed">
            <slot>{{ label }}</slot>
        </div>

        <!-- right line -->
        <div :class="rightLineClass"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
defineOptions({ name: 'DividerChip' })

const props = defineProps({
    label:       { type: String, default: '' },

    /**
     * Modernisierte Varianten:
     * - neutral (Default), brand, success, warning, danger
     * Legacy-Farbwerte (emerald, amber, rose, blue) bleiben gültig und
     * werden intern auf die semantischen Varianten gemappt.
     */
    variant:     { type: String, default: 'neutral' },

    /**
     * Größen:
     * - sm, md (Default), lg
     */
    size:        { type: String, default: 'sm' },

    /**
     * Ausrichtung:
     * - center (Default), start  -> bei start entfällt die linke Linie
     */
    align:       { type: String, default: 'center' },

    /**
     * Manuelle Overrides (optional). Wenn gesetzt, überschreiben sie die modernen Defaults.
     */
    pillClass:   { type: String, default: '' },
    lineClass:   { type: String, default: '' },
})

const sizeMap = {
    sm: 'px-3 py-1 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base'
}

/** Legacy-Prop-Werte (Farbnamen) → semantische Varianten */
const variantAliases = {
    emerald: 'success',
    green: 'success',
    amber: 'warning',
    yellow: 'warning',
    rose: 'danger',
    red: 'danger',
    blue: 'brand',
    zinc: 'neutral',
    gray: 'neutral',
}

const variantMap = {
    neutral: {
        pill: 'bg-surface text-text-muted ring-1 ring-border shadow-raised',
        line: 'bg-border'
    },
    brand: {
        pill: 'bg-accent-50 text-accent-700 ring-1 ring-accent-200',
        line: 'bg-border'
    },
    success: {
        pill: 'bg-success-surface text-success ring-1 ring-success-border',
        line: 'from-transparent via-border to-transparent'
    },
    warning: {
        pill: 'bg-warning-surface text-warning ring-1 ring-warning-border',
        line: 'from-transparent via-border to-transparent'
    },
    danger: {
        pill: 'bg-danger-surface text-danger ring-1 ring-danger-border',
        line: 'from-transparent via-border to-transparent'
    }
}

const resolvedVariant = computed(() => {
    const name = variantAliases[props.variant] ?? props.variant
    return variantMap[name] ?? variantMap.neutral
})

const pillBase = 'rounded-full font-medium whitespace-nowrap max-w-full truncate'
const pillClassComputed = computed(() => {
    if (props.pillClass) return props.pillClass
    const size = sizeMap[props.size] ?? sizeMap.md
    return `${pillBase} ${size} ${resolvedVariant.value.pill}`
})

const lineGradient = computed(() =>
    `h-px flex-1 bg-gradient-to-r ${resolvedVariant.value.line}`
)

const containerClass = computed(() =>
    `relative flex items-center gap-3 select-none`
)

const leftLineClass = computed(() => {
    if (props.align === 'start') return 'w-0'
    return props.lineClass || lineGradient.value
})

const rightLineClass = computed(() =>
    props.lineClass || lineGradient.value
)
</script>
