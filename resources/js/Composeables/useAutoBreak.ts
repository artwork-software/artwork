// composable/useAutoBreak.ts
import { computed, ref, watch, type Ref } from 'vue'
import { useLegalBreak, parseHHMM, type UseLegalBreakOptions } from '@/Composeables/useLegalBreak'
import { resolveAutoBreakValue, toBreakNumber } from '@/Helper/LegalBreak.js'

export type BreakValue = number | string | null | undefined

export type UseAutoBreakOptions = UseLegalBreakOptions & {
    /** Optional: Auto-Befüllung nur, solange true (z.B. nicht bei "ganztägig"). */
    enabled?: Ref<boolean>
}

export { toBreakNumber }

/**
 * Automatische Pausen-Befüllung nach ArbZG für ein Pausenfeld.
 *
 * Regeln:
 * - Leeres Feld wird mit der gesetzlichen Mindestpause befüllt.
 * - Liegt der Wert unter dem Minimum und wurde er in dieser Sitzung nicht manuell
 *   geändert, wird er auf das Minimum angehoben.
 * - Automatisch gesetzte Werte folgen dem Minimum auch nach unten (Zeiten verkürzt).
 * - Manuell eingegebene Werte werden nie still überschrieben; dafür gibt es
 *   `resetToLegal()` und die Hinweis-Anzeige (isManual / isBelowMinimum).
 */
export function useAutoBreak(
    startRef: Ref<string | null | undefined>,
    endRef: Ref<string | null | undefined>,
    breakRef: Ref<BreakValue>,
    opts: UseAutoBreakOptions = {}
) {
    const { enabled, ...legalOpts } = opts
    const { breakMinutes: legalMinutes, workMinutes } = useLegalBreak(startRef, endRef, legalOpts)

    const manuallyEdited = ref(false)
    const lastAutoValue = ref<number | null>(null)

    const hasTimes = computed(() =>
        (enabled ? enabled.value : true)
        && parseHHMM(startRef.value) != null
        && parseHHMM(endRef.value) != null
        && workMinutes.value > 0
    )

    const currentBreak = computed(() => toBreakNumber(breakRef.value))
    const isEmpty = computed(() => currentBreak.value === null)

    /** Wert entspricht exakt dem gesetzlichen Minimum. */
    const isAuto = computed(() => hasTimes.value && !isEmpty.value && currentBreak.value === legalMinutes.value)
    /** Wert weicht vom gesetzlichen Minimum ab (nach oben oder unten). */
    const isManual = computed(() => hasTimes.value && !isEmpty.value && currentBreak.value !== legalMinutes.value)
    /** Wert unterschreitet das gesetzliche Minimum. */
    const isBelowMinimum = computed(() =>
        hasTimes.value && !isEmpty.value && (currentBreak.value as number) < legalMinutes.value
    )

    function setAuto(value: number) {
        breakRef.value = value
        lastAutoValue.value = value
        manuallyEdited.value = false
    }

    /** Vom Pausenfeld bei Nutzereingabe rufen (@input). */
    function markManual() {
        manuallyEdited.value = true
    }

    /** Pause auf das gesetzliche Minimum zurücksetzen. */
    function resetToLegal() {
        setAuto(legalMinutes.value)
    }

    /**
     * Programmatisches Setzen (z.B. Übernahme einer Vorlage): gilt nicht als manuell,
     * wird aber bei Unterschreitung des Minimums angehoben.
     */
    function applyExternalValue(value: BreakValue) {
        breakRef.value = value
        manuallyEdited.value = false
        lastAutoValue.value = null
        syncToLegal(legalMinutes.value)
    }

    function syncToLegal(legal: number) {
        if (!hasTimes.value) return

        const next = resolveAutoBreakValue({
            current: breakRef.value,
            legal,
            manuallyEdited: manuallyEdited.value,
            lastAutoValue: lastAutoValue.value,
        })

        if (next !== null) {
            setAuto(next)
        }
    }

    watch([legalMinutes, hasTimes], ([legal]) => syncToLegal(legal), { immediate: true })

    return {
        legalMinutes,
        workMinutes,
        hasTimes,
        currentBreak,
        manuallyEdited,
        isAuto,
        isManual,
        isBelowMinimum,
        markManual,
        resetToLegal,
        applyExternalValue,
    }
}
