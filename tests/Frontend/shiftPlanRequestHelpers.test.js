import assert from 'node:assert/strict';
import test from 'node:test';
import {
    appendForeignCraftEntries,
    canResubmitRequest,
    canWithdrawRequest,
    isRequesterOf,
    shiftDurationHours,
    shiftHistoryFilterForRequest,
    shiftPlanRequestWeekLabel,
    sortDayEntriesByStart,
} from '../../resources/js/Pages/ShiftPlanRequests/components/shiftPlanRequestHelpers.js';

test('isRequesterOf prefers requested_by_user_id, then requested_by.id, then assumeOwn', () => {
    assert.equal(isRequesterOf(null, 5), false);
    assert.equal(isRequesterOf({ requested_by_user_id: 5 }, 5), true);
    assert.equal(isRequesterOf({ requested_by_user_id: '5' }, 5), true, 'numeric strings from payloads');
    assert.equal(isRequesterOf({ requested_by_user_id: 6 }, 5), false);
    // id-Feld gewinnt gegen requested_by-Objekt
    assert.equal(isRequesterOf({ requested_by_user_id: 6, requested_by: { id: 5 } }, 5), false);
    assert.equal(isRequesterOf({ requested_by: { id: 5 } }, 5), true);
    assert.equal(isRequesterOf({ requested_by: { id: 6 } }, 5), false);
    // ohne Angabe: nur mit assumeOwn (z. B. Liste „nur eigene Anfragen")
    assert.equal(isRequesterOf({ requested_by_user_id: null }, 5), false);
    assert.equal(isRequesterOf({ requested_by_user_id: null }, 5, true), true);
    assert.equal(isRequesterOf({}, 5, true), true);
    assert.equal(isRequesterOf({ requested_by: {} }, 5, true), true);
});

test('canWithdrawRequest only for pending requests of the requester', () => {
    assert.equal(canWithdrawRequest({ status: 'pending', requested_by_user_id: 5 }, 5), true);
    assert.equal(canWithdrawRequest({ status: 'pending', requested_by_user_id: 6 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'approved', requested_by_user_id: 5 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'rejected', requested_by_user_id: 5 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'pending' }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'pending' }, 5, true), true);
    assert.equal(canWithdrawRequest(null, 5, true), false);
});

test('canResubmitRequest only for rejected requests of the requester', () => {
    assert.equal(canResubmitRequest({ status: 'rejected', requested_by_user_id: 5 }, 5), true);
    assert.equal(canResubmitRequest({ status: 'rejected', requested_by_user_id: 6 }, 5), false);
    assert.equal(canResubmitRequest({ status: 'pending', requested_by_user_id: 5 }, 5), false);
    assert.equal(canResubmitRequest({ status: 'rejected' }, 5), false);
    assert.equal(canResubmitRequest({ status: 'rejected' }, 5, true), true);
    assert.equal(canResubmitRequest(undefined, 5), false);
});

test('shiftPlanRequestWeekLabel shows the Monday–Sunday range for valid weeks', () => {
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 37, year: 2026 }), 'KW 37 / 2026 (07.09.2026 – 13.09.2026)');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: '1', year: '2026' }), 'KW 1 / 2026 (29.12.2025 – 04.01.2026)');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 53, year: 2026 }), 'KW 53 / 2026 (28.12.2026 – 03.01.2027)');
});

test('shiftPlanRequestWeekLabel degrades gracefully for invalid or missing weeks', () => {
    // KW 53 existiert 2025 nicht → kein Datumsbereich, aber die Eingabe bleibt sichtbar
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 53, year: 2025 }), 'KW 53 / 2025');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 0, year: 2026 }), 'KW 0 / 2026');
    assert.equal(shiftPlanRequestWeekLabel({}), 'KW – / –');
    assert.equal(shiftPlanRequestWeekLabel(null), 'KW – / –');
});

test('shiftDurationHours subtracts the break and handles shifts over midnight', () => {
    assert.equal(shiftDurationHours({ start: '08:00', end: '16:00' }), 8);
    assert.equal(shiftDurationHours({ start: '08:00', end: '16:30', break_minutes: 30 }), 8);
    assert.equal(shiftDurationHours({ start: '22:00', end: '06:00' }), 8, 'ends on the next day');
    assert.equal(shiftDurationHours({ start: '08:00', end: '08:00' }), 0);
    assert.equal(shiftDurationHours({ start: '08:00' }), 0);
    assert.equal(shiftDurationHours(null), 0);
});

const rowFor = () => ({ days: {}, totals: { total_shifts: 1, total_hours: 8, foreign_shifts: 0, foreign_hours: 0 } });
const foreignShift = (overrides = {}) => ({
    id: 10,
    date: '2026-07-08',
    start: '18:00',
    end: '23:00',
    break_minutes: 0,
    is_committed: true,
    room_name: 'Studio',
    craft: { id: 3, name: 'Ton', abbreviation: 'TO' },
    workers: [{ type: 'user', id: 5, shift_qualification_id: 2 }],
    ...overrides,
});

test('appendForeignCraftEntries adds greyed entries with craft abbreviation to existing rows only', () => {
    const user = rowFor();
    const rows = new Map([['user-5', user]]);
    const shift = foreignShift({ workers: [
        { type: 'user', id: 5, shift_qualification_id: 2 },
        { type: 'user', id: 99, shift_qualification_id: null },
    ] });

    appendForeignCraftEntries(rows, [shift], ['2026-07-07', '2026-07-08'], { 2: 'Azubi' });

    assert.equal(rows.size, 1, 'no new row for people without a shift in the request');
    assert.deepEqual(user.days['2026-07-08'], [{
        unique_key: 'foreign-10-user-5',
        is_foreign_craft: true,
        shift_id: 10,
        start_time: '18:00',
        end_time: '23:00',
        room_name: 'Studio',
        qualification: 'Azubi',
        is_committed: true,
        craft_name: 'Ton',
        craft_abbreviation: 'TO',
    }]);
    assert.equal(user.totals.total_shifts, 1, 'request shifts stay untouched');
    assert.equal(user.totals.foreign_shifts, 1);
    assert.equal(user.totals.foreign_hours, 5);
});

test('appendForeignCraftEntries skips days outside the week, duplicates and falls back to the craft name', () => {
    const user = rowFor();
    const rows = new Map([['user-5', user]]);

    appendForeignCraftEntries(rows, [
        foreignShift({ date: '2026-07-20' }),
        foreignShift({ craft: { id: 3, name: 'Ton', abbreviation: null } }),
        foreignShift(),
    ], ['2026-07-08']);

    assert.equal(user.days['2026-07-20'], undefined);
    assert.equal(user.days['2026-07-08'].length, 1);
    assert.equal(user.days['2026-07-08'][0].craft_abbreviation, 'Ton');
    assert.equal(user.days['2026-07-08'][0].qualification, null);
    assert.equal(user.totals.foreign_shifts, 1);
});

test('sortDayEntriesByStart orders a day chronologically, entries without start first', () => {
    const entries = [
        { unique_key: 'b', start_time: '18:00' },
        { unique_key: 'a', start_time: '08:00' },
        { unique_key: 'full-day', start_time: null },
    ];
    assert.deepEqual(sortDayEntriesByStart(entries).map(e => e.unique_key), ['full-day', 'a', 'b']);
});

test('appendAbsenceEntries adds absences to existing person rows only', async () => {
    const { appendAbsenceEntries, absenceTimeLabel } = await import(
        '../../resources/js/Pages/ShiftPlanRequests/components/shiftPlanRequestHelpers.js'
    );
    const rows = new Map([['user-1', { days: {}, totals: {} }]]);
    const absences = [
        { id: 5, worker_type: 'user', worker_id: 1, date: '2026-11-24', type: 'NOT_AVAILABLE', full_day: false, start_time: '08:00', end_time: '12:00', comment: 'Arzt' },
        { id: 6, worker_type: 'user', worker_id: 2, date: '2026-11-24', type: 'OFF_WORK', full_day: true },
        { id: 7, worker_type: 'user', worker_id: 1, date: '2026-12-01', type: 'OFF_WORK', full_day: true },
    ];
    appendAbsenceEntries(rows, absences, ['2026-11-24']);
    appendAbsenceEntries(rows, absences, ['2026-11-24']);

    const entries = rows.get('user-1').days['2026-11-24'];
    assert.equal(entries.length, 1);
    assert.equal(entries[0].is_absence, true);
    assert.equal(entries[0].absence_type, 'NOT_AVAILABLE');
    assert.equal(entries[0].comment, 'Arzt');
    assert.equal(absenceTimeLabel(entries[0]), '08:00 – 12:00');
    assert.equal(absenceTimeLabel({ full_day: true }), 'Full day');
    assert.equal(absenceTimeLabel({ full_day: false, day_part: 'afternoon' }), 'Afternoon');
    assert.equal(rows.has('user-2'), false);
});

test('shiftHistoryFilterForRequest prefilters craft and Monday–Sunday of the request week', () => {
    const filter = shiftHistoryFilterForRequest({
        week_number: 37,
        year: 2026,
        craft_id: 4,
        craft: { id: 4, name: 'Licht', abbreviation: 'LI', color: '#fff' },
    });
    assert.deepEqual(filter, {
        craftId: 4,
        startDate: '2026-09-07',
        endDate: '2026-09-13',
        crafts: [{ id: 4, name: 'Licht', abbreviation: 'LI' }],
    });
});

test('shiftHistoryFilterForRequest handles ISO year boundaries and missing craft data', () => {
    // KW 1/2026 beginnt am Montag 29.12.2025
    const boundary = shiftHistoryFilterForRequest({ week_number: 1, year: 2026, craft: { id: 2, name: 'Ton' } });
    assert.equal(boundary.startDate, '2025-12-29');
    assert.equal(boundary.endDate, '2026-01-04');
    assert.equal(boundary.craftId, 2, 'craft id falls back to craft.id');

    // ohne Gewerk-Objekt kein Vorfilter aufs Gewerk (Modal zeigt „Alle Gewerke“)
    const withoutCraft = shiftHistoryFilterForRequest({ week_number: 10, year: 2026, craft_id: 3 });
    assert.equal(withoutCraft.craftId, null);
    assert.deepEqual(withoutCraft.crafts, []);

    // ungültige KW → kein Verlauf
    assert.equal(shiftHistoryFilterForRequest({ week_number: 53, year: 2025, craft_id: 1 }), null);
    assert.equal(shiftHistoryFilterForRequest(null), null);
});
