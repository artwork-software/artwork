/**
 * Freigabe-Status einer KW-Kachel im Dienstplan (Map aus ShiftPlanWorkflowStatusService).
 *
 * Schlüssel je Gewerk-ID; individuelle Zeiten gehören zur Person und stehen für Wochen, in denen die
 * Person im Gewerk keine Schicht hat, unter INDIVIDUAL_TIMES_KEY. Ein Gewerk-Eintrag hat Vorrang
 * (auch 'open', das bewusst keinen Status färbt).
 */
export const INDIVIDUAL_TIMES_KEY = '*';

/**
 * @param {Object|null|undefined} weeklyWorkflowStatus [craftId|'*'][weekNumber] => status
 * @param {number|string} craftId
 * @param {number|string} weekNumber
 * @returns {string|null} attention | requested | committed | open | null
 */
export function resolveKwWorkflowStatus(weeklyWorkflowStatus, craftId, weekNumber) {
    if (!weeklyWorkflowStatus) return null;
    return weeklyWorkflowStatus[craftId]?.[weekNumber]
        ?? weeklyWorkflowStatus[INDIVIDUAL_TIMES_KEY]?.[weekNumber]
        ?? null;
}

/**
 * Plakette für den Freigabe-Status einer individuellen Zeit (workflow_status aus dem Backend).
 * Farben wie die KW-Kachel: angefragt blau, freigegeben grün.
 *
 * @param {string|null|undefined} workflowStatus committed | requested | null
 * @returns {{label: string, classes: string}|null} label = Übersetzungsschlüssel
 */
export function individualTimeWorkflowBadge(workflowStatus) {
    switch (workflowStatus) {
        case 'committed':
            return { label: 'Released', classes: 'bg-success-surface text-success ring-success-border' };
        case 'requested':
            return { label: 'In release request', classes: 'bg-accent-50 text-accent-600 ring-accent-200' };
        default:
            return null;
    }
}
