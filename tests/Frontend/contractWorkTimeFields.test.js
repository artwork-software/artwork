import assert from 'node:assert/strict';
import test from 'node:test';
import {
    addDays,
    contractFieldLabels,
    contractValuesFrom,
    coversDate,
    emptyContractValues,
    emptyWorkTimeValues,
    entryValidOn,
    formatDate,
    formatMinutesAsHours,
    formatPeriod,
    normalizeTime,
    seasonInfoParams,
    timeToMinutes,
    weekDays,
    weeklyMinutes,
    workTimeValuesFrom,
} from '../../resources/js/Pages/Users/ContractWorkTime/contractWorkTimeFields.js';

const t = (key) => key;

test('contractFieldLabels covers every season parameter and its active flag', () => {
    for (const param of seasonInfoParams) {
        assert.equal(contractFieldLabels[param.key], param.label);
        assert.equal(contractFieldLabels[param.activeKey], `${param.label} (active)`);
        assert.ok(param.key in emptyContractValues(), `${param.key} missing in empty contract values`);
        assert.ok(param.activeKey in emptyContractValues(), `${param.activeKey} missing in empty contract values`);
    }
});

test('contractValuesFrom copies known keys only and keeps the template id separate', () => {
    assert.deepEqual(contractValuesFrom(null, 7), { ...emptyContractValues(), user_contract_id: 7 });

    const values = contractValuesFrom({
        id: 99,
        user_contract_id: 3,
        free_full_days_per_week: 2,
        compensation_period: 14,
        unknown_field: 'ignored',
        annual_vacation_days: null,
    }, 5);
    assert.equal(values.user_contract_id, 5, 'template id comes from the argument, not the source');
    assert.equal(values.free_full_days_per_week, 2);
    assert.equal(values.compensation_period, 14);
    assert.equal(values.annual_vacation_days, 0, 'null falls back to the empty default');
    assert.equal(values.special_day_rule_active, true, 'missing keys keep defaults');
    assert.ok(!('unknown_field' in values));
    assert.ok(!('id' in values));
});

test('normalizeTime accepts HH:MM(:SS) and falls back to 00:00', () => {
    assert.equal(normalizeTime('08:00:00'), '08:00');
    assert.equal(normalizeTime('8:5'), '08:05');
    assert.equal(normalizeTime('08:30'), '08:30');
    assert.equal(normalizeTime(''), '00:00');
    assert.equal(normalizeTime(null), '00:00');
    assert.equal(normalizeTime(480), '00:00');
    assert.equal(normalizeTime('abc'), '00:00');
});

test('workTimeValuesFrom normalizes every weekday and sets the pattern id', () => {
    assert.deepEqual(workTimeValuesFrom(null, 4), { ...emptyWorkTimeValues(), work_time_pattern_id: 4 });
    const values = workTimeValuesFrom({ monday: '08:00:00', friday: '6:30', sunday: null }, 9);
    assert.equal(values.work_time_pattern_id, 9);
    assert.equal(values.monday, '08:00');
    assert.equal(values.friday, '06:30');
    assert.equal(values.sunday, '00:00');
    assert.equal(values.tuesday, '00:00');
    for (const day of weekDays) {
        assert.ok(day.key in values);
    }
});

test('timeToMinutes, weeklyMinutes and formatMinutesAsHours agree', () => {
    assert.equal(timeToMinutes('08:30'), 510);
    assert.equal(timeToMinutes('8:05'), 485);
    assert.equal(timeToMinutes(''), 0);
    assert.equal(timeToMinutes('8'), 0);
    assert.equal(timeToMinutes(null), 0);
    assert.equal(weeklyMinutes({ monday: '08:00', tuesday: '08:00', wednesday: '08:00', thursday: '08:00', friday: '06:30' }), 2310);
    assert.equal(weeklyMinutes(null), 0);
    assert.equal(formatMinutesAsHours(2310), '38:30 h');
    assert.equal(formatMinutesAsHours(5), '0:05 h');
});

test('formatDate/formatPeriod render DD.MM.YYYY with open ends', () => {
    assert.equal(formatDate('2026-01-01'), '01.01.2026');
    assert.equal(formatDate(null), '');
    assert.equal(formatPeriod('2026-01-01', null, t), '01.01.2026 – open-ended');
    assert.equal(formatPeriod(null, '2026-12-31', t), 'from the beginning – 31.12.2026');
    assert.equal(formatPeriod('2026-01-01', '2026-12-31', t), '01.01.2026 – 31.12.2026');
});

test('coversDate treats null bounds as open', () => {
    assert.equal(coversDate(null, '2026-07-21'), false);
    assert.equal(coversDate({ valid_from: null, valid_until: null }, '2026-07-21'), true);
    assert.equal(coversDate({ valid_from: '2026-01-01', valid_until: null }, '2026-07-21'), true);
    assert.equal(coversDate({ valid_from: '2026-08-01', valid_until: null }, '2026-07-21'), false);
    assert.equal(coversDate({ valid_from: '2026-01-01', valid_until: '2026-06-30' }, '2026-07-21'), false);
    assert.equal(coversDate({ valid_from: '2026-01-01', valid_until: '2026-07-21' }, '2026-07-21'), true, 'valid_until is inclusive');
});

test('entryValidOn picks the entry with the latest valid_from when several overlap', () => {
    const older = { id: 1, valid_from: '2026-01-01', valid_until: null };
    const newer = { id: 2, valid_from: '2026-06-01', valid_until: null };
    const expired = { id: 3, valid_from: '2026-07-01', valid_until: '2026-07-10' };
    assert.equal(entryValidOn([older, newer, expired], '2026-07-21'), newer);
    assert.equal(entryValidOn([older, newer, expired], '2026-07-05'), expired);
    assert.equal(entryValidOn([older], '2025-12-31'), null);
    assert.equal(entryValidOn(null, '2026-07-21'), null);
    // ohne valid_from gilt der Satz seit Beginn, verliert aber gegen einen datierten Satz
    const undated = { id: 4, valid_from: null, valid_until: null };
    assert.equal(entryValidOn([undated, older], '2026-03-01'), older);
});

test('addDays returns YYYY-MM-DD across month and year boundaries', () => {
    assert.equal(addDays('2026-01-31', 1), '2026-02-01');
    assert.equal(addDays('2026-12-31', 1), '2027-01-01');
    assert.equal(addDays('2026-03-01', -1), '2026-02-28');
});
