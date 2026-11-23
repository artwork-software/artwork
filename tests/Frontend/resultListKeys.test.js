import test from 'node:test';
import assert from 'node:assert/strict';
import { resultListKey } from '../../resources/js/Composeables/resultListKeys.js';

test('Pfeil runter markiert zuerst den ersten Treffer und geht dann weiter', () => {
    assert.deepEqual(resultListKey('ArrowDown', -1, 3), { action: 'move', index: 0 });
    assert.deepEqual(resultListKey('ArrowDown', 0, 3), { action: 'move', index: 1 });
});

test('Pfeil runter beginnt am Ende wieder vorn', () => {
    assert.deepEqual(resultListKey('ArrowDown', 2, 3), { action: 'move', index: 0 });
});

test('Pfeil hoch springt ohne Markierung und vom ersten Treffer ans Ende', () => {
    assert.deepEqual(resultListKey('ArrowUp', -1, 3), { action: 'move', index: 2 });
    assert.deepEqual(resultListKey('ArrowUp', 0, 3), { action: 'move', index: 2 });
    assert.deepEqual(resultListKey('ArrowUp', 2, 3), { action: 'move', index: 1 });
});

test('Enter wählt den markierten Treffer, ohne Markierung den ersten', () => {
    assert.deepEqual(resultListKey('Enter', 1, 3), { action: 'select', index: 1 });
    assert.deepEqual(resultListKey('Enter', -1, 3), { action: 'select', index: 0 });
});

test('ohne Treffer bleiben Pfeile und Enter unbehandelt', () => {
    assert.equal(resultListKey('ArrowDown', -1, 0), null);
    assert.equal(resultListKey('ArrowUp', -1, 0), null);
    assert.equal(resultListKey('Enter', -1, 0), null);
});

test('Escape schließt immer, auch ohne Treffer', () => {
    assert.deepEqual(resultListKey('Escape', 1, 3), { action: 'close', index: -1 });
    assert.deepEqual(resultListKey('Escape', -1, 0), { action: 'close', index: -1 });
});

test('eine veraltete Markierung jenseits der Liste zählt als keine', () => {
    assert.deepEqual(resultListKey('ArrowDown', 7, 3), { action: 'move', index: 0 });
    assert.deepEqual(resultListKey('Enter', 7, 3), { action: 'select', index: 0 });
});

test('andere Tasten bleiben unbehandelt', () => {
    assert.equal(resultListKey('a', 0, 3), null);
    assert.equal(resultListKey('Tab', 0, 3), null);
});
