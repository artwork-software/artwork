import assert from 'node:assert/strict';
import { register } from 'node:module';
import test from 'node:test';

register('./support/viteAliasHooks.mjs', import.meta.url);

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
}

const flush = () => new Promise((resolve) => setImmediate(resolve));

async function setup({ stored = 'alt', accept = () => true } = {}) {
    const { createComponentTextSave } = await import('../../resources/js/Composeables/componentTextSave.js');
    const state = { stored, sent: [], requests: [], savedValues: [] };
    const text = { value: stored };
    const isEditing = { value: false };
    const dataListener = {
        saved: (value) => {
            state.savedValues.push(value);
            if (!accept(value)) return false;
            state.stored = value.data.text;
            return true;
        },
    };
    const textSave = createComponentTextSave({
        text,
        isEditing,
        getStoredText: () => state.stored,
        dataListener,
        send: (value) => {
            state.sent.push(value);
            const request = deferred();
            state.requests.push(request);
            return request.promise;
        },
    });

    return { textSave, text, isEditing, state };
}

const response = (value) => ({ data: { project_value: { data: { text: value } } } });

test('nothing is sent without a change, parallel edits are sent one after another', async () => {
    const { textSave, text, state } = await setup();

    assert.equal(textSave.submit(), false);
    assert.deepEqual(state.sent, []);

    text.value = 'A';
    assert.equal(textSave.submit(), true);
    text.value = 'B';
    textSave.submit();
    assert.deepEqual(state.sent, ['A']);

    state.requests[0].resolve(response('A'));
    await flush();
    assert.deepEqual(state.sent, ['A', 'B']);
    state.requests[1].resolve(response('B'));
    await flush();

    assert.equal(state.stored, 'B');
    assert.equal(text.value, 'B');
});

test('an own answer older than a foreign value shows the foreign value', async () => {
    const { textSave, text, state } = await setup({ accept: () => false });
    state.stored = 'fremd';

    text.value = 'eigen';
    textSave.submit();
    state.requests[0].resolve(response('eigen'));
    await flush();

    assert.equal(text.value, 'fremd');
    assert.equal(textSave.submit(), false);
});

test('typing back to the old text after a skipped live update shows the stored value', async () => {
    const { textSave, text, isEditing, state } = await setup();
    isEditing.value = true;
    text.value = 'tippe';
    state.stored = 'fremd';
    textSave.syncFromStored();
    assert.equal(text.value, 'tippe');

    text.value = 'alt';
    isEditing.value = false;
    assert.equal(textSave.submit(), false);
    assert.equal(text.value, 'fremd');
});

test('a failed save keeps the input and retries on the next submit', async () => {
    const { textSave, text, state } = await setup();
    const originalError = console.error;
    console.error = () => {};
    try {
        text.value = 'neu';
        textSave.submit();
        state.requests[0].reject(new Error('500'));
        await flush();
    } finally {
        console.error = originalError;
    }

    assert.equal(text.value, 'neu');
    assert.equal(textSave.submit(), true);
    assert.deepEqual(state.sent, ['neu', 'neu']);
});

test('a failed input is not replaced by a live update until it is saved', async () => {
    const { textSave, text, state } = await setup();
    const originalError = console.error;
    console.error = () => {};
    try {
        text.value = 'neu';
        textSave.submit();
        state.requests[0].reject(new Error('500'));
        await flush();
    } finally {
        console.error = originalError;
    }

    state.stored = 'fremd';
    textSave.syncFromStored();
    assert.equal(text.value, 'neu');

    textSave.submit();
    state.requests[1].resolve(response('neu'));
    await flush();
    state.stored = 'fremd2';
    textSave.syncFromStored();
    assert.equal(text.value, 'fremd2');
});
