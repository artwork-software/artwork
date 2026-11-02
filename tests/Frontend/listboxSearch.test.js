import test from 'node:test';
import assert from 'node:assert/strict';
import {listboxItemMatches, resolveListboxSearchKeys} from '../../resources/js/Helper/listboxSearch.js';

const displayUserName = (user) => `${user.first_name} ${user.last_name}`;
const users = [
    {id: 1, first_name: 'Anna', last_name: 'Müller'},
    {id: 2, first_name: 'Jörg', last_name: 'Schmidt'},
];

test('function option label is searched when no search keys are given', () => {
    const keys = resolveListboxSearchKeys([], displayUserName);

    assert.deepEqual(users.filter((u) => listboxItemMatches(u, 'muller', keys)).map((u) => u.id), [1]);
    assert.deepEqual(users.filter((u) => listboxItemMatches(u, 'Jörg Sch', keys)).map((u) => u.id), [2]);
});

test('explicit search keys win over the option label', () => {
    const keys = resolveListboxSearchKeys(['last_name'], displayUserName);

    assert.equal(listboxItemMatches(users[0], 'anna', keys), false);
    assert.equal(listboxItemMatches(users[0], 'müll', keys), true);
});

test('string option label and name/id fallback', () => {
    assert.deepEqual(resolveListboxSearchKeys([], 'title'), ['title']);
    assert.deepEqual(resolveListboxSearchKeys(undefined, undefined), ['name', 'id']);
});

test('empty query matches everything', () => {
    assert.equal(listboxItemMatches(users[1], '', ['name']), true);
});
