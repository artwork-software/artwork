import assert from 'node:assert/strict';
import { register } from 'node:module';
import test from 'node:test';

register('./support/viteAliasHooks.mjs', import.meta.url);

const { computeDayProperties, enrichDay } = await import('../../resources/js/Composeables/calendarDateUtils.js');

test('week numbers follow ISO 8601 in years starting on a Friday', () => {
    // 2027 beginnt an einem Freitag: US-Zählung lag hier ganzjährig um eins zu hoch
    assert.equal(computeDayProperties('2027-01-11').weekNumber, 2);
    assert.equal(computeDayProperties('2027-01-17').weekNumber, 2);
    assert.equal(computeDayProperties('2027-06-14').weekNumber, 24);
});

test('sundays belong to the week of the preceding monday', () => {
    assert.equal(computeDayProperties('2026-10-19').weekNumber, 43);
    assert.equal(computeDayProperties('2026-10-25').weekNumber, 43);
});

test('week numbers across the turn of the year', () => {
    assert.equal(computeDayProperties('2026-12-28').weekNumber, 53);
    assert.equal(computeDayProperties('2027-01-01').weekNumber, 53);
    assert.equal(computeDayProperties('2027-01-04').weekNumber, 1);
    assert.equal(computeDayProperties('2025-12-29').weekNumber, 1);
});

test('the weekly extra row carries the ISO week of its monday', () => {
    assert.deepEqual(enrichDay({ date: '2027-01-11', isExtraRow: true }), {
        isExtraRow: true,
        weekNumber: 2,
        date: '2027-01-11',
    });
});
