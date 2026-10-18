import test from 'node:test';
import assert from 'node:assert/strict';
import {createSaveSnapshot, createQueuedSave} from '../../resources/js/Helper/saveSnapshot.js';

test('unchanged form has no changes', () => {
    const form = {a: 1, b: 2};
    const snapshot = createSaveSnapshot(() => ({...form}));

    assert.equal(snapshot.hasChanges(), false);
    form.a = 3;
    assert.equal(snapshot.hasChanges(), true);
});

test('input typed while a save is running still counts as unsaved', () => {
    const form = {a: 1, b: 2};
    const snapshot = createSaveSnapshot(() => ({...form}));

    form.a = 10;
    const sent = snapshot.capture();
    // the person types into the next field before the response arrives
    form.b = 20;
    snapshot.markSaved(sent);

    assert.equal(snapshot.hasChanges(), true);
    snapshot.markSaved(snapshot.capture());
    assert.equal(snapshot.hasChanges(), false);
});

test('saves requested while one is running are queued once and run afterwards', () => {
    const started = [];
    const finishers = [];
    const save = createQueuedSave((done) => {
        started.push(started.length + 1);
        finishers.push(done);
    });

    save();
    save();
    save();
    assert.deepEqual(started, [1], 'no second request while the first runs');

    finishers[0]();
    assert.deepEqual(started, [1, 2], 'one queued save follows');

    finishers[1]();
    assert.deepEqual(started, [1, 2]);
});
