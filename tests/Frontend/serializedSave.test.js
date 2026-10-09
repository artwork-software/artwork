import assert from 'node:assert/strict';
import test from 'node:test';
import { createSerializedSaver } from '../../resources/js/Helper/serializedSave.js';

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
}

test('saves run one after another and only the newest queued value is sent afterwards', async () => {
    const sent = [];
    const pending = [];
    const saved = [];
    const intermediate = [];
    const saver = createSerializedSaver({
        send: (value) => {
            sent.push(value);
            const request = deferred();
            pending.push(request);
            return request.promise;
        },
        onSaved: (result) => saved.push(result),
        onIntermediateSaved: (result) => intermediate.push(result),
    });

    const first = saver.save(true);
    saver.save(false);
    saver.save(true);
    assert.equal(saver.isSaving(), true);
    assert.deepEqual(sent, [true]);

    pending[0].resolve('antwort-1');
    await new Promise((resolve) => setImmediate(resolve));
    // Zwischenstand false wird übersprungen, nur der neueste Wert folgt
    assert.deepEqual(sent, [true, true]);
    assert.deepEqual(saved, []);
    assert.deepEqual(intermediate, ['antwort-1']);

    pending[1].resolve('antwort-2');
    await first;
    assert.deepEqual(saved, ['antwort-2']);
    assert.equal(saver.isSaving(), false);
});

test('a failed last save reports the error, a failed intermediate save does not', async () => {
    const failures = [];
    const saved = [];
    let call = 0;
    const saver = createSerializedSaver({
        send: async (value) => {
            call++;
            if (call === 1 || value === 'kaputt') throw new Error('500');
            return value;
        },
        onSaved: (result) => saved.push(result),
        onFailed: (error) => failures.push(error.message),
    });

    const run = saver.save('a');
    saver.save('b');
    await run;
    assert.deepEqual(failures, []);
    assert.deepEqual(saved, ['b']);

    await saver.save('kaputt');
    assert.deepEqual(failures, ['500']);
});

test('a failing last save reports the last successful intermediate result (database state)', async () => {
    // Skript aus dem Review: Checkbox an (ok, DB=true), sofort aus (500) → zurück auf true, nicht auf false
    const db = { checked: false };
    const log = [];
    const ui = { checked: false };
    let call = 0;
    const saver = createSerializedSaver({
        send: async (checked) => {
            call++;
            if (call === 2) throw new Error('500');
            db.checked = checked;
            return { checked };
        },
        onSaved: (result) => { ui.checked = result.checked; },
        onIntermediateSaved: (result) => log.push(`intermediate ${result.checked}`),
        onFailed: (error, lastSuccess) => {
            log.push(`failed ${error.message}`);
            ui.checked = lastSuccess?.checked ?? false;
        },
    });

    ui.checked = true;
    const run = saver.save(true);
    ui.checked = false;
    saver.save(false);
    await run;

    assert.deepEqual(db, { checked: true });
    assert.deepEqual(log, ['intermediate true', 'failed 500']);
    assert.equal(ui.checked, db.checked);
});
