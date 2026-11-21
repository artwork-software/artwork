import test from 'node:test';
import assert from 'node:assert/strict';
import {
    INVENTORY_SORT_OPTIONS,
    findInventorySortOption,
    inventoryExportQuery,
    inventoryHitCount,
    overbookedArticleIds,
    sanitizeBasketQuantity,
} from '../../resources/js/Pages/Inventory/Composables/inventoryListTools.js';

test('Sortier-Option folgt den Adress-Parametern und fällt auf den Standard zurück', () => {
    assert.equal(findInventorySortOption(null, null).id, 'default');
    assert.equal(findInventorySortOption('name', 'desc').id, 'name-desc');
    assert.equal(findInventorySortOption('name', 'quer').id, 'name-asc');
    // nur eine Richtung vorhanden: die vorhandene Option statt des Standards
    assert.equal(findInventorySortOption('created_at', 'asc').id, 'created_at-desc');
    assert.equal(findInventorySortOption('description', 'asc').id, 'default');
    assert.equal(new Set(INVENTORY_SORT_OPTIONS.map((option) => option.id)).size, INVENTORY_SORT_OPTIONS.length);
});

test('Trefferanzeige erkennt eine eingeschränkte Liste', () => {
    assert.deepEqual(inventoryHitCount(138, 138), { total: 138, all: 138, filtered: false });
    assert.deepEqual(inventoryHitCount(23, 138), { total: 23, all: 138, filtered: true });
    // Gesamtzahl aus dem Cache kann kurz hinterherhinken: nie "140 von 138"
    assert.deepEqual(inventoryHitCount(140, 138), { total: 140, all: 140, filtered: false });
    assert.deepEqual(inventoryHitCount(undefined, undefined), { total: 0, all: 0, filtered: false });
});

test('Menge je Klick ist eine ganze Zahl ab 1', () => {
    assert.equal(sanitizeBasketQuantity('5'), 5);
    assert.equal(sanitizeBasketQuantity(2.9), 2);
    assert.equal(sanitizeBasketQuantity(0), 1);
    assert.equal(sanitizeBasketQuantity(-3), 1);
    assert.equal(sanitizeBasketQuantity(''), 1);
    assert.equal(sanitizeBasketQuantity('abc'), 1);
    assert.equal(sanitizeBasketQuantity(123456), 9999);
});

test('Export übernimmt die Filter der Liste', () => {
    assert.deepEqual(
        inventoryExportQuery({
            categoryId: 4,
            subCategoryId: 9,
            search: 'Podest',
            statusId: 2,
            filters: [{ property_id: 1, operator: 'like', value: 'Holz' }],
            tagIds: [7],
            sort: 'quantity',
            direction: 'desc',
        }),
        {
            sort: 'quantity',
            direction: 'desc',
            category_id: 4,
            sub_category_id: 9,
            search: 'Podest',
            status_id: 2,
            filters: '[{"property_id":1,"operator":"like","value":"Holz"}]',
            tag_ids: [7],
        }
    );
    assert.deepEqual(inventoryExportQuery(), { filters: '[]' });
});

test('Export einer Auswahl ignoriert Filter und Kategorie', () => {
    assert.deepEqual(
        inventoryExportQuery({ selectedIds: [3, 5], categoryId: 4, search: 'Podest', tagIds: [7], sort: 'name' }),
        { sort: 'name', direction: 'asc', ids: '3,5' }
    );
});

test('Überbuchte Artikel im sichtbaren Zeitraum', () => {
    const availability = {
        base: { 1: 5, 2: -1, 3: -2, 4: 0 },
        deltas: {
            '2026-11-19': { 1: -3, 3: 1 },
            '2026-11-20': { 3: 4, 4: -1 },
            '2026-12-24': { 1: -9 },
        },
    };
    const dates = [{ date: '2026-11-19' }, { date: '2026-11-20' }];

    // 1: an einem Tag negativ; 2: negativer Basiswert ohne Deltas; 4: an einem Tag negativ;
    // 3: Basiswert negativ, aber an jedem sichtbaren Tag durch ein Delta überdeckt
    assert.deepEqual([...overbookedArticleIds(availability, dates)].sort(), [1, 2, 4]);
    assert.deepEqual([...overbookedArticleIds(availability, ['2026-11-21'])].sort(), [2, 3]);
    assert.equal(overbookedArticleIds(null, dates).size, 0);
    assert.equal(overbookedArticleIds(availability, []).size, 0);
});
