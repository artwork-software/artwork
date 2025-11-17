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

    // Loop-Guard, damit A->B nicht wieder B->A triggert usw.
    const syncingFrom = ref<'shift' | 'overview' | null>(null)
    let rafId: number | null = null

    function _syncScroll(source: 'shift' | 'overview', targetEl: HTMLElement, left: number) {
        // Guard: nicht zurückspielen, wenn wir gerade vom Ziel kommen
        if (syncingFrom.value && syncingFrom.value !== source) return
        syncingFrom.value = source
        targetEl.scrollLeft = left
        // nach dem nächsten Frame wieder freigeben
        if (rafId) cancelAnimationFrame(rafId)
        rafId = requestAnimationFrame(() => {
            syncingFrom.value = null
            rafId = null
        })
    }

    function syncScrollUserOverview(e: Event) {
        const src = e.target as HTMLElement
        const target = shiftPlanEl.value
        if (!target) return
        _syncScroll('overview', target, src.scrollLeft)
    }

    function syncScrollShiftPlan(e: Event) {
        const src = e.target as HTMLElement
        const target = userOverviewEl.value
        if (target) {
            _syncScroll('shift', target, src.scrollLeft)
        }

        const container = shiftPlanEl.value
        if (!container || !days.value?.length) return

        const roomNameFixedPosition = container.getBoundingClientRect().left + roomNameOffsetPx

        let closestDayIndex: number | null = null
        let closestDayDistance = Infinity

        for (let i = 0; i < days.value.length; i++) {
            const day = days.value[i]
            const id = getDayElementId(day)
            if (!id) continue

            const dayElement = document.getElementById(id)
            if (!dayElement) continue

            const rect = dayElement.getBoundingClientRect()
            const elementCenter = rect.left + rect.width / 2
            const distance = Math.abs(roomNameFixedPosition - elementCenter)

            if (distance < closestDayDistance) {
                closestDayDistance = distance
                closestDayIndex = i
            }
        }

        if (closestDayIndex !== null) {
            const selectedDay = days.value[closestDayIndex]
            if (selectedDay?.isExtraRow) {
                for (let j = closestDayIndex + 1; j < days.value.length; j++) {
                    if ((days.value[j] as any).isMonday) {
                        currentDayOnView.value = days.value[j]
                        return
                    }
                }
            } else {
                currentDayOnView.value = selectedDay
            }
        }
    }

    function attach() {
        // defensiv: zuerst abkoppeln
        detach()

        if (shiftPlanEl.value) {
            shiftPlanEl.value.addEventListener('scroll', syncScrollShiftPlan, { passive: true })
        }
        if (userOverviewEl.value) {
            userOverviewEl.value.addEventListener('scroll', syncScrollUserOverview, { passive: true })
        }
    }

    function detach() {
        if (shiftPlanEl.value) {
            shiftPlanEl.value.removeEventListener('scroll', syncScrollShiftPlan)
        }
        if (userOverviewEl.value) {
            userOverviewEl.value.removeEventListener('scroll', syncScrollUserOverview)
        }
        if (rafId) {
            cancelAnimationFrame(rafId)
            rafId = null
        }
        syncingFrom.value = null
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
