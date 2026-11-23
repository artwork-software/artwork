/**
 * Reine Hilfslogik des Papierkorbs: Auswahl, Ergebnis einer Sammelaktion, Text zur Restfrist.
 */

/**
 * @param {Array<string|number>} selectedKeys
 * @param {string|number} key
 * @returns {Array<string|number>}
 */
export function toggleKey(selectedKeys, key) {
    const selected = selectedKeys ?? [];

    return selected.includes(key) ? selected.filter((entry) => entry !== key) : [...selected, key];
}

/**
 * Sind alle Einträge gewählt, werden sie abgewählt, sonst alle gewählt.
 *
 * @param {Array<string|number>} selectedKeys
 * @param {Array<string|number>} keys
 * @returns {Array<string|number>}
 */
export function toggleAllKeys(selectedKeys, keys) {
    const selected = new Set(selectedKeys ?? []);
    const allSelected = keys.length > 0 && keys.every((key) => selected.has(key));
    keys.forEach((key) => (allSelected ? selected.delete(key) : selected.add(key)));

    return [...selected];
}

/**
 * @param {Array<string|number>} selectedKeys
 * @param {Array<string|number>} keys
 * @returns {'none'|'some'|'all'}
 */
export function selectionState(selectedKeys, keys) {
    const hits = keys.filter((key) => (selectedKeys ?? []).includes(key)).length;
    if (hits === 0) {
        return 'none';
    }

    return hits === keys.length ? 'all' : 'some';
}

/**
 * Auswahl ohne Einträge, die nicht mehr in der Liste stehen.
 *
 * @param {Array<string|number>} selectedKeys
 * @param {Array<string|number>} keys
 * @returns {Array<string|number>}
 */
export function pruneKeys(selectedKeys, keys) {
    const existing = new Set(keys);

    return (selectedKeys ?? []).filter((key) => existing.has(key));
}

/**
 * Ergebnis einer Sammelaktion: erledigt ist, was danach nicht mehr in der Liste steht.
 *
 * @param {Array<string|number>} processedKeys
 * @param {Array<string|number>} remainingKeys Einträge der Liste nach dem Neuladen
 * @returns {{done: number, failed: number, failedKeys: Array<string|number>}}
 */
export function bulkOutcome(processedKeys, remainingKeys) {
    const remaining = new Set(remainingKeys);
    const failedKeys = (processedKeys ?? []).filter((key) => remaining.has(key));

    return {
        done: (processedKeys ?? []).length - failedKeys.length,
        failed: failedKeys.length,
        failedKeys,
    };
}

/**
 * Übersetzungsschlüssel und Parameter für die Restfrist bis zur automatischen Löschung.
 *
 * @param {number|null|undefined} daysLeft
 * @returns {{key: string, params: object}|null}
 */
export function daysLeftText(daysLeft) {
    if (daysLeft === null || daysLeft === undefined) {
        return null;
    }
    if (daysLeft <= 0) {
        return {key: 'Will be deleted automatically with the next cleanup', params: {}};
    }
    if (daysLeft === 1) {
        return {key: 'Will be deleted automatically in 1 day', params: {}};
    }

    return {key: 'Will be deleted automatically in {count} days', params: {count: daysLeft}};
}

/**
 * @param {number|null|undefined} daysLeft
 * @returns {boolean}
 */
export function isExpiringSoon(daysLeft) {
    return daysLeft !== null && daysLeft !== undefined && daysLeft <= 3;
}

/**
 * Wert des XSRF-Cookies für den Header X-XSRF-TOKEN.
 *
 * @param {string} cookieString document.cookie
 * @returns {string}
 */
export function xsrfTokenFromCookie(cookieString) {
    const entry = String(cookieString ?? '')
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    return entry ? decodeURIComponent(entry.slice('XSRF-TOKEN='.length)) : '';
}
