<template>
    <div
        v-if="hasTimes && current !== null"
        class="mt-1 flex items-center gap-1 text-[11px] leading-snug"
        :class="tone"
        aria-live="polite"
    >
        <span v-if="!deviates">
            {{ $t('Break automatically filled according to the Working Hours Act ({0} min)', [legalMinutes]) }}
        </span>
        <template v-else>
            <span>{{ $t('Manual ({0} min, legally {1} min)', [current, legalMinutes]) }}</span>
            <ToolTipComponent
                icon="IconArrowBackUp"
                icon-size="h-3.5 w-3.5"
                direction="top"
                stroke="1.5"
                classes-button="rounded p-0.5 hover:bg-surface-sunken transition-colors"
                :tooltip-text="$t('Reset to legal minimum')"
                @click="$emit('reset')"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { toBreakNumber } from '@/Composeables/useAutoBreak'

/**
 * Hinweis unter einem Pausenfeld: zeigt, ob der Wert automatisch nach ArbZG
 * gesetzt wurde oder manuell abweicht (mit Zurücksetzen-Button); Unterschreitung
 * des Minimums wird in Warnfarbe dargestellt.
 */
const props = defineProps<{
    breakMinutes: number | string | null | undefined
    legalMinutes: number
    hasTimes: boolean
}>()

defineEmits<{ (e: 'reset'): void }>()

const current = computed(() => toBreakNumber(props.breakMinutes))
const deviates = computed(() => current.value !== null && current.value !== props.legalMinutes)
const below = computed(() => current.value !== null && current.value < props.legalMinutes)
const tone = computed(() => (below.value ? 'text-warning' : 'text-text-subtle'))
</script>
