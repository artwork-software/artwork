import { computed, reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Live-Overrides pro Datum (YYYY-MM-DD → dayRemark|null). Modul-global, damit
// eigene Speicherungen UND Broadcasts alle Ansichten sofort aktualisieren —
// die day-Objekte der Seiten sind teils nicht reaktiv (computed/enrichDays,
// v-memo, shallowRef), eine Mutation daran löst kein Re-Render aus. Alle
// Anzeigen lesen deshalb über remarkForDay() aus diesem Store.
const liveRemarks = reactive({})

/**
 * Tagesbemerkungen: zentrale Sichtbarkeits-/Berechtigungslogik + Persistenz.
 *
 * Instanz-Setting und Rechte kommen als globaler Inertia-Prop `day_remarks`
 * (HandleInertiaRequests). Die User-Abwahl (`show_day_remarks`) liegt auf
 * user_calendar_settings und gilt einheitlich für Kalender, Planungskalender
 * und Dienstplan; bei Pflicht-Einstellung überstimmt das Admin-Setting.
 */
export function useDayRemarks() {
    const page = usePage()

    const state = computed(
        () => page.props.day_remarks ?? { enabled: false, mandatory: false, can_view: false, can_edit: false }
    )

    const enabled = computed(() => !!state.value.enabled)
    const mandatory = computed(() => !!state.value.mandatory)
    const canView = computed(() => enabled.value && !!state.value.can_view)
    const canEdit = computed(() => enabled.value && !!state.value.can_edit)

    // Spalte anzeigen: Feature aktiv + Sichtrecht + (Pflicht ODER User-Setting an;
    // fehlender Wert bei Alt-Settings zählt als aktiviert)
    const columnVisible = computed(() => {
        if (!canView.value) {
            return false
        }
        if (mandatory.value) {
            return true
        }
        return page.props.auth?.user?.calendar_settings?.show_day_remarks ?? true
    })

    /**
     * Live-Override eines Datums setzen — Quelle für remarkForDay().
     * @param {string} isoDate YYYY-MM-DD
     * @param {object|null} dayRemark
     */
    const applyLiveRemark = (isoDate, dayRemark) => {
        liveRemarks[isoDate] = dayRemark ?? null
    }

    /**
     * Effektive Bemerkung eines Tages: Live-Override (eigene Speicherung oder
     * Broadcast) vor dem Server-Payload des Seitenaufrufs.
     * @param {object|null} day enriched day (withoutFormat/date + dayRemark)
     * @returns {object|null}
     */
    const remarkForDay = (day) => {
        const key = day?.withoutFormat ?? day?.date
        if (key && key in liveRemarks) {
            return liveRemarks[key]
        }
        return day?.dayRemark ?? null
    }

    /**
     * Upsert der Bemerkung eines Tages (leerer Text löscht). Aktualisiert bei
     * Erfolg direkt den Live-Store — alle Ansichten rendern sofort neu.
     * @param {string} isoDate YYYY-MM-DD
     * @param {string} text
     * @returns {Promise<{date: string, dayRemark: object|null}>}
     */
    const saveDayRemark = (isoDate, text) =>
        axios.put(route('day-remarks.upsert', { date: isoDate }), { remark: text }).then((response) => {
            applyLiveRemark(response.data.date, response.data.dayRemark)
            return response.data
        })

    /**
     * Live-Updates abonnieren (Broadcast `day-remark.updated` auf dem privaten
     * `day-remarks`-Channel). Schreibt eingehende Updates in den Live-Store;
     * No-op ohne Sichtrecht. Gibt eine Stop-Funktion zurück — in
     * onBeforeUnmount aufrufen.
     *
     * @param {(payload: {date: string, dayRemark: object|null}) => void} [onUpdate] optionaler Zusatz-Hook
     * @returns {() => void}
     */
    const listenForDayRemarkUpdates = (onUpdate) => {
        if (!canView.value || typeof window === 'undefined' || !window.Echo) {
            return () => {}
        }
        // Frischer Seiten-Payload ist aktueller als alte Overrides früherer Seiten
        Object.keys(liveRemarks).forEach((key) => delete liveRemarks[key])
        window.Echo.private('day-remarks').listen('.day-remark.updated', (payload) => {
            applyLiveRemark(payload.date, payload.dayRemark ?? null)
            onUpdate?.({ date: payload.date, dayRemark: payload.dayRemark ?? null })
        })
        return () => {
            window.Echo.leave('day-remarks')
        }
    }

    return {
        enabled,
        mandatory,
        canView,
        canEdit,
        columnVisible,
        saveDayRemark,
        applyLiveRemark,
        remarkForDay,
        listenForDayRemarkUpdates,
    }
}

export const DAY_REMARK_COLUMN_WIDTH = 192
export const DAY_REMARK_MAX_LENGTH = 500
