import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

/**
 * Zentraler, reaktiver Kalender-Zoom.
 *
 * Ersetzt die früheren, unabhängigen `usePage().props.auth.user.zoom_factor`-Reads
 * in den Kalender-Komponenten. Der Zoom steuert nur noch die Informationsdichte
 * (Zeilenhöhe, Kachel-Kompaktheit) — die Raumspaltenbreite ist entkoppelt und
 * kommt aus den Anzeigeeinstellungen (calendar_column_width).
 *
 * Persistiert debounced per axios, ohne Inertia-Full-Reload: alle Konsumenten
 * teilen sich die Refs in diesem Modul und reagieren sofort.
 */

export const CALENDAR_ZOOM_STEPS = [0.4, 0.6, 0.8, 1, 1.2, 1.4]

// Zeilenhöhe je Stufe: unterhalb 100 % deutlich steiler fallend als die alte
// Gerade (zoom * 212), damit bei 40 % ("Monatsansicht") 33px-Zeilen entstehen
// und ein ganzer Monat auf den Schirm passt. Ab 100 % unverändert zu vorher.
const ROW_HEIGHT_BY_STEP = {
    0.4: 33,
    0.6: 64,
    0.8: 110,
    1: 212,
    1.2: 254,
    1.4: 297,
}

export const DEFAULT_COLUMN_WIDTH = 212
export const DATE_COLUMN_WIDTH = 90

// Unter dieser Stufe rendern Terminkacheln im Kompaktmodus (eine Zeile,
// Klick öffnet das Termin-Modal statt des Drei-Punkte-Menüs).
const COMPACT_THRESHOLD = 0.8

const zoomFactor = ref(null)
// Zähler statt Boolean: jede Monatsansicht-Auswahl soll erneut zum
// Monatsanfang scrollen, auch wenn der Zoom bereits auf 40 % steht.
const monthViewRequestId = ref(0)
let persistTimer = null

const snapToStep = (value) => {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) {
        return 1
    }
    return CALENDAR_ZOOM_STEPS.reduce(
        (closest, step) => (Math.abs(step - numeric) < Math.abs(closest - numeric) ? step : closest),
        CALENDAR_ZOOM_STEPS[0]
    )
}

export function useCalendarZoom() {
    const page = usePage()

    if (zoomFactor.value === null) {
        zoomFactor.value = snapToStep(page.props.auth.user.zoom_factor ?? 1)
        // Legacy-Werte (z.B. 0.2) auf die gültigen Stufen einrasten — auch in den
        // Page-Props, damit Alt-Konsumenten dieselbe Stufe sehen.
        page.props.auth.user.zoom_factor = zoomFactor.value
    }

    const persistDebounced = () => {
        if (persistTimer) {
            clearTimeout(persistTimer)
        }
        persistTimer = setTimeout(() => {
            const sentValue = zoomFactor.value
            axios
                .patch(route('user.update.zoom_factor', { user: page.props.auth.user.id }), {
                    zoom_factor: sentValue,
                })
                .then(() => {
                    // Während des Requests weitergezoomt? Aktuellen Wert nachspeichern —
                    // sonst kann ein langsamer, älterer Request in der DB "gewinnen".
                    if (zoomFactor.value !== sentValue) {
                        persistDebounced()
                    }
                })
                .catch(() => {
                    // Anzeige bleibt lokal korrekt; beim nächsten Zoom-Wechsel wird erneut gespeichert.
                })
        }, 500)
    }

    const setZoomFactor = (value) => {
        const snapped = snapToStep(value)
        if (snapped === zoomFactor.value) {
            return
        }
        zoomFactor.value = snapped
        // Legacy-Konsumenten (z.B. ShiftInCalendarCell) lesen den Wert beim Mount
        // aus den Page-Props — synchron halten, damit sie frisch mounten.
        page.props.auth.user.zoom_factor = snapped
        persistDebounced()
    }

    const zoomIn = () => {
        const idx = CALENDAR_ZOOM_STEPS.indexOf(zoomFactor.value)
        if (idx < CALENDAR_ZOOM_STEPS.length - 1) {
            setZoomFactor(CALENDAR_ZOOM_STEPS[idx + 1])
        }
    }

    const zoomOut = () => {
        const idx = CALENDAR_ZOOM_STEPS.indexOf(zoomFactor.value)
        if (idx > 0) {
            setZoomFactor(CALENDAR_ZOOM_STEPS[idx - 1])
        }
    }

    // "Monatsansicht": kompakteste Stufe + (im BaseCalendar beobachtet) Scroll
    // zum Anfang des aktuell fokussierten Monats.
    const requestMonthView = () => {
        setZoomFactor(CALENDAR_ZOOM_STEPS[0])
        monthViewRequestId.value++
    }

    const zoomPercent = computed(() => Math.round(zoomFactor.value * 100))

    const isCompact = computed(() => zoomFactor.value < COMPACT_THRESHOLD)

    // Vom Zoom entkoppelt: globale Raumspaltenbreite aus den Anzeigeeinstellungen.
    const columnWidth = computed(() => {
        const stored = Number(page.props.auth.user.calendar_settings?.calendar_column_width)
        return Number.isFinite(stored) && stored >= 120 && stored <= 400 ? stored : DEFAULT_COLUMN_WIDTH
    })

    const rowHeight = computed(
        () => ROW_HEIGHT_BY_STEP[zoomFactor.value] ?? Math.round(zoomFactor.value * 212)
    )

    // Kachelbreite folgt der Spaltenbreite (16px Innenabstand wie bisher 212→196).
    const eventCardWidth = computed(() => columnWidth.value - 16)

    // Schrift der (nicht-kompakten) Terminkacheln skaliert weiter mit dem Zoom.
    const fontSize = computed(() => `max(calc(${zoomFactor.value} * 0.875rem), 10px)`)
    const lineHeight = computed(() => `max(calc(${zoomFactor.value} * 1.25rem), 1.3)`)

    return {
        zoomFactor,
        zoomPercent,
        zoomSteps: CALENDAR_ZOOM_STEPS,
        setZoomFactor,
        zoomIn,
        zoomOut,
        requestMonthView,
        monthViewRequestId,
        isCompact,
        columnWidth,
        rowHeight,
        eventCardWidth,
        dateColumnWidth: DATE_COLUMN_WIDTH,
        fontSize,
        lineHeight,
    }
}
