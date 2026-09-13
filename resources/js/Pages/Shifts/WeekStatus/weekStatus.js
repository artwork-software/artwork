/**
 * Status-Metadaten der Seite „Wochenstatus" (Gewerk × KW).
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit. Die Labels sind Übersetzungs-Keys
 * (werden vom Aufrufer über $t() aufgelöst), die Klassen nutzen die Design-Tokens
 * der Nachbarseiten (useShiftPlanRequest.statusClasses).
 */

export const STATUS_ORDER = ['open', 'requested', 'partial', 'committed', 'rejected', 'none']

export const STATUS_META = {
    none: {
        label: 'No shifts',
        description: 'No shifts of this craft in this week.',
        chip: 'bg-surface-sunken text-text-subtle ring-border-subtle',
        dot: 'bg-border',
    },
    open: {
        label: 'Open',
        description: 'Shifts exist, nothing is committed and no request has been submitted.',
        chip: 'bg-surface-sunken text-text-muted ring-border',
        dot: 'bg-text-subtle',
    },
    requested: {
        label: 'Requested',
        description: 'An approval request is pending.',
        chip: 'bg-accent-50 text-accent-700 ring-accent-200',
        dot: 'bg-accent-500',
    },
    partial: {
        label: 'Partially committed',
        description: 'Some shifts of this week are committed, others are not.',
        chip: 'bg-warning-surface text-warning ring-warning-border',
        dot: 'bg-warning',
    },
    committed: {
        label: 'Committed',
        description: 'All shifts of this week are committed.',
        chip: 'bg-success-surface text-success ring-success-border',
        dot: 'bg-success',
    },
    rejected: {
        label: 'Rejected',
        description: 'The last request was rejected and nothing has been resubmitted.',
        chip: 'bg-danger-surface text-danger ring-danger-border',
        dot: 'bg-danger',
    },
}

export const DEADLINE_META = {
    ok: { label: 'Deadline met', classes: 'bg-success' },
    due_soon: { label: 'Deadline due soon', classes: 'bg-warning' },
    overdue: { label: 'Deadline overdue', classes: 'bg-danger' },
}

export function statusMeta(status) {
    return STATUS_META[status] ?? STATUS_META.none
}

export function deadlineMeta(state) {
    return state ? (DEADLINE_META[state] ?? null) : null
}

/** Zelle ohne Bedarf und ohne Schichten → grauer Strich statt Kennzahlen */
export function isEmptyCell(cell) {
    if (!cell) return true
    return cell.status === 'none' && Number(cell.required_slots ?? 0) === 0
}

/**
 * Aktionen, die in der Detailansicht angeboten werden.
 * @param {object} cell
 * @param {{workflowEnabled: boolean, canCommit: boolean}} context
 */
export function availableActions(cell, { workflowEnabled, canCommit }) {
    if (!cell || cell.status === 'none') {
        return { canRequest: false, canCommitDirectly: false }
    }
    const requestable = ['open', 'rejected', 'partial'].includes(cell.status)
    return {
        canRequest: workflowEnabled && requestable && canCommit,
        canCommitDirectly: !workflowEnabled && cell.status !== 'committed' && canCommit,
    }
}

/** Montag/Sonntag einer Woche als 'YYYY-MM-DD' aus dem Wochen-Deskriptor des Backends */
export function shiftPlanFilterRange(week) {
    if (!week?.monday || !week?.sunday) return null
    return { start: week.monday, end: week.sunday }
}
