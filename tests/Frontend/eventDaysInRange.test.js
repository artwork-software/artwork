import assert from 'node:assert/strict';
import { register } from 'node:module';
import test from 'node:test';

register('./support/viteAliasHooks.mjs', import.meta.url);

const { getEventDaysInRange } = await import('../../resources/js/Composeables/calendarDateUtils.js');

test('multi-day event covers every calendar day regardless of the clock times', () => {
    assert.deepEqual(getEventDaysInRange('2026-10-24 10:00', '2026-10-26 14:00'), ['24.10.2026', '25.10.2026', '26.10.2026']);
    assert.deepEqual(getEventDaysInRange('2026-10-24 15:00', '2026-10-26 14:00'), ['24.10.2026', '25.10.2026', '26.10.2026']);
});

test('an end exactly at midnight does not occupy the following day', () => {
    assert.deepEqual(getEventDaysInRange('2026-08-30 22:00', '2026-08-31 00:00'), ['30.08.2026']);
    assert.deepEqual(getEventDaysInRange('2026-10-24 10:00', '2026-10-26 00:00'), ['24.10.2026', '25.10.2026']);
});

test('overnight events and broken legacy data', () => {
    assert.deepEqual(getEventDaysInRange('2026-08-30 22:30', '2026-08-31 02:00'), ['30.08.2026', '31.08.2026']);
    assert.deepEqual(getEventDaysInRange('2026-08-30 22:00', '2026-08-30 00:00'), ['30.08.2026']);
});
