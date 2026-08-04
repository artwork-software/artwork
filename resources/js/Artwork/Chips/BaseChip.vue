<template>
    <span
        class="inline-flex h-[22px] items-center gap-1 rounded-full border px-2 text-[11.5px] font-medium"
        :class="variantClasses"
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
    /** Optionaler Zähler, wird rechts vom Inhalt angezeigt */
    count: {
        type: [Number, String],
        default: undefined,
    },
})

const VARIANTS = {
    neutral: 'bg-surface-sunken text-text-muted border-border-subtle',
    accent: 'bg-accent-50 text-accent-700 border-accent-200',
    success: 'bg-success-surface text-success border-success-border',
    warning: 'bg-warning-surface text-warning border-warning-border',
    danger: 'bg-danger-surface text-danger border-danger-border',
}

const COUNT_CLASSES = {
    neutral: 'text-text-subtle',
    accent: 'text-accent-600',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
}

const variantClasses = computed(() => VARIANTS[props.variant] ?? VARIANTS.neutral)
const countClass = computed(() => COUNT_CLASSES[props.variant] ?? COUNT_CLASSES.neutral)
</script>
