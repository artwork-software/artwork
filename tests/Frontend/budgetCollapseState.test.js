import test from 'node:test';
import assert from 'node:assert/strict';
import {register} from 'node:module';

register('./support/viteAliasHooks.mjs', import.meta.url);

const data = new Map();
globalThis.window = {
    localStorage: {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, String(value)),
        removeItem: (key) => data.delete(key),
    },
};

// Altbestand im bisherigen Speicherformat, bevor das Modul ihn zum ersten Mal liest
data.set('closedMainPositions:7', JSON.stringify([{id: 1, closed: true}, {id: 2, closed: false}]));
data.set('closedSubPositions:7', 'kein json');

const {
    isBudgetEntryClosed,
    setAllBudgetEntriesClosed,
    setBudgetEntryClosed,
    toggleBudgetEntryClosed,
} = await import('../../resources/js/Layouts/Components/Budget/budgetCollapseState.js');

test('reads the previously stored format and keeps only collapsed entries', () => {
    assert.equal(isBudgetEntryClosed('main', 7, 1), true);
    assert.equal(isBudgetEntryClosed('main', 7, 2), false);
    assert.equal(isBudgetEntryClosed('main', 7, 99), false);
});

test('unreadable stored data counts as everything expanded', () => {
    assert.equal(isBudgetEntryClosed('sub', 7, 1), false);
});

test('toggling persists per table and does not leak into other tables', () => {
    toggleBudgetEntryClosed('main', 7, 2);
    assert.equal(isBudgetEntryClosed('main', 7, 2), true);
    assert.equal(isBudgetEntryClosed('main', 8, 2), false);
    assert.deepEqual(
        JSON.parse(data.get('closedMainPositions:7')),
        [{id: 1, closed: true}, {id: 2, closed: true}]
    );

    toggleBudgetEntryClosed('main', 7, 2);
    assert.equal(isBudgetEntryClosed('main', 7, 2), false);
    assert.deepEqual(JSON.parse(data.get('closedMainPositions:7')), [{id: 1, closed: true}]);
});

test('collapse all and expand all set every given entry', () => {
    setAllBudgetEntriesClosed('main', 9, [1, 2, 3], true);
    assert.deepEqual([1, 2, 3].map(id => isBudgetEntryClosed('main', 9, id)), [true, true, true]);

    setAllBudgetEntriesClosed('main', 9, [1, 2, 3], false);
    assert.deepEqual([1, 2, 3].map(id => isBudgetEntryClosed('main', 9, id)), [false, false, false]);
    assert.deepEqual(JSON.parse(data.get('closedMainPositions:9')), []);
});

test('sections use their own key and string ids', () => {
    setBudgetEntryClosed('section', 7, 'cost', true);
    assert.equal(isBudgetEntryClosed('section', 7, 'cost'), true);
    assert.equal(isBudgetEntryClosed('section', 7, 'earning'), false);
    assert.deepEqual(JSON.parse(data.get('closedBudgetSections:7')), [{id: 'cost', closed: true}]);
});
