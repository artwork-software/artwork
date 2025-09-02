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

const variantMap = {
    neutral: {
        pill: 'bg-white text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700 shadow-sm',
        line: 'from-transparent via-zinc-300 to-transparent dark:via-zinc-700'
    },
    brand: {
        pill: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900',
        line: 'from-transparent via-zinc-300 to-transparent dark:via-zinc-700'
    },
    success: {
        pill: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900',
        line: 'from-transparent via-zinc-300 to-transparent dark:via-zinc-700'
    },
    warning: {
        pill: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900',
        line: 'from-transparent via-zinc-300 to-transparent dark:via-zinc-700'
    },
    danger: {
        pill: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900',
        line: 'from-transparent via-zinc-300 to-transparent dark:via-zinc-700'
    }
}

const pillBase = 'rounded-full font-medium whitespace-nowrap max-w-full truncate'
const pillClassComputed = computed(() => {
    if (props.pillClass) return props.pillClass
    const size = sizeMap[props.size] ?? sizeMap.md
    const variant = variantMap[props.variant] ?? variantMap.neutral
    return `${pillBase} ${size} ${variant.pill}`
})

const lineGradient = computed(() => {
    const variant = variantMap[props.variant] ?? variantMap.neutral
    return `h-px flex-1 bg-gradient-to-r ${variant.line}`
})

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
