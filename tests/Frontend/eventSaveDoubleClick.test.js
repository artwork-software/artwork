import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/Layouts/Components/EventComponent.vue', import.meta.url), 'utf8');

const functionBody = (name) => {
    const start = source.indexOf(`async function ${name}(`);
    assert.ok(start > -1, `${name} missing`);
    return source.slice(start, source.indexOf('\n}\n', start));
};

test('a second click while saving does not start a second save', () => {
    assert.match(functionBody('updateOrCreateEvent'), /^\s+if \(isLoading\.value\) return$/m);
    assert.match(functionBody('doSaveEvent'), /^\s+if \(isLoading\.value\) return$/m);
});

test('the save is locked before the first await of doSaveEvent', () => {
    const body = functionBody('doSaveEvent');
    const lock = body.indexOf('isLoading.value = true');
    const firstAwait = body.indexOf('await ensureDescriptionLoaded()');

    assert.ok(lock > -1, 'doSaveEvent must set isLoading');
    assert.ok(lock < firstAwait, 'isLoading must be set before the description is awaited');
});
