<template>
    <div v-if="variant === 'line' && lines > 1" class="space-y-2" aria-hidden="true">
        <div
            v-for="index in lines"
            :key="index"
            class="animate-pulse rounded-md bg-surface-sunken"
            :class="[sizeClasses, index === lines ? 'w-2/3' : '']"
            :style="sizeStyles"
        />
    </div>
    <div
        v-else
        class="animate-pulse bg-surface-sunken"
        :class="[variant === 'circle' ? 'rounded-full' : 'rounded-md', sizeClasses]"
        :style="sizeStyles"
        aria-hidden="true"
    />
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ name: 'BaseSkeleton' })

const props = defineProps({
    /** Form des Platzhalters */
    variant: {
        type: String,
        default: 'line',
        validator: (value) => ['line', 'block', 'circle'].includes(value),
    },
    /** Breite als Tailwind-Klasse (z. B. 'w-40') oder CSS-Länge (z. B. '160px') */
    width: {
        type: String,
        default: '',
    },
    /** Höhe als Tailwind-Klasse (z. B. 'h-4') oder CSS-Länge (z. B. '16px') */
    height: {
        type: String,
        default: '',
    },
    /** Anzahl Zeilen — nur bei variant 'line' */
    lines: {
        type: Number,
        default: 1,
    },
})

/** CSS-Längen (z. B. '160px', '50%', '4rem') werden als Inline-Style gesetzt, alles andere als Klasse */
const isCssLength = (value) => /^[\d.]+(px|rem|em|%|vh|vw|ch)$/.test(value)

const DEFAULT_WIDTH = {
    line: 'w-full',
    block: 'w-full',
    circle: 'w-10',
}

const DEFAULT_HEIGHT = {
    line: 'h-3',
    block: 'h-24',
    circle: 'h-10',
}

const sizeClasses = computed(() => {
    const classes = []
    if (props.width && !isCssLength(props.width)) classes.push(props.width)
    else if (!props.width) classes.push(DEFAULT_WIDTH[props.variant] ?? DEFAULT_WIDTH.line)
    if (props.height && !isCssLength(props.height)) classes.push(props.height)
    else if (!props.height) classes.push(DEFAULT_HEIGHT[props.variant] ?? DEFAULT_HEIGHT.line)
    return classes
})

const sizeStyles = computed(() => {
    const styles = {}
    if (props.width && isCssLength(props.width)) styles.width = props.width
    if (props.height && isCssLength(props.height)) styles.height = props.height
    return styles
})
</script>
