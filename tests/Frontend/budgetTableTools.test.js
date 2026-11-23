import test from 'node:test';
import assert from 'node:assert/strict';
import {
    budgetCellAmount,
    budgetCellRange,
    findBudgetSearchMatches,
    summarizeBudgetSelection,
} from '../../resources/js/Layouts/Components/Budget/budgetTableTools.js';

const cell = (id, value, extra = {}) => ({id, value, column: {type: 'empty'}, ...extra});
const row = (id, kto, kst, name, ...values) => ({
    id,
    cells: [cell(id * 100, kto), cell(id * 100 + 1, kst), cell(id * 100 + 2, name), ...values.map((v, i) => cell(id * 100 + 3 + i, v))],
});

const mainPositions = [
    {
        id: 2, type: 'BUDGET_TYPE_EARNING', name: 'Einnahmen Gastspiel',
        sub_positions: [{id: 20, name: 'Tickets', sub_position_rows: [row(3, '8000', '100', 'Kartenverkauf', '500,00')]}],
    },
    {
        id: 1, type: 'BUDGET_TYPE_COST', name: 'Gesamtkosten Produktion',
        sub_positions: [{
            id: 10, name: 'Gagen',
            sub_position_rows: [row(1, '3005', '50800', 'Regie', '850,00', '800,00'), row(2, '3060', '20700', 'Reisekosten', '1200,50', '10,25')],
        }],
    },
];

test('amounts are parsed from decimal comma text and sage numbers', () => {
    assert.equal(budgetCellAmount(cell(1, '1200,50')), 1200.5);
    assert.equal(budgetCellAmount(cell(1, '-32,00')), -32);
    assert.equal(budgetCellAmount(cell(1, '')), 0);
    assert.equal(budgetCellAmount(cell(1, null)), 0);
    assert.equal(budgetCellAmount(cell(1, 'Regie')), 0);
    assert.equal(budgetCellAmount({id: 1, value: '5,00', sage_value: 99.5, column: {type: 'sage'}}), 99.5);
});

test('search finds position names and the first three cells, costs before earnings', () => {
    assert.deepEqual(findBudgetSearchMatches(mainPositions, ''), []);
    assert.deepEqual(findBudgetSearchMatches(mainPositions, '   '), []);

    assert.deepEqual(
        findBudgetSearchMatches(mainPositions, 'REISE').map(match => `${match.type}:${match.id}`),
        ['row:2']
    );
    assert.deepEqual(
        findBudgetSearchMatches(mainPositions, 'ga').map(match => `${match.type}:${match.id}`),
        ['sub:10', 'main:2']
    );
    assert.deepEqual(
        findBudgetSearchMatches(mainPositions, 'kosten').map(match => `${match.type}:${match.id}`),
        ['main:1', 'row:2']
    );
    // KTO/KST werden gefunden, Beträge nicht
    assert.equal(findBudgetSearchMatches(mainPositions, '50800').length, 1);
    assert.equal(findBudgetSearchMatches(mainPositions, '850').length, 0);

    const [match] = findBudgetSearchMatches(mainPositions, 'karten');
    assert.deepEqual(match, {type: 'row', id: 3, mainPositionId: 2, subPositionId: 20, section: 'earning'});
});

test('search also matches the displayed account label', () => {
    const withDisplayValue = [{
        id: 1, type: 'BUDGET_TYPE_COST', name: 'A',
        sub_positions: [{id: 10, name: 'B', sub_position_rows: [{id: 5, cells: [cell(1, '3005', {display_value: '3005 – Honorare'})]}]}],
    }];
    assert.equal(findBudgetSearchMatches(withDisplayValue, 'honorar').length, 1);
});

test('range selection covers the rectangle and skips non-selectable cells', () => {
    const rows = mainPositions[1].sub_positions[0].sub_position_rows;
    const valueOnly = (c, index) => index > 2;

    assert.deepEqual(budgetCellRange(rows, 103, 204, valueOnly), [103, 104, 203, 204]);
    assert.deepEqual(budgetCellRange(rows, 204, 103, valueOnly), [103, 104, 203, 204]);
    assert.deepEqual(budgetCellRange(rows, 102, 203, valueOnly), [103, 203]);
    // Anker liegt in einer anderen Unterposition: nur die Zielzelle
    assert.deepEqual(budgetCellRange(rows, 999, 203, valueOnly), [203]);
});

test('selection summary adds up the selected cells', () => {
    assert.deepEqual(
        summarizeBudgetSelection(mainPositions, {103: true, 203: true, 303: true}),
        {count: 3, sum: 2550.5, average: 2550.5 / 3}
    );
    assert.deepEqual(summarizeBudgetSelection(mainPositions, {}), {count: 0, sum: 0, average: 0});
});
