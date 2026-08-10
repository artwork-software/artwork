import { router, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'

/**
 * Zu-/Absage einer Schichtzuweisung (shift_workers-Pivot).
 *
 * Der Pivot trägt confirmation_status ('accepted' | 'declined' | null = ausstehend),
 * confirmation_at, confirmation_by_user_id und confirmation_comment.
 */
export function useShiftWorkerConfirmation() {
    const isEnabled = () => !!usePage().props.shift_confirmation_enabled

    const respond = (pivotId, status, comment = null, options = {}) => {
        router.patch(
            route('shift-worker.confirmation.update', { shiftWorker: pivotId }),
            { status, comment },
            { preserveScroll: true, ...options }
        )
    }

    // Liefert null (Feature aus / kein Status) oder ein Anzeige-Objekt für
    // Rahmen + Tooltip an Worker-Chips.
    const getConfirmationInfo = (worker) => {
        const pivot = worker?.pivot
        if (!isEnabled() || !pivot?.confirmation_status) {
            return null
        }

        const isProxy = worker.type === 'user'
            ? pivot.confirmation_by_user_id !== null && Number(pivot.confirmation_by_user_id) !== Number(worker.id)
            : true

        return {
            status: pivot.confirmation_status,
            accepted: pivot.confirmation_status === 'accepted',
            date: pivot.confirmation_at ? dayjs(pivot.confirmation_at).format('DD.MM.YYYY') : null,
            isProxy,
            comment: pivot.confirmation_comment || null,
        }
    }

    // Rahmenklassen für Worker-Chips in den Planer-Ansichten.
    const getConfirmationBorderClass = (worker) => {
        const info = getConfirmationInfo(worker)
        if (!info) {
            return ''
        }

        return info.accepted
            ? '!border-success ring-1 ring-success'
            : '!border-danger ring-1 ring-danger'
    }

    // Tooltip-Text; $t wird hereingereicht, damit die Komponenten-Übersetzung greift.
    const getConfirmationTooltip = (worker, $t) => {
        const info = getConfirmationInfo(worker)
        if (!info) {
            return null
        }

        const name = worker.name
            || worker.provider_name
            || [worker.first_name, worker.last_name].filter(Boolean).join(' ')

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
        respond,
        getConfirmationInfo,
        getConfirmationBorderClass,
        getConfirmationTooltip,
    }
}
