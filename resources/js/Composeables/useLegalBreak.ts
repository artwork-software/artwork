// composable/useLegalBreak.ts
import { computed, type Ref } from 'vue'

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

const ARBZG_RULES: BreakRule[] = [
    { minMinutesExclusive: 6 * 60, maxMinutesInclusive: 9 * 60, breakMinutes: 30 },
    { minMinutesExclusive: 9 * 60, breakMinutes: 45 },
]

function parseHHMM(val: string | null | undefined): number | null {
    if (!val) return null
    const m = String(val).trim().match(/^(\d{1,2}):([0-5]\d)$/)
    if (!m) return null
    const h = +m[1], mm = +m[2]
    if (h > 47) return null
    return h * 60 + mm
}

function roundTo(v: number, step: number) {
    if (step <= 1) return v
    return Math.round(v / step) * step
}

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

    const startMin = computed(() => parseHHMM(startRef.value))
    const endMin = computed(() => parseHHMM(endRef.value))

    const workMinutes = computed(() => {
        if (startMin.value == null || endMin.value == null) return 0
        let diff = endMin.value - startMin.value
        if (diff < 0 && allowCrossMidnight) diff += 24 * 60
        return diff > 0 ? roundTo(diff, roundToMinutes) : 0
    })

    const breakMinutes = computed(() => {
        const wm = workMinutes.value
        if (wm <= 0) return 0
        for (const rule of rules) {
            const lo = rule.minMinutesExclusive ?? -Infinity
            const hi = rule.maxMinutesInclusive ?? Infinity
            if (wm > lo && wm <= hi) return rule.breakMinutes
        }
        return 0
    })

    const infoText =
        'Diese Zeit wird bei der Arbeitszeitberechnung von der geleisteten Arbeitszeit abgezogen.'

    return { breakMinutes, workMinutes, infoText }
}
