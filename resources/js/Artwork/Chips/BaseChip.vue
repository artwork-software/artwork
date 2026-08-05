<template>
    <span
        class="inline-flex items-center gap-1 rounded-full font-medium"
        :class="[appearanceClasses, variantClasses]"
    >
        <slot />
        <span v-if="count !== undefined && count !== null" class="tabular-nums" :class="countClass">
            {{ count }}
        </span>
    </span>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ name: 'BaseChip' })

const props = defineProps({
    /** Farbvariante des Chips */
    variant: {
        type: String,
        default: 'neutral',
        validator: (value) => ['neutral', 'accent', 'success', 'warning', 'danger'].includes(value),
    },
    /** v2: 'soft' (Filter, helle Fläche) oder 'filled' (Status: weiß auf satter Farbe, 20px) */
    appearance: {
        type: String,
        default: 'soft',
        validator: (value) => ['soft', 'filled'].includes(value),
    },
    /** Optionaler Zähler, wird rechts vom Inhalt angezeigt */
    count: {
        type: [Number, String],
        default: undefined,
    },
})

const SOFT = {
    neutral: 'bg-surface-sunken text-text-muted border border-border-subtle',
    accent: 'bg-accent-50 text-accent-700 border border-accent-200',
    success: 'bg-success-surface text-success border border-success-border',
    warning: 'bg-warning-surface text-warning border border-warning-border',
    danger: 'bg-danger-surface text-danger border border-danger-border',
}

const FILLED = {
    neutral: 'bg-text-subtle text-white',
    accent: 'bg-accent-600 text-white',
    success: 'bg-success text-white',
    warning: 'bg-warning text-white',
    danger: 'bg-danger text-white',
}

const SOFT_COUNT = {
    neutral: 'text-text-subtle',
    accent: 'text-accent-600',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
}

const appearanceClasses = computed(() =>
    props.appearance === 'filled'
        ? 'h-5 px-2 text-[11px]'
        : 'h-[22px] px-2 text-[11.5px]'
)
const variantClasses = computed(() =>
    props.appearance === 'filled'
        ? (FILLED[props.variant] ?? FILLED.neutral)
        : (SOFT[props.variant] ?? SOFT.neutral)
)
const countClass = computed(() =>
    props.appearance === 'filled'
        ? 'text-white/80'
        : (SOFT_COUNT[props.variant] ?? SOFT_COUNT.neutral)
)
</script>
