import { router, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'

/**
 * Zu-/Absage einer Schichtzuweisung (shift_workers-Pivot).
 *
 * Der Pivot trägt confirmation_status ('accepted' | 'declined' | null = ausstehend),
 * confirmation_at, confirmation_by_user_id und confirmation_comment.
 *
 * Teilnahme am Flow ist ein Opt-in je Person über das Recht „Darf Schichten
 * annehmen/ablehnen": das Backend liefert dazu worker.confirmation_eligible
 * (ShiftConfirmationEligibilityService). Ohne das Flag gibt es weder Buttons
 * noch Status — auch ein alter, noch gespeicherter Status wird nicht gezeigt.
 *
 * Status-Modell (getConfirmationInfo):
 *   requested  — zugewiesen (auch vorläufig), Person berechtigt, noch keine Antwort (blau)
 *   accepted   — zugesagt (grün)
 *   declined   — abgesagt (rot)
 */
export const CONFIRMATION_REQUESTED = 'requested'
export const CONFIRMATION_ACCEPTED = 'accepted'
export const CONFIRMATION_DECLINED = 'declined'

export function useShiftWorkerConfirmation() {
    const isEnabled = () => !!usePage().props.shift_confirmation_enabled

    // Person nimmt am Zu-/Absage-Flow teil (Recht am User; Externe nie)
    const isEligible = (worker) => !!worker?.confirmation_eligible

    const respond = (pivotId, status, comment = null, options = {}) => {
        router.patch(
            route('shift-worker.confirmation.update', { shiftWorker: pivotId }),
            { status, comment },
            { preserveScroll: true, ...options }
        )
    }

    // Liefert null (Feature aus / Person nicht berechtigt) oder ein Anzeige-Objekt für
    // Pille, Rahmen und Tooltip. Unbeantwortet = „angefragt" — unabhängig davon, ob die
    // Schicht festgeschrieben ist. `shift` bleibt als Parameter für Aufrufer erhalten.
    // eslint-disable-next-line no-unused-vars
    const getConfirmationInfo = (worker, shift = null) => {
        const pivot = worker?.pivot
        if (!isEnabled() || !pivot || !isEligible(worker)) {
            return null
        }

        if (!pivot.confirmation_status) {
            return {
                status: CONFIRMATION_REQUESTED,
                requested: true,
                accepted: false,
                declined: false,
                date: null,
                isProxy: false,
                comment: null,
            }
        }

        const isProxy = worker.type === 'user'
            ? pivot.confirmation_by_user_id !== null && Number(pivot.confirmation_by_user_id) !== Number(worker.id)
            : true

        return {
            status: pivot.confirmation_status,
            requested: false,
            accepted: pivot.confirmation_status === CONFIRMATION_ACCEPTED,
            declined: pivot.confirmation_status === CONFIRMATION_DECLINED,
            date: pivot.confirmation_at ? dayjs(pivot.confirmation_at).format('DD.MM.YYYY') : null,
            isProxy,
            comment: pivot.confirmation_comment || null,
        }
    }

    // Rahmenklassen für Worker-Chips in den Planer-Ansichten.
    const getConfirmationBorderClass = (worker, shift = null) => {
        const info = getConfirmationInfo(worker, shift)
        if (!info) {
            return ''
        }

        if (info.requested) {
            return '!border-accent-500 ring-1 ring-accent-500'
        }

        return info.accepted
            ? '!border-success ring-1 ring-success'
            : '!border-danger ring-1 ring-danger'
    }

    // Tooltip-Text; $t wird hereingereicht, damit die Komponenten-Übersetzung greift.
    const getConfirmationTooltip = (worker, $t, shift = null) => {
        const info = getConfirmationInfo(worker, shift)
        if (!info) {
            return null
        }

        const name = worker.name
            || worker.provider_name
            || [worker.first_name, worker.last_name].filter(Boolean).join(' ')

        if (info.requested) {
            return $t('Requested from {name} – no reply yet', { name })
        }

        let text = info.accepted
            ? $t('Accepted by {name} on {date}', { name, date: info.date ?? '–' })
            : $t('Declined by {name} on {date}', { name, date: info.date ?? '–' })

        if (info.isProxy) {
            text += ` (${$t('recorded by planner')})`
        }

        if (info.comment) {
            text += ` – „${info.comment}“`
        }

        return text
    }

    return {
        isEnabled,
        isEligible,
        respond,
        getConfirmationInfo,
        getConfirmationBorderClass,
        getConfirmationTooltip,
    }
}
