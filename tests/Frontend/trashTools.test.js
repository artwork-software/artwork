import test from 'node:test';
import assert from 'node:assert/strict';
import {
    bulkOutcome,
    daysLeftText,
    isExpiringSoon,
    pruneKeys,
    selectionState,
    toggleAllKeys,
    toggleKey,
    xsrfTokenFromCookie,
} from '../../resources/js/Pages/Trash/Composables/trashTools.js';

test('toggleKey fügt hinzu und entfernt', () => {
    assert.deepEqual(toggleKey([], 3), [3]);
    assert.deepEqual(toggleKey([3, 4], 3), [4]);
    assert.deepEqual(toggleKey(undefined, 'genres-1'), ['genres-1']);
});

test('toggleAllKeys wählt alle oder hebt die Auswahl auf', () => {
    assert.deepEqual(toggleAllKeys([1], [1, 2, 3]).sort(), [1, 2, 3]);
    assert.deepEqual(toggleAllKeys([1, 2, 3, 9], [1, 2, 3]), [9]);
    assert.deepEqual(toggleAllKeys([5], []), [5]);
});

test('selectionState unterscheidet keine, einige und alle', () => {
    assert.equal(selectionState([], [1, 2]), 'none');
    assert.equal(selectionState([2], [1, 2]), 'some');
    assert.equal(selectionState([1, 2, 7], [1, 2]), 'all');
});

test('pruneKeys entfernt verschwundene Einträge', () => {
    assert.deepEqual(pruneKeys([1, 2, 3], [2, 3, 4]), [2, 3]);
    assert.deepEqual(pruneKeys(undefined, [1]), []);
});

test('bulkOutcome zählt als erledigt, was nicht mehr in der Liste steht', () => {
    assert.deepEqual(bulkOutcome([1, 2, 3], [3, 4]), {done: 2, failed: 1, failedKeys: [3]});
    assert.deepEqual(bulkOutcome([1, 2], []), {done: 2, failed: 0, failedKeys: []});
    assert.deepEqual(bulkOutcome([], [1]), {done: 0, failed: 0, failedKeys: []});
});

test('daysLeftText wählt den passenden Text', () => {
    assert.equal(daysLeftText(null), null);
    assert.equal(daysLeftText(undefined), null);
    assert.equal(daysLeftText(0).key, 'Will be deleted automatically with the next cleanup');
    assert.equal(daysLeftText(1).key, 'Will be deleted automatically in 1 day');
    assert.deepEqual(daysLeftText(12), {key: 'Will be deleted automatically in {count} days', params: {count: 12}});
});

test('isExpiringSoon markiert die letzten drei Tage', () => {
    assert.equal(isExpiringSoon(null), false);
    assert.equal(isExpiringSoon(4), false);
    assert.equal(isExpiringSoon(3), true);
    assert.equal(isExpiringSoon(0), true);
});

test('xsrfTokenFromCookie liest und dekodiert das Cookie', () => {
    assert.equal(xsrfTokenFromCookie('a=1; XSRF-TOKEN=abc%3D%3D; b=2'), 'abc==');
    assert.equal(xsrfTokenFromCookie('a=1'), '');
    assert.equal(xsrfTokenFromCookie(''), '');
});
