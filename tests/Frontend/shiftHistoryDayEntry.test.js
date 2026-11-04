import assert from 'node:assert/strict';
import test from 'node:test';
import {
    dayEntryMessageKey,
    dayEntryOf,
    dayEntryTime,
    describeDayEntry,
} from '../../resources/js/Helper/shiftHistoryDayEntry.js';

const VACATION = 'Artwork\\Modules\\Vacation\\Models\\Vacation';
const AVAILABILITY = 'Artwork\\Modules\\Availability\\Models\\Availability';
const NOTE = 'Artwork\\Modules\\Shift\\Models\\ShiftPlanComment';

test('dayEntryOf recognises day entries and ignores shift logs', () => {
    assert.equal(dayEntryOf({ subject_type: 'Artwork\\Modules\\Shift\\Models\\Shift', properties: {} }), null);
    assert.equal(dayEntryOf(null), null);

    const entry = dayEntryOf({
        subject_type: VACATION,
        event: 'created',
        properties: { worker_day: '2026-05-06', day_entry: { kind: 'absence', type: 'NOT_AVAILABLE', full_day: true } },
    });
    assert.deepEqual(entry, {
        kind: 'absence',
        day: '2026-05-06',
        data: { kind: 'absence', type: 'NOT_AVAILABLE', full_day: true },
    });
});

test('dayEntryOf reads older entries from attributes or, when deleted, from old', () => {
    const created = dayEntryOf({
        subject_type: AVAILABILITY,
        event: 'created',
        properties: { attributes: { date: '2026-05-06', start_time: '18:00:00', end_time: '22:00:00', full_day: false } },
    });
    assert.equal(created.kind, 'availability');
    assert.equal(created.day, '2026-05-06');
    assert.equal(dayEntryTime(created.data), '18:00 – 22:00');

    const deleted = dayEntryOf({
        subject_type: NOTE,
        event: 'deleted',
        properties: { old: { date: '2026-05-07', comment: 'Weg' } },
    });
    assert.equal(deleted.day, '2026-05-07');
    assert.equal(describeDayEntry(deleted), '„Weg“');
});

test('describeDayEntry combines type, time and comment without repeating the type', () => {
    const t = (key) => `t:${key}`;
    assert.equal(
        describeDayEntry({ kind: 'absence', data: { type: 'NOT_AVAILABLE', full_day: true, comment: 'Arzttermin' } }, t),
        't:Not available · t:Full day · „Arzttermin“'
    );
    assert.equal(
        describeDayEntry({ kind: 'absence', data: { type: 'NOT_AVAILABLE', day_part: 'morning', comment: 'NOT_AVAILABLE' } }, t),
        't:Not available · t:Morning'
    );
    assert.equal(describeDayEntry({ kind: 'availability', data: {} }, t), '–');
    assert.equal(describeDayEntry({ kind: 'note', data: { comment: '' } }, t), '–');
});

test('dayEntryMessageKey picks the message per kind and event', () => {
    assert.equal(dayEntryMessageKey({ kind: 'absence' }, 'created'), 'Absence entered: {0}');
    assert.equal(dayEntryMessageKey({ kind: 'availability' }, 'deleted'), 'Availability removed: {0}');
    assert.equal(dayEntryMessageKey({ kind: 'note' }, 'updated'), 'Note changed: {0}');
    assert.equal(dayEntryMessageKey({ kind: 'note' }, 'restored'), 'Note changed: {0}');
    assert.equal(dayEntryMessageKey({ kind: 'unknown' }, 'created'), null);
});
