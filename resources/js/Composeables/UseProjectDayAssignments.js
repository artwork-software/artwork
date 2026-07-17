// Geteilte Helfer für Projektzuordnungen im Dienstplan/Einsatzplan/ShiftTab:
// deterministische Projektfarben (Muster planningBars.js) + Streifen-Styles
// für die Per-Zellen-Balken (verbindlich = gefüllt, Wunsch = gestreift).

export const PROJECT_BAR_PALETTE = [
    '#0ea5e9', // sky
    '#22c55e', // green
    '#a855f7', // purple
    '#f59e0b', // amber
    '#14b8a6', // teal
    '#8b5cf6', // violet
    '#0891b2', // cyan
    '#65a30d', // lime
    '#db2777', // pink
    '#ea580c', // orange
    '#4338ca', // indigo
    '#0d9488', // teal-dark
];

/** Deterministischer 32-bit-Hash (Projekt-ID → stabile Farbe). */
export function hashProjectId(id) {
    let h = 0;
    const s = String(id);
    for (let i = 0; i < s.length; i++) {
        h = (h * 31 + s.charCodeAt(i)) >>> 0;
    }
    return h;
}

export function colorForProjectId(projectId) {
    return PROJECT_BAR_PALETTE[hashProjectId(projectId) % PROJECT_BAR_PALETTE.length];
}

/**
 * Inline-Style für einen Zuordnungs-Streifen: verbindlich = gefüllt,
 * Wunsch = 45°-Streifenmuster (analog Extern-Materialausgaben).
 */
export function assignmentStripStyle(assignment) {
    const color = colorForProjectId(assignment.project_id);

    if (assignment.type === 'wish') {
        return {
            background: `repeating-linear-gradient(45deg, ${color}66, ${color}66 4px, transparent 4px, transparent 8px)`,
            border: `1px solid ${color}99`,
        };
    }

    return { backgroundColor: color };
}

export function formatAssignmentDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    return `${day}.${month}.${date.getFullYear()}`;
}

/** Tooltip-/Listen-Label: Projektname + Serien-Zeitraum (+ Wunsch-Kennzeichnung). */
export function assignmentLabel(assignment, wishLabel = 'Wunsch') {
    const period = assignment.series_start === assignment.series_end
        ? formatAssignmentDate(assignment.series_start)
        : `${formatAssignmentDate(assignment.series_start)} - ${formatAssignmentDate(assignment.series_end)}`;
    const suffix = assignment.type === 'wish' ? ` (${wishLabel})` : '';

    return `${assignment.project_name} (${period})${suffix}`;
}
