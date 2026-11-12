/**
 * Freigabe-Status einer individuellen Zeit in der Dienstplan-Zelle. Reines JS ohne Vue, damit es in
 * Node-Tests läuft (tests/Frontend/individualTimeWorkflow.test.js).
 *
 * Die Zeit steht als Chip in den Farben der KW-Kachel (ShiftPlan.vue): grün freigegeben (festgeschrieben),
 * blau in einer offenen Freigabe-Anfrage. Ohne Freigabe-Status gibt es keinen Chip.
 */
const WORKFLOW_MARKERS = {
    committed: {
        chip: 'rounded px-1 box-decoration-clone bg-[var(--uo-kw-committed-bg)] ring-1 ring-inset ring-[var(--uo-kw-committed-ring)]',
        titleKey: 'Individual time released',
    },
    requested: {
        chip: 'rounded px-1 box-decoration-clone bg-[var(--uo-kw-requested-bg)] ring-1 ring-inset ring-[var(--uo-kw-requested-ring)]',
        titleKey: 'Individual time in release request',
    },
};

/**
 * @param {string|null|undefined} status workflow_status aus dem Payload ('committed' | 'requested' | null)
 * @returns {{chip: string, titleKey: string}|null}
 */
export function individualTimeWorkflowMarker(status) {
    return WORKFLOW_MARKERS[status] ?? null;
}
