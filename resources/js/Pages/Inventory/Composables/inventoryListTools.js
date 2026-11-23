/**
 * Reine Hilfsfunktionen der Artikelübersicht (Sortierung, Trefferzahl, Export-Abfrage,
 * Warenkorb-Menge), ohne Vue.
 */

/** Sortierungen der Artikelübersicht; `sort: null` ist die Standardreihenfolge nach Kategorie. */
export const INVENTORY_SORT_OPTIONS = [
    { id: 'default', sort: null, direction: null, name: 'By category' },
    { id: 'name-asc', sort: 'name', direction: 'asc', name: 'Name (A–Z)' },
    { id: 'name-desc', sort: 'name', direction: 'desc', name: 'Name (Z–A)' },
    { id: 'quantity-desc', sort: 'quantity', direction: 'desc', name: 'Quantity (highest first)' },
    { id: 'quantity-asc', sort: 'quantity', direction: 'asc', name: 'Quantity (lowest first)' },
    { id: 'inventory_number-asc', sort: 'inventory_number', direction: 'asc', name: 'Inventory number' },
    { id: 'created_at-desc', sort: 'created_at', direction: 'desc', name: 'Newest first' },
];

/** Passende Sortier-Option zu den Adress-Parametern; Unbekanntes fällt auf den Standard zurück. */
export function findInventorySortOption(sort, direction) {
    if (!sort) {
        return INVENTORY_SORT_OPTIONS[0];
    }
    const wanted = direction === 'desc' ? 'desc' : 'asc';
    return INVENTORY_SORT_OPTIONS.find((option) => option.sort === sort && option.direction === wanted)
        ?? INVENTORY_SORT_OPTIONS.find((option) => option.sort === sort)
        ?? INVENTORY_SORT_OPTIONS[0];
}

/**
 * Zahlen für die Trefferanzeige: `filtered` ist true, sobald die Liste weniger zeigt als das
 * gesamte Inventar (Suche, Filter, Status oder Kategorie).
 */
export function inventoryHitCount(matching, overall) {
    const total = Math.max(0, Number(matching) || 0);
    const all = Math.max(total, Number(overall) || 0);
    return { total, all, filtered: total < all };
}

/** Menge je Klick im Warenkorb-Modus: ganze Zahl, mindestens 1, höchstens 9999. */
export function sanitizeBasketQuantity(value) {
    const quantity = Math.floor(Number(value));
    if (!Number.isFinite(quantity) || quantity < 1) {
        return 1;
    }
    return Math.min(quantity, 9999);
}

/**
 * Abfrage für den Excel-Export: dieselben Filter wie die Liste. Mit Auswahl zählt nur die Auswahl,
 * Filter und Kategorie bleiben dann weg.
 */
export function inventoryExportQuery({
    selectedIds = [],
    categoryId = null,
    subCategoryId = null,
    search = '',
    searchPropertyId = null,
    statusId = null,
    filters = [],
    tagIds = [],
    presetId = null,
    sort = null,
    direction = null,
} = {}) {
    const query = {};
    if (sort) {
        query.sort = sort;
        query.direction = direction === 'desc' ? 'desc' : 'asc';
    }
    if (selectedIds.length > 0) {
        return { ...query, ids: selectedIds.join(',') };
    }
    if (categoryId) query.category_id = categoryId;
    if (subCategoryId) query.sub_category_id = subCategoryId;
    if ((search ?? '').trim() !== '') query.search = search;
    if (searchPropertyId) query.search_property_id = searchPropertyId;
    if (statusId) query.status_id = statusId;
    query.filters = JSON.stringify(filters ?? []);
    if ((tagIds ?? []).length > 0) query.tag_ids = tagIds;
    if (presetId) query.filter_preset_id = presetId;
    return query;
}

/**
 * Artikelplanung: IDs der Artikel, die im sichtbaren Zeitraum an mindestens einem Tag überbucht
 * sind (verfügbare Menge < 0). `availability` hat die kompakte Form
 * `{ base: { artikelId: menge }, deltas: { datum: { artikelId: menge } } }`; ein Tag ohne
 * Delta-Eintrag zeigt den Basiswert.
 */
export function overbookedArticleIds(availability, dates) {
    const base = availability?.base ?? {};
    const deltas = availability?.deltas ?? {};
    const dateKeys = (dates ?? []).map((date) => (typeof date === 'string' ? date : date?.date)).filter(Boolean);
    const overbooked = new Set();
    const daysWithDelta = {};

    for (const date of dateKeys) {
        for (const [articleId, value] of Object.entries(deltas[date] ?? {})) {
            daysWithDelta[articleId] = (daysWithDelta[articleId] ?? 0) + 1;
            if (Number(value) < 0) {
                overbooked.add(Number(articleId));
            }
        }
    }
    for (const [articleId, value] of Object.entries(base)) {
        // negativer Basiswert zählt nur, wenn ihn mindestens ein sichtbarer Tag tatsächlich zeigt
        if (Number(value) < 0 && (daysWithDelta[articleId] ?? 0) < dateKeys.length) {
            overbooked.add(Number(articleId));
        }
    }
    return overbooked;
}
