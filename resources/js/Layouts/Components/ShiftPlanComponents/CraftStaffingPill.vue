<template>
    <button
        v-if="count"
        type="button"
        class="inline-flex items-center gap-x-1 rounded-full px-2 py-0.5 font-lexend text-[10px] font-semibold tabular-nums transition-colors duration-150 hover:ring-1 hover:ring-[var(--uo-hover-ring)]"
        :class="[levelClass, active ? 'ring-1 ring-[var(--uo-active-ring)]' : '']"
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

// Ampelfarben je Farbwelt des Personenbereichs (--uo-staff-*, app.css: dunkel = Ist, hell = Statusflächen)
const LEVEL_CLASS = {
    none: 'bg-[var(--uo-staff-none-bg)] text-[var(--uo-staff-none-text)]',
    low: 'bg-[var(--uo-staff-low-bg)] text-[var(--uo-staff-low-text)]',
    partial: 'bg-[var(--uo-staff-partial-bg)] text-[var(--uo-staff-partial-text)]',
    full: 'bg-[var(--uo-staff-full-bg)] text-[var(--uo-staff-full-text)]',
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
