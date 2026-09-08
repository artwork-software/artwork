/**
 * Reine Pausenlogik nach § 4 ArbZG – ohne Vue-Abhängigkeit, damit sie per
 * `node --test tests/Frontend` testbar ist. Die Composables useLegalBreak /
 * useAutoBreak bauen darauf auf.
 *
 * Grenzen: bis einschließlich 6:00 h keine Pause, über 6 h 30 min, über 9 h 45 min.
 * Vergleich strikt (6:00 → 0, 6:01 → 30, 9:00 → 30, 9:01 → 45).
 * Spiegel von artwork/Modules/Shift/Services/LegalBreakCalculator.php.
 */

/** @typedef {{ minMinutesExclusive?: number, maxMinutesInclusive?: number, breakMinutes: number }} BreakRule */

/** @type {BreakRule[]} */
export const ARBZG_RULES = [
    { minMinutesExclusive: 6 * 60, maxMinutesInclusive: 9 * 60, breakMinutes: 30 },
    { minMinutesExclusive: 9 * 60, breakMinutes: 45 },
];

/**
 * "HH:MM" oder "HH:MM:SS" → Minuten seit 0:00, sonst null.
 * @param {string|null|undefined} val
 * @returns {number|null}
 */
export function parseHHMM(val) {
    if (!val) return null;
    const m = String(val).trim().match(/^(\d{1,2}):([0-5]\d)(?::[0-5]\d)?$/);
    if (!m) return null;
    const h = +m[1];
    const mm = +m[2];
    if (h > 47) return null;
    return h * 60 + mm;
}

function roundTo(v, step) {
    if (step <= 1) return v;
    return Math.round(v / step) * step;
}

/**
 * Brutto-Arbeitsminuten zwischen zwei Uhrzeiten. Ende <= Start gilt als
 * Über-Mitternacht-Schicht (+24 h). Ungültige Eingaben ergeben 0.
 * @param {string|null|undefined} start
 * @param {string|null|undefined} end
 * @param {{ allowCrossMidnight?: boolean, roundToMinutes?: number }} [opts]
 * @returns {number}
 */
export function workMinutesBetween(start, end, opts = {}) {
    const { allowCrossMidnight = true, roundToMinutes = 1 } = opts;
    const s = parseHHMM(start);
    const e = parseHHMM(end);
    if (s == null || e == null) return 0;
    let diff = e - s;
    if (diff <= 0 && allowCrossMidnight) diff += 24 * 60;
    return diff > 0 ? roundTo(diff, roundToMinutes) : 0;
}

/**
 * Gesetzliche Mindestpause für eine Brutto-Arbeitszeit in Minuten.
 * @param {number} workMinutes
 * @param {BreakRule[]} [rules]
 * @returns {number}
 */
export function minimumBreakMinutes(workMinutes, rules = ARBZG_RULES) {
    if (workMinutes <= 0) return 0;
    for (const rule of rules) {
        const lo = rule.minMinutesExclusive ?? -Infinity;
        const hi = rule.maxMinutesInclusive ?? Infinity;
        if (workMinutes > lo && workMinutes <= hi) return rule.breakMinutes;
    }
    return 0;
}

/**
 * Gesetzliche Mindestpause direkt aus Start-/Endzeit.
 * @param {string|null|undefined} start
 * @param {string|null|undefined} end
 * @param {{ allowCrossMidnight?: boolean, roundToMinutes?: number, rules?: BreakRule[] }} [opts]
 * @returns {number}
 */
export function legalBreakMinutesFor(start, end, opts = {}) {
    return minimumBreakMinutes(workMinutesBetween(start, end, opts), opts.rules ?? ARBZG_RULES);
}

/**
 * Pausenfeld-Wert (Zahl, String, leer) → Zahl oder null bei leer/ungültig.
 * @param {number|string|null|undefined} value
 * @returns {number|null}
 */
export function toBreakNumber(value) {
    if (value === null || value === undefined || value === '') return null;
    const n = typeof value === 'number' ? value : Number(String(value).replace(',', '.'));
    return Number.isFinite(n) ? n : null;
}

/**
 * Entscheidung der Auto-Befüllung (reine Funktion, siehe useAutoBreak):
 * - leer → gesetzliches Minimum
 * - manuell geändert → unangetastet
 * - unter dem Minimum oder exakt der zuletzt automatisch gesetzte Wert → Minimum
 * - sonst unangetastet
 *
 * @param {{ current: number|string|null|undefined, legal: number, manuallyEdited: boolean, lastAutoValue: number|null }} state
 * @returns {number|null} neuer Wert oder null, wenn nichts zu ändern ist
 */
export function resolveAutoBreakValue({ current, legal, manuallyEdited, lastAutoValue }) {
    const value = toBreakNumber(current);
    if (value === null) return legal;
    if (manuallyEdited) return null;
    if (value < legal || value === lastAutoValue) return legal;
    return null;
}
