import test from 'node:test';
import assert from 'node:assert/strict';
import {
    changedFields,
    outdatedErrorFields,
    searchWithTab,
    tabFromSearch,
} from '../../resources/js/Composeables/urlTab.js';

test('tabFromSearch nimmt nur erlaubte Reiter', () => {
    assert.equal(tabFromSearch('?tab=plan', ['plan', 'data'], 'data'), 'plan');
    assert.equal(tabFromSearch('?tab=terms', ['plan', 'data'], 'data'), 'data');
    assert.equal(tabFromSearch('', ['plan', 'data'], 'data'), 'data');
    assert.equal(tabFromSearch(undefined, ['plan'], 'data'), 'data');
});

test('searchWithTab setzt den Reiter und lässt andere Parameter stehen', () => {
    assert.equal(searchWithTab('', 'plan', 'data'), '?tab=plan');
    assert.equal(searchWithTab('?month=3', 'plan', 'data'), '?month=3&tab=plan');
    assert.equal(searchWithTab('?tab=plan&month=3', 'terms', 'data'), '?tab=terms&month=3');
});

test('searchWithTab lässt den Standardreiter weg', () => {
    assert.equal(searchWithTab('?tab=plan', 'data', 'data'), '');
    assert.equal(searchWithTab('?tab=plan&month=3', 'data', 'data'), '?month=3');
});

test('outdatedErrorFields nennt Felder, die seit dem Absenden geändert wurden', () => {
    const errors = {email: 'ungültig', first_name: 'fehlt'};
    const sent = {email: 'kein-at', first_name: ''};

    assert.deepEqual(outdatedErrorFields(errors, sent, {email: 'a@b.de', first_name: ''}), ['email']);
    assert.deepEqual(outdatedErrorFields(errors, sent, sent), []);
    assert.deepEqual(outdatedErrorFields(null, sent, sent), []);
});

test('changedFields vergleicht zwei Stände', () => {
    assert.deepEqual(changedFields({a: 1, b: 2}, {a: 1, b: 3}), ['b']);
    assert.deepEqual(changedFields(undefined, {a: 1}), ['a']);
    assert.deepEqual(changedFields({a: 1}, {a: 1}), []);
});
