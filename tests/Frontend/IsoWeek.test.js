import assert from 'node:assert/strict';
import test from 'node:test';
import { isoWeekToDateRange, mondayOfIsoWeek1, toDmy, toYmd } from '../../resources/js/Helper/IsoWeek.js';

test('ISO week 1 starts on the Monday of the week containing January 4th', () => {
    // 2026-01-04 ist ein Sonntag → Montag der KW 1 liegt noch im Vorjahr
    assert.equal(toYmd(mondayOfIsoWeek1(2026)), '2025-12-29');
    // 2021-01-04 ist ein Montag
    assert.equal(toYmd(mondayOfIsoWeek1(2021)), '2021-01-04');
    // 2020-01-04 ist ein Samstag
    assert.equal(toYmd(mondayOfIsoWeek1(2020)), '2019-12-30');
});

test('week ranges span Monday..Sunday including year boundaries', () => {
    assert.deepEqual(pick(isoWeekToDateRange(1, 2026)), { start: '2025-12-29', end: '2026-01-04' });
    assert.deepEqual(pick(isoWeekToDateRange(36, 2026)), { start: '2026-08-31', end: '2026-09-06' });
    assert.deepEqual(pick(isoWeekToDateRange(53, 2020)), { start: '2020-12-28', end: '2021-01-03' });
    assert.deepEqual(pick(isoWeekToDateRange(1, 2021)), { start: '2021-01-04', end: '2021-01-10' });
});

test('accepts numeric strings as delivered by the backend payload', () => {
    assert.deepEqual(pick(isoWeekToDateRange('36', '2026')), { start: '2026-08-31', end: '2026-09-06' });
});

test('rejects invalid input instead of throwing', () => {
    assert.equal(isoWeekToDateRange(0, 2026), null);
    assert.equal(isoWeekToDateRange(54, 2026), null);
    assert.equal(isoWeekToDateRange('x', 2026), null);
    assert.equal(isoWeekToDateRange(10, null), null);
    assert.equal(isoWeekToDateRange(undefined, undefined), null);
});

test('display format helper uses DD.MM.YYYY', () => {
    const { monday, sunday } = isoWeekToDateRange(36, 2026);
    assert.equal(toDmy(monday), '31.08.2026');
    assert.equal(toDmy(sunday), '06.09.2026');
});

function pick(range) {
    return { start: range.start, end: range.end };
}
