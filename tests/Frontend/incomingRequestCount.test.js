import test from 'node:test';
import assert from 'node:assert/strict';

// die Datei importiert axios und ruft route() erst beim Laden auf; hier zählt nur die reine Altersprüfung
const { isCountStale } = await import('../../resources/js/Composeables/incomingRequestCount.js');

test('isCountStale: nie geladen heißt laden', () => {
    assert.equal(isCountStale(0, 1_000), true);
});

test('isCountStale: innerhalb der Frist wird nicht neu geladen', () => {
    assert.equal(isCountStale(1_000, 30_000), false);
    assert.equal(isCountStale(1_000, 61_000), true);
});

test('isCountStale: force lädt immer', () => {
    assert.equal(isCountStale(1_000, 2_000, true), true);
});

test('isCountStale: eigene Frist', () => {
    assert.equal(isCountStale(1_000, 1_600, false, 500), true);
    assert.equal(isCountStale(1_000, 1_400, false, 500), false);
});
