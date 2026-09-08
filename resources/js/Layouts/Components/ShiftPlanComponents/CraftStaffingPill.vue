<template>
    <button
        v-if="count"
        type="button"
        class="inline-flex items-center gap-x-1 rounded-full px-2 py-0.5 font-lexend text-[10px] font-semibold tabular-nums transition-colors duration-150 hover:ring-1 hover:ring-white/40"
        :class="[levelClass, active ? 'ring-1 ring-white/70' : '']"
        :title="title"
        :aria-pressed="active"
        @click.stop="$emit('toggle')"
    >
        {{ count.staffed }}/{{ count.required }}
    </button>
</template>

<script setup>
/**
 * Besetzungs-Pille „besetzt/Bedarf" einer Gewerks-Zelle im Dienstplan (Tag oder KW-Summe).
 *
 * Eigene Komponente, damit Ampelklasse und Tooltip-Text (mit $t) je Zelle nur einmal je
 * Prop-Änderung berechnet werden und nicht bei jedem Re-Render des gesamten Plans.
 */
import {computed} from 'vue'
import {useI18n} from 'vue-i18n'
import {staffingLevel, staffingOpen} from '@/Helper/shiftStaffing.js'

const props = defineProps({
    /** { required, staffed } oder null (kein Bedarf → keine Pille) */
    count: {type: Object, default: null},
    /** Klick-Filter (nur nicht voll besetzte + genau dieses Gewerk) aktiv */
    active: {type: Boolean, default: false},
    /** gesetzt in der KW-Spalte: Tooltip mit KW-Präfix */
    weekNumber: {type: [Number, String], default: null},
})

defineEmits(['toggle'])

const {t} = useI18n()

const LEVEL_CLASS = {
    none: 'bg-white/10 text-white/50',
    low: 'bg-danger/25 text-danger-surface',
    partial: 'bg-warning/25 text-warning-surface',
    full: 'bg-success/25 text-success-surface',
}

const levelClass = computed(() => LEVEL_CLASS[staffingLevel(props.count)])

const title = computed(() => {
    const count = props.count
    if (!count || count.required === 0) return t('No demand')
    const base = t('Demand {required} slots, staffed {staffed}, open {open}', {
        required: count.required,
        staffed: count.staffed,
        open: staffingOpen(count),
    })
    return props.weekNumber != null ? `KW ${props.weekNumber}: ${base}` : base
})
</script>
