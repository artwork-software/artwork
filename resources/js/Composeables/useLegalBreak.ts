// composable/useLegalBreak.ts
import { computed, type Ref } from 'vue'
import {
    ARBZG_RULES,
    legalBreakMinutesFor,
    minimumBreakMinutes,
    parseHHMM,
    workMinutesBetween,
} from '@/Helper/LegalBreak.js'

export type BreakRule = {
    minMinutesExclusive?: number
    maxMinutesInclusive?: number
    breakMinutes: number
}

export type UseLegalBreakOptions = {
    rules?: BreakRule[]
    allowCrossMidnight?: boolean
    roundToMinutes?: number
}

/**
 * § 4 ArbZG: bis einschließlich 6:00 h keine Pause, über 6 h 30 min, über 9 h 45 min.
 * Die reine Logik liegt in resources/js/Helper/LegalBreak.js (node-testbar) und
 * spiegelt artwork/Modules/Shift/Services/LegalBreakCalculator.php.
 */
export { ARBZG_RULES, legalBreakMinutesFor, minimumBreakMinutes, parseHHMM, workMinutesBetween }

export function useLegalBreak(
    startRef: Ref<string | null | undefined>,
    endRef: Ref<string | null | undefined>,
    opts: UseLegalBreakOptions = {}
) {
    const {
        rules = ARBZG_RULES,
        allowCrossMidnight = true,
        roundToMinutes = 1,
    } = opts

    const workMinutes = computed<number>(() =>
        workMinutesBetween(startRef.value, endRef.value, { allowCrossMidnight, roundToMinutes })
    )

    const breakMinutes = computed<number>(() => minimumBreakMinutes(workMinutes.value, rules))

    const infoText =
        'Diese Zeit wird bei der Arbeitszeitberechnung von der geleisteten Arbeitszeit abgezogen.'

    return { breakMinutes, workMinutes, infoText }
}
