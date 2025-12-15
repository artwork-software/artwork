// /composables/useSyncedHorizontalScroll.ts
import { ref, type Ref } from 'vue'

export type DayLike = {
    fullDay?: string
    weekNumber?: number
    isExtraRow?: boolean
    isMonday?: boolean
}

type Options = {
    /** fester Linksoffset, an dem der Raumname 'fix' steht – original: +200px */
    roomNameOffsetPx?: number
    /** Ermittelt die Element-ID für einen Tag (Default wie im bestehenden Code) */
    getDayElementId?: (day: DayLike) => string | null
}

/**
 * Synchronisiert horizontale Scrolls zwischen zwei Containern und
 * bestimmt den 'aktuellen Tag' im Viewport anhand deines Algorithmus.
 */
export function useSyncedHorizontalScroll(
    shiftPlanEl: Ref<HTMLElement | null>,
    userOverviewEl: Ref<HTMLElement | null>,
    days: Ref<DayLike[]>,
    currentDayOnView: Ref<DayLike | null>,
    options: Options = {}
) {
    const roomNameOffsetPx = options.roomNameOffsetPx ?? 200
    const getDayElementId =
        options.getDayElementId ??
        ((d: DayLike) => (d.fullDay ? d.fullDay : d.weekNumber != null ? `extra_row_${d.weekNumber}` : null))

    /** Loop-Guard, damit A->B nicht wieder B->A triggert usw. */
    const syncingFrom = ref<'shift' | 'overview' | null>(null)

    /** rAF für "release guard" */
    let releaseRafId: number | null = null

    /** rAF für "current day"-Berechnung (throttled) */
    let dayScanRafId: number | null = null

    /** Cache für Day-Elemente (vermeidet document.getElementById auf jedem Scroll-Event) */
    const dayElCache = new Map<string, HTMLElement>()

    function getDayEl(id: string): HTMLElement | null {
        const cached = dayElCache.get(id)
        // Wenn Element aus dem DOM geflogen ist (Re-Render), neu holen
        if (cached && cached.isConnected) return cached

        const el = document.getElementById(id)
        if (el && el instanceof HTMLElement) {
            dayElCache.set(id, el)
            return el
        }

        dayElCache.delete(id)
        return null
    }

    function _syncScroll(source: 'shift' | 'overview', targetEl: HTMLElement, left: number) {
        // Guard: nicht zurückspielen, wenn wir gerade vom Ziel kommen
        if (syncingFrom.value && syncingFrom.value !== source) return

        // unnötige Writes vermeiden (reduziert Layout/Scroll-Events)
        if (targetEl.scrollLeft === left) return

        syncingFrom.value = source
        targetEl.scrollLeft = left

        // nach dem nächsten Frame wieder freigeben
        if (releaseRafId) cancelAnimationFrame(releaseRafId)
        releaseRafId = requestAnimationFrame(() => {
            syncingFrom.value = null
            releaseRafId = null
        })
    }

    function syncScrollUserOverview(e: Event) {
        const src = e.target
        if (!(src instanceof HTMLElement)) return

        const target = shiftPlanEl.value
        if (!target) return

        _syncScroll('overview', target, src.scrollLeft)
        // Hinweis: currentDayOnView wird weiterhin über shiftPlan-scroll event aktualisiert
        // (wie in deiner Original-Logik), weil shiftPlan durch _syncScroll scrollt.
    }

    function scanCurrentDay() {
        dayScanRafId = null

        const container = shiftPlanEl.value
        const list = days.value
        if (!container || !list?.length) return

        const roomNameFixedPosition = container.getBoundingClientRect().left + roomNameOffsetPx

        let closestDayIndex: number | null = null
        let closestDayDistance = Infinity

        for (let i = 0; i < list.length; i++) {
            const day = list[i]
            const id = getDayElementId(day)
            if (!id) continue

            const dayElement = getDayEl(id)
            if (!dayElement) continue

            const rect = dayElement.getBoundingClientRect()
            // wenn Element nicht sichtbar/0-breit ist, skip (verhindert komische Sprünge)
            if (rect.width <= 0) continue

            const elementCenter = rect.left + rect.width / 2
            const distance = Math.abs(roomNameFixedPosition - elementCenter)

            if (distance < closestDayDistance) {
                closestDayDistance = distance
                closestDayIndex = i
            }
        }

        if (closestDayIndex === null) return

        const selectedDay = list[closestDayIndex]
        if (!selectedDay) return

        if (selectedDay.isExtraRow) {
            for (let j = closestDayIndex + 1; j < list.length; j++) {
                if (list[j]?.isMonday) {
                    currentDayOnView.value = list[j]
                    return
                }
            }
            // fallback: wenn keine Monday gefunden, setze wenigstens selectedDay
            currentDayOnView.value = selectedDay
            return
        }

        currentDayOnView.value = selectedDay
    }

    function syncScrollShiftPlan(e: Event) {
        const src = e.target
        if (!(src instanceof HTMLElement)) return

        const target = userOverviewEl.value
        if (target) {
            _syncScroll('shift', target, src.scrollLeft)
        }

        // Current-Day-Berechnung throttlen: maximal 1x pro Frame
        if (!dayScanRafId) {
            dayScanRafId = requestAnimationFrame(scanCurrentDay)
        }
    }

    function attach() {
        // defensiv: zuerst abkoppeln
        detach()

        const passiveOpts: AddEventListenerOptions = { passive: true }

        if (shiftPlanEl.value) {
            shiftPlanEl.value.addEventListener('scroll', syncScrollShiftPlan, passiveOpts)
        }
        if (userOverviewEl.value) {
            userOverviewEl.value.addEventListener('scroll', syncScrollUserOverview, passiveOpts)
        }
    }

    function detach() {
        if (shiftPlanEl.value) {
            shiftPlanEl.value.removeEventListener('scroll', syncScrollShiftPlan)
        }
        if (userOverviewEl.value) {
            userOverviewEl.value.removeEventListener('scroll', syncScrollUserOverview)
        }

        if (releaseRafId) {
            cancelAnimationFrame(releaseRafId)
            releaseRafId = null
        }
        if (dayScanRafId) {
            cancelAnimationFrame(dayScanRafId)
            dayScanRafId = null
        }

        syncingFrom.value = null
        dayElCache.clear()
    }

    return {
        // Handler (falls du sie direkt binden möchtest)
        syncScrollUserOverview,
        syncScrollShiftPlan,
        // Lifecycle-angepasste Methoden
        attach,
        detach,
    }
}
