/**
 * Reine Hilfsfunktionen der Finanzierungsquellen-Liste, ohne Vue, damit sie sich isoliert testen lassen.
 */

/** Noch verfügbar: Volumen plus Summe der verknüpften Positionen (Ausgaben sind negativ). */
export function moneySourceRemainingAmount(moneySource) {
    return (Number(moneySource?.amount) || 0) + (Number(moneySource?.sumOfPositions) || 0);
}

/** Verbrauchter Anteil des Volumens in Prozent, begrenzt auf 0 bis 100. */
export function moneySourceUsedPercent(moneySource) {
    const total = Number(moneySource?.amount) || 0;
    if (total <= 0) {
        return moneySourceRemainingAmount(moneySource) < 0 ? 100 : 0;
    }
    const used = total - moneySourceRemainingAmount(moneySource);
    return Math.min(100, Math.max(0, Math.round((used / total) * 100)));
}

/**
 * Ganze Tage bis zu einem Datum (YYYY-MM-DD), negativ wenn es in der Vergangenheit liegt.
 * Ohne oder mit unlesbarem Datum null.
 */
export function daysUntilDate(dateString, today = new Date()) {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(dateString ?? ''));
    if (!match) {
        return null;
    }
    const target = Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    const start = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
    return Math.round((target - start) / 86400000);
}

/** Suche über Name, Beschreibung, Geldgeber, Kategorien und den Namen der Gruppe. */
export function matchesMoneySourceSearch(moneySource, term, groupName = '') {
    const needle = String(term ?? '').trim().toLowerCase();
    if (needle === '') {
        return true;
    }
    return [
        moneySource?.name,
        moneySource?.description,
        moneySource?.source_name,
        groupName,
        ...(moneySource?.categories ?? []).map(category => category?.name),
    ].some(value => String(value ?? '').toLowerCase().includes(needle));
}

/**
 * Ordnet die (bereits gefilterte und sortierte) Liste als Baum: Gruppen bleiben an ihrer Stelle,
 * ihre Quellen folgen eingerückt direkt darunter. Quellen, deren Gruppe nicht in der Liste ist
 * (z. B. weggefiltert), bleiben auf oberster Ebene.
 *
 * @returns {{source: object, depth: number, childCount: number, orphan: boolean}[]}
 */
export function buildMoneySourceTree(moneySources, collapsedGroupIds = {}) {
    const list = moneySources ?? [];
    const groupIds = new Set(list.filter(source => source?.is_group).map(source => source.id));
    const childrenByGroup = new Map();

    for (const source of list) {
        if (!source?.is_group && source?.group_id != null && groupIds.has(source.group_id)) {
            if (!childrenByGroup.has(source.group_id)) {
                childrenByGroup.set(source.group_id, []);
            }
            childrenByGroup.get(source.group_id).push(source);
        }
    }

    const entries = [];
    for (const source of list) {
        const isNestedChild = !source?.is_group && source?.group_id != null && groupIds.has(source.group_id);
        if (isNestedChild) {
            continue;
        }
        const children = source?.is_group ? (childrenByGroup.get(source.id) ?? []) : [];
        entries.push({
            source,
            depth: 0,
            childCount: children.length,
            orphan: !source?.is_group && source?.group_id != null,
        });
        if (!collapsedGroupIds[source.id]) {
            children.forEach(child => entries.push({source: child, depth: 1, childCount: 0, orphan: false}));
        }
    }
    return entries;
}

/**
 * Kennzahlen über die Liste. Gruppen zählen nicht mit: ihre Summe enthält die Positionen ihrer
 * Quellen bereits, sonst ginge der Verbrauch doppelt ein.
 */
export function summarizeMoneySources(moneySources) {
    const singles = (moneySources ?? []).filter(source => !source?.is_group);
    const volume = singles.reduce((sum, source) => sum + (Number(source?.amount) || 0), 0);
    const remaining = singles.reduce((sum, source) => sum + moneySourceRemainingAmount(source), 0);
    return {
        count: singles.length,
        volume,
        remaining,
        used: volume - remaining,
        usedPercent: moneySourceUsedPercent({amount: volume, sumOfPositions: remaining - volume}),
    };
}

/**
 * Verlauf des verfügbaren Betrags aus den verknüpften Positionen: je Datum (TT.MM.JJJJ) ein Punkt
 * mit dem Stand nach allen Positionen dieses Tages, aufsteigend sortiert.
 *
 * @returns {{date: string, time: number, remaining: number}[]}
 */
export function buildRemainingTimeline(amount, positions) {
    const byDay = new Map();
    for (const position of positions ?? []) {
        const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(String(position?.created_at ?? ''));
        if (!match) {
            continue;
        }
        const time = Date.UTC(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
        const value = parseFloat(String(position?.value ?? '').replace(',', '.')) || 0;
        const signed = position?.type === 'COST' ? -value : value;
        const day = byDay.get(time) ?? {date: position.created_at, time, change: 0};
        day.change += signed;
        byDay.set(time, day);
    }

    let remaining = Number(amount) || 0;
    return [...byDay.values()]
        .sort((a, b) => a.time - b.time)
        .map((day) => {
            remaining += day.change;
            return {date: day.date, time: day.time, remaining};
        });
}
