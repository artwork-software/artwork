import assert from 'node:assert/strict';
import test from 'node:test';
import {
    legalBreakMinutesFor,
    minimumBreakMinutes,
    parseHHMM,
    resolveAutoBreakValue,
    toBreakNumber,
    workMinutesBetween,
} from '../../resources/js/Helper/LegalBreak.js';

test('ArbZG boundaries are strict: 6:00 → 0, 6:01 → 30, 9:00 → 30, 9:01 → 45', () => {
    assert.equal(minimumBreakMinutes(360), 0);
    assert.equal(minimumBreakMinutes(361), 30);
    assert.equal(minimumBreakMinutes(540), 30);
    assert.equal(minimumBreakMinutes(541), 45);
    assert.equal(minimumBreakMinutes(0), 0);
    assert.equal(minimumBreakMinutes(240), 0);
});

test('work minutes handle over-midnight shifts and seconds in the time string', () => {
    assert.equal(workMinutesBetween('22:00', '06:00'), 480);
    assert.equal(workMinutesBetween('08:00:00', '17:00:00'), 540);
    assert.equal(workMinutesBetween('08:00', '12:00'), 240);
    assert.equal(workMinutesBetween('', '12:00'), 0);
    assert.equal(workMinutesBetween('8:00', null), 0);
});

test('legal break from times: exactly 4h → 0, over-midnight 10h → 45', () => {
    assert.equal(legalBreakMinutesFor('08:00', '12:00'), 0);
    assert.equal(legalBreakMinutesFor('08:00', '14:00'), 0);
    assert.equal(legalBreakMinutesFor('08:00', '14:01'), 30);
    assert.equal(legalBreakMinutesFor('08:00', '17:00'), 30);
    assert.equal(legalBreakMinutesFor('08:00', '17:01'), 45);
    assert.equal(legalBreakMinutesFor('20:00', '06:00'), 45);
});

test('parseHHMM accepts HH:MM and HH:MM:SS and rejects garbage', () => {
    assert.equal(parseHHMM('07:30'), 450);
    assert.equal(parseHHMM('07:30:00'), 450);
    assert.equal(parseHHMM('7:05'), 425);
    assert.equal(parseHHMM('abc'), null);
    assert.equal(parseHHMM(''), null);
    assert.equal(parseHHMM('48:00'), null);
});

test('toBreakNumber normalises empty and string values', () => {
    assert.equal(toBreakNumber(''), null);
    assert.equal(toBreakNumber(null), null);
    assert.equal(toBreakNumber('30'), 30);
    assert.equal(toBreakNumber(15), 15);
    assert.equal(toBreakNumber('x'), null);
});

test('auto break fills empty field and raises non-manual values below the minimum', () => {
    assert.equal(resolveAutoBreakValue({ current: '', legal: 30, manuallyEdited: false, lastAutoValue: null }), 30);
    assert.equal(resolveAutoBreakValue({ current: null, legal: 0, manuallyEdited: false, lastAutoValue: null }), 0);
    assert.equal(resolveAutoBreakValue({ current: 15, legal: 30, manuallyEdited: false, lastAutoValue: null }), 30);
    assert.equal(resolveAutoBreakValue({ current: '15', legal: 45, manuallyEdited: false, lastAutoValue: null }), 45);
});

test('auto break never silently overwrites manual values', () => {
    assert.equal(resolveAutoBreakValue({ current: 15, legal: 30, manuallyEdited: true, lastAutoValue: null }), null);
    assert.equal(resolveAutoBreakValue({ current: 60, legal: 30, manuallyEdited: true, lastAutoValue: null }), null);
});

test('auto break keeps higher non-manual values but follows the minimum when the value was set automatically', () => {
    assert.equal(resolveAutoBreakValue({ current: 60, legal: 30, manuallyEdited: false, lastAutoValue: null }), null);
    assert.equal(resolveAutoBreakValue({ current: 45, legal: 30, manuallyEdited: false, lastAutoValue: 45 }), 30);
    assert.equal(resolveAutoBreakValue({ current: 30, legal: 30, manuallyEdited: false, lastAutoValue: 30 }), 30);
});
