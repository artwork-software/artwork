/**
 * Suchfelder der ArtworkBaseListbox: ausdrückliche searchKeys > optionLabel (Feldname ODER Funktion) > name/id.
 * Ohne den Funktions-Fall fand z. B. der Filter „Zuständig“ (Label = Vor- und Nachname) niemanden per Namen.
 */
export function resolveListboxSearchKeys(searchKeys, optionLabel) {
    if (searchKeys?.length) {
        return searchKeys;
    }
    if (typeof optionLabel === 'string' || typeof optionLabel === 'function') {
        return [optionLabel];
    }
    return ['name', 'id'];
}

export function normalizeSearchText(value) {
    return String(value ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');
}

export function listboxItemMatches(item, query, keys) {
    const normalizedQuery = normalizeSearchText(query);
    if (!normalizedQuery) {
        return true;
    }
    return keys.some((key) => {
        const value = typeof key === 'function' ? key(item) : item?.[key];
        return normalizeSearchText(value).includes(normalizedQuery);
    });
}
