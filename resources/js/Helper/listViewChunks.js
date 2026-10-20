/**
 * Blockweises Laden der Dienstplan-Listenansicht (ShiftPlanListView): Die Seite liefert den ersten Block,
 * die übrigen Tage kommen über shifts.plan.list-view.days. Jeder Block "besitzt" einen Tagesbereich —
 * der erste zusätzlich alle Tage davor, der letzte alle danach (Schichten, die vor/nach dem Zeitraum
 * starten; siehe ShiftListViewService::getGroupedShifts). Ein Block ersetzt beim Zusammenführen genau
 * seine Tage, sodass die Vereinigung aller Blöcke dem Gesamtergebnis entspricht.
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit (tests/Frontend/listViewChunks.test.js). Datumswerte sind
 * 'YYYY-MM-DD'-Strings und werden in UTC gerechnet (keine Zeitzonen-/Sommerzeit-Effekte).
 */

const toUtc = (day) => new Date(`${day}T00:00:00Z`);
const toDay = (date) => date.toISOString().slice(0, 10);

/** Tag + n Tage ('YYYY-MM-DD') */
export function addDays(day, days) {
    const date = toUtc(day);
    date.setUTCDate(date.getUTCDate() + days);
    return toDay(date);
}

/**
 * Blöcke ab fromDay bis end (inklusive), je chunkDays Tage.
 *
 * @returns {Array<{start: string, end: string}>}
 */
export function chunkRanges(fromDay, end, chunkDays) {
    const ranges = [];
    if (!fromDay || !end || fromDay > end || !(chunkDays > 0)) {
        return ranges;
    }
    for (let start = fromDay; start <= end; start = addDays(start, chunkDays)) {
        const chunkEnd = addDays(start, chunkDays - 1);
        ranges.push({ start, end: chunkEnd < end ? chunkEnd : end });
    }
    return ranges;
}

/**
 * Tagesbereich, den ein Block beim Zusammenführen ersetzt: null = offen (erster bzw. letzter Block).
 *
 * @returns {{from: string|null, to: string|null}}
 */
export function ownedRange(chunk, periodStart, periodEnd) {
    return {
        from: chunk.start <= periodStart ? null : chunk.start,
        to: chunk.end >= periodEnd ? null : chunk.end,
    };
}

/**
 * Tage eines Blocks in die geladenen Tage übernehmen: alle Tage im besessenen Bereich werden ersetzt
 * (auch weggefallene), das Ergebnis ist nach Tag sortiert.
 *
 * @param {Array<{day: string}>} days
 * @param {Array<{day: string}>} chunkDays
 * @param {{from: string|null, to: string|null}} owned
 */
export function mergeDayChunk(days, chunkDays, owned) {
    const inOwned = (day) => (owned.from === null || day >= owned.from) && (owned.to === null || day <= owned.to);
    return [
        ...(days || []).filter((entry) => !inOwned(entry.day)),
        ...(chunkDays || []).filter((entry) => inOwned(entry.day)),
    ].sort((a, b) => (a.day < b.day ? -1 : a.day > b.day ? 1 : 0));
}

/**
 * Lade-Reihenfolge beim Aktualisieren: Blöcke mit sichtbaren Tagen zuerst, danach die übrigen in
 * Datumsreihenfolge.
 *
 * @param {Array<{start: string, end: string}>} ranges
 * @param {Iterable<string>} visibleDays
 */
export function prioritizeChunks(ranges, visibleDays) {
    const visible = [...(visibleDays || [])];
    const isVisible = (range) => visible.some((day) => day >= range.start && day <= range.end);
    return [...ranges.filter(isVisible), ...ranges.filter((range) => !isVisible(range))];
}
