import assert from 'node:assert/strict';
import test from 'node:test';

import { followStartDate } from '../../resources/js/Helper/followStartDate.js';

test('an empty end date jumps to the new start date', () => {
    assert.equal(followStartDate('2026-11-03', '', ''), '2026-11-03');
    assert.equal(followStartDate('2026-11-03', null, null), '2026-11-03');
});

test('a single-day entry moves along with the start date', () => {
    assert.equal(followStartDate('2026-11-05', '2026-11-03', '2026-11-03'), '2026-11-05');
    assert.equal(followStartDate('2026-11-01', '2026-11-03', '2026-11-03'), '2026-11-01');
});

test('an end date before the new start date is moved to it', () => {
    assert.equal(followStartDate('2026-11-20', '2026-11-03', '2026-11-10'), '2026-11-20');
});

test('a chosen range keeps its end date', () => {
    assert.equal(followStartDate('2026-11-05', '2026-11-03', '2026-11-10'), '2026-11-10');
});

test('clearing the start date leaves the end date untouched', () => {
    assert.equal(followStartDate('', '2026-11-03', '2026-11-03'), '2026-11-03');
});
