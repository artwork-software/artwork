/**
 * Gewählten Reiter in der Adresse führen (?tab=…), damit er Neuladen und geteilte Links übersteht.
 */

/**
 * @param {string} search location.search
 * @param {string[]} allowedKeys Reiter, die die Person sehen darf
 * @param {string} fallback
 * @returns {string}
 */
export function tabFromSearch(search, allowedKeys, fallback) {
    const wanted = new URLSearchParams(search ?? '').get('tab');

    return wanted && allowedKeys.includes(wanted) ? wanted : fallback;
}

/**
 * Query-String mit dem gewählten Reiter; der Standardreiter steht nicht in der Adresse.
 * Andere Parameter bleiben erhalten.
 *
 * @param {string} search location.search
 * @param {string} key
 * @param {string} defaultKey
 * @returns {string} mit führendem "?" oder leer
 */
export function searchWithTab(search, key, defaultKey) {
    const params = new URLSearchParams(search ?? '');
    if (key === defaultKey) {
        params.delete('tab');
    } else {
        params.set('tab', key);
    }
    const query = params.toString();

    return query === '' ? '' : `?${query}`;
}

/**
 * Für Formulare, die beim Verlassen eines Feldes speichern: Felder mit Fehlermeldung, deren Wert sich
 * seit dem Absenden geändert hat. Ihre Meldung gilt nicht mehr.
 *
 * @param {Record<string, unknown>} errors
 * @param {Record<string, unknown>} sent abgesendeter Stand
 * @param {Record<string, unknown>} current aktueller Stand
 * @returns {string[]}
 */
export function outdatedErrorFields(errors, sent, current) {
    return Object.keys(errors ?? {}).filter((field) => sent?.[field] !== current?.[field]);
}

/**
 * @param {Record<string, unknown>} before
 * @param {Record<string, unknown>} now
 * @returns {string[]}
 */
export function changedFields(before, now) {
    return Object.keys(now ?? {}).filter((field) => now[field] !== before?.[field]);
}
