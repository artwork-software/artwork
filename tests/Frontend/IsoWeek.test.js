import assert from 'node:assert/strict';
import test from 'node:test';
import { isoWeekOf, isoWeekToDateRange, isoWeeksInYear, mondayOfIsoWeek, mondayOfIsoWeek1, toDmy, toYmd } from '../../resources/js/Helper/IsoWeek.js';

test('isoWeeksInYear knows 52- and 53-week years', () => {
    assert.equal(isoWeeksInYear(2020), 53);
    assert.equal(isoWeeksInYear(2021), 52);
    assert.equal(isoWeeksInYear(2024), 52);
    assert.equal(isoWeeksInYear(2025), 52);
    assert.equal(isoWeeksInYear(2026), 53);
    assert.equal(isoWeeksInYear(2027), 52);
});

test('week 53 only exists in 53-week years and never rolls over into the next year', () => {
    // 2026 hat 53 Wochen: KW 53 = 28.12.2026–03.01.2027
    assert.deepEqual(pick(isoWeekToDateRange(53, 2026)), { start: '2026-12-28', end: '2027-01-03' });
    assert.deepEqual(pick(isoWeekToDateRange(1, 2027)), { start: '2027-01-04', end: '2027-01-10' });
    // 2025 hat nur 52 Wochen: KW 53 existiert nicht (vorher: still KW 1/2026)
    assert.equal(isoWeekToDateRange(53, 2025), null);
    assert.deepEqual(pick(isoWeekToDateRange(52, 2025)), { start: '2025-12-22', end: '2025-12-28' });
    assert.equal(isoWeekToDateRange(53, 2024), null);
});

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

test('isoWeekOf assigns year-boundary days to the ISO year of their Thursday', () => {
    assert.deepEqual(isoWeekOf(new Date(2026, 0, 1)), { week: 1, year: 2026 });
    assert.deepEqual(isoWeekOf(new Date(2027, 0, 1)), { week: 53, year: 2026 });
    assert.deepEqual(isoWeekOf(new Date(2025, 11, 29)), { week: 1, year: 2026 });
    assert.deepEqual(isoWeekOf(new Date(2021, 0, 3)), { week: 53, year: 2020 });
    assert.deepEqual(isoWeekOf(new Date(2026, 8, 8)), { week: 37, year: 2026 });
});

test('mondayOfIsoWeek matches the start of isoWeekToDateRange and round-trips through isoWeekOf', () => {
    for (const [week, year] of [[1, 2026], [37, 2026], [53, 2026], [52, 2025], [53, 2020]]) {
        const monday = mondayOfIsoWeek(week, year);
        assert.equal(toYmd(monday), isoWeekToDateRange(week, year).start);
        assert.equal(monday.getDay(), 1);
        assert.deepEqual(isoWeekOf(monday), { week, year });
    }
});
