import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

/**
 * Zentraler, reaktiver Zoom für die Schichtplan-Wochenansicht.
 *
 * Anders als im Kalender (dort: Zeilenhöhe) steuert der Zoom hier die
 * TAGESSPALTENBREITE — der Schichtplan ist transponiert (Räume = Zeilen,
 * Tage = Spalten), "mehr auf einen Blick" heißt also mehr Tage horizontal.
 * Die Raumzeilenhöhe bleibt inhaltsgetrieben (112px bzw. expand_days).
 *
 * Bewusst eigener Faktor (user_shift_plan_settings.zoom_factor), NICHT der
 * Kalender-zoom_factor — sonst verstellt der Kalender-Zoom den Schichtplan mit.
 */

export const SHIFT_PLAN_ZOOM_STEPS = [0.55, 0.75, 1]

// Basisbreite einer Tagesspalte (Haupt- UND Worker-Grid). ShiftPlan.vue streckt die
// Tagesspalten zusätzlich elastisch, wenn der geladene Zeitraum schmaler als der
// Viewport ist — dieser Wert ist also die MINDESTbreite je Zoomstufe.
// KW-Trennspalte (130px) und Sticky-Spalte (192px) skalieren bewusst nicht.
export const BASE_DAY_COL_WIDTH = 202

const zoomFactor = ref(null)
let persistTimer = null

const snapToStep = (value) => {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) {
        return 1
    }
    return SHIFT_PLAN_ZOOM_STEPS.reduce(
        (closest, step) => (Math.abs(step - numeric) < Math.abs(closest - numeric) ? step : closest),
        SHIFT_PLAN_ZOOM_STEPS[0]
    )
}

export function useShiftPlanZoom() {
    const page = usePage()

    if (zoomFactor.value === null) {
        zoomFactor.value = snapToStep(page.props.shift_plan_settings?.zoom_factor ?? 1)
    }

    const persistDebounced = () => {
        if (persistTimer) {
            clearTimeout(persistTimer)
        }
        persistTimer = setTimeout(() => {
            axios
                .patch(route('user.update.shift_plan_zoom_factor', { user: page.props.auth.user.id }), {
                    zoom_factor: zoomFactor.value,
                })
                .catch(() => {
                    // Anzeige bleibt lokal korrekt; beim nächsten Wechsel wird erneut gespeichert.
                })
        }, 500)
    }

    const setZoomFactor = (value) => {
        const snapped = snapToStep(value)
        if (snapped === zoomFactor.value) {
            return
        }
        zoomFactor.value = snapped
        if (page.props.shift_plan_settings) {
            page.props.shift_plan_settings.zoom_factor = snapped
        }
        persistDebounced()
    }

    const zoomIn = () => {
        const idx = SHIFT_PLAN_ZOOM_STEPS.indexOf(zoomFactor.value)
        if (idx < SHIFT_PLAN_ZOOM_STEPS.length - 1) {
            setZoomFactor(SHIFT_PLAN_ZOOM_STEPS[idx + 1])
        }
    }

    const zoomOut = () => {
        const idx = SHIFT_PLAN_ZOOM_STEPS.indexOf(zoomFactor.value)
        if (idx > 0) {
            setZoomFactor(SHIFT_PLAN_ZOOM_STEPS[idx - 1])
        }
    }

    const zoomPercent = computed(() => Math.round(zoomFactor.value * 100))

    // Unter 100 % rendern Schichten als Kompaktkarte (Zeit · Gewerk · Besetzung)
    const isCompact = computed(() => zoomFactor.value < 1)

    const dayColWidth = computed(() => Math.round(BASE_DAY_COL_WIDTH * zoomFactor.value))

    return {
        zoomFactor,
        zoomPercent,
        zoomSteps: SHIFT_PLAN_ZOOM_STEPS,
        setZoomFactor,
        zoomIn,
        zoomOut,
        isCompact,
        dayColWidth,
    }
}
