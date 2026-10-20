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

/** Besitzt der Block den Tag? (erster/letzter Block auch Tage vor/nach dem Zeitraum) */
function ownsDay(range, day, periodStart, periodEnd) {
    const owned = ownedRange(range, periodStart, periodEnd);
    return (owned.from === null || day >= owned.from) && (owned.to === null || day <= owned.to);
}

/**
 * Blöcke im oder am sichtbaren Bereich: mit sichtbaren Tagen oder ohne gerenderten Tag zwischen Block und
 * sichtbarem Bereich. Leere Wochen haben keine Zeile, die der IntersectionObserver melden könnte — sie
 * gelten als sichtbar, sobald ihre gerenderten Nachbartage es sind. Ohne gerenderte Tage (leere Liste) sind
 * alle Blöcke betroffen; sind Tage gerendert, aber (noch) keine sichtbar, keiner.
 *
 * @param {Array<{start: string, end: string}>} ranges
 * @param {{periodStart: string, periodEnd: string, visibleDays: Iterable<string>, renderedDays: Iterable<string>}} view
 */
export function rangesNearVisibleDays(ranges, { periodStart, periodEnd, visibleDays, renderedDays }) {
    const visible = [...(visibleDays || [])].sort();
    const rendered = [...(renderedDays || [])];
    if (!visible.length) {
        return rendered.length ? [] : [...ranges];
    }
    const firstVisible = visible[0];
    const lastVisible = visible[visible.length - 1];
    // nächstgelegene gerenderte, nicht sichtbare Tage vor bzw. nach dem sichtbaren Bereich
    let renderedBefore = null;
    let renderedAfter = null;
    for (const day of rendered) {
        if (day < firstVisible && (renderedBefore === null || day > renderedBefore)) renderedBefore = day;
        if (day > lastVisible && (renderedAfter === null || day < renderedAfter)) renderedAfter = day;
    }

    return ranges.filter((range) => {
        const owned = ownedRange(range, periodStart, periodEnd);
        return (owned.to === null || renderedBefore === null || owned.to > renderedBefore)
            && (owned.from === null || renderedAfter === null || owned.from < renderedAfter);
    });
}

/**
 * Blöcke, die nach einer Aktion bzw. einem fremden Broadcast sofort neu laden: die am sichtbaren Bereich
 * und die mit betroffenen Tagen, sichtbare zuerst. Alle übrigen gelten als veraltet und laden beim
 * Hineinscrollen (rangesNearVisibleDays) nach.
 *
 * @param {Array<{start: string, end: string}>} ranges
 * @param {{periodStart: string, periodEnd: string, visibleDays: Iterable<string>, renderedDays: Iterable<string>,
 *     affectedDays?: Iterable<string>}} view
 */
export function chunksToLoadNow(ranges, view) {
    const due = new Set([
        ...rangesNearVisibleDays(ranges, view),
        ...chunksOwningDays(ranges, view.affectedDays, view.periodStart, view.periodEnd),
    ]);
    return prioritizeChunks(ranges.filter((range) => due.has(range)), view.visibleDays);
}

/** Blöcke, die einen der Tage besitzen */
export function chunksOwningDays(ranges, days, periodStart, periodEnd) {
    const wanted = [...(days || [])].filter(Boolean);
    return ranges.filter((range) => wanted.some((day) => ownsDay(range, day, periodStart, periodEnd)));
}
