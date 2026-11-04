import assert from 'node:assert/strict';
import test from 'node:test';
import {
    dayEntryCategory,
    dayEntryDayLabel,
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
        dayEnd: null,
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

const INDIVIDUAL_TIME = 'Artwork\\Modules\\IndividualTimes\\Models\\IndividualTime';
const COMPENSATION = 'Artwork\\Modules\\Shift\\Models\\CompensationDayOff';

test('individual times are day entries spanning their days', () => {
    const entry = dayEntryOf({
        subject_type: INDIVIDUAL_TIME,
        event: 'created',
        properties: {
            worker_day: '2026-11-24',
            worker_day_end: '2026-11-25',
            day_entry: { kind: 'individual_time', title: 'Aufbau', full_day: false, start_time: '22:00', end_time: '02:00' },
        },
    });
    assert.equal(entry.kind, 'individual_time');
    assert.equal(dayEntryCategory(entry), 'day_planning');
    assert.equal(describeDayEntry(entry), 'Aufbau · 22:00 – 02:00');
    assert.equal(dayEntryMessageKey(entry, 'deleted'), 'Individual time removed: {0}');
    assert.equal(dayEntryDayLabel(entry, (day) => day.split('-').reverse().join('.')), '24.11.2026 – 25.11.2026');
});

test('compensation days describe half or full day and holiday', () => {
    const entry = dayEntryOf({
        subject_type: COMPENSATION,
        event: 'deleted_with_reason',
        properties: {
            worker_day: '2026-11-24',
            day_entry: { kind: 'compensation', value: 0.5, half_day_period: 'morning', for_holiday: true },
        },
    });
    assert.equal(describeDayEntry(entry), 'Morning · For a public holiday');
    assert.equal(dayEntryMessageKey(entry, 'deleted_with_reason'), 'Compensation day off removed: {0}');
    assert.equal(dayEntryDayLabel(entry), '2026-11-24');
});

test('project assignments are recognised by log name and keep their own message', () => {
    const entry = dayEntryOf({
        subject_type: null,
        log_name: 'project_day_assignment',
        event: 'created',
        properties: { worker_day: '2026-11-24', day_entry: { kind: 'project_assignment', project: 'Faust' } },
    });
    assert.equal(entry.kind, 'project_assignment');
    assert.equal(dayEntryCategory(entry), 'day_planning');
    assert.equal(dayEntryMessageKey(entry, 'created'), null);
});

test('day services are recognised by log name and named in the description', () => {
    const entry = dayEntryOf({
        subject_type: 'Artwork\\Modules\\DayService\\Models\\DayService',
        log_name: 'day_service',
        event: 'deleted',
        properties: { worker_day: '2026-11-24', day_entry: { kind: 'day_service', name: 'Abenddienst' } },
    });
    assert.equal(entry.kind, 'day_service');
    assert.equal(dayEntryCategory(entry), 'day_planning');
    assert.equal(describeDayEntry(entry), 'Abenddienst');
    assert.equal(dayEntryTime(entry.data), '');
    assert.equal(dayEntryMessageKey(entry, 'deleted'), 'Day service removed: {0}');
});
