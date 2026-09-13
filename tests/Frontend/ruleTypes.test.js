import assert from 'node:assert/strict';
import test from 'node:test';
import {
    RULE_TYPES,
    SELECTABLE_RULE_TYPES,
    decimalHourToTime,
    formatMinutesAsHours,
    formatNumber,
    formatRuleValue,
    formatViolationMeasure,
    ruleTypeHasPeriodWeeks,
    ruleTypeLabelKey,
    ruleTypeNeedsValue,
    ruleTypeValueKind,
    ruleTypeValueOptional,
    timeToDecimalHour,
} from '../../resources/js/Pages/ShiftWarnings/ruleTypes.js';

/** Übersetzungs-Stub: Key mit {param}-Ersetzung, wie vue-i18n benannte Parameter */
const t = (key, params = {}) => String(key).replace(/\{(\w+)\}/g, (_, name) => (params[name] ?? `{${name}}`));

test('selectable rule types exclude hidden aliases', () => {
    assert.ok(SELECTABLE_RULE_TYPES.includes('weeklyMaxHours'));
    assert.ok(!SELECTABLE_RULE_TYPES.includes('maxWorkingHoursOnWeek'), 'alias must stay hidden');
    for (const type of SELECTABLE_RULE_TYPES) {
        assert.ok(!RULE_TYPES[type].hidden);
    }
});

test('ruleType helpers resolve kind/label/flags with safe fallbacks', () => {
    assert.equal(ruleTypeValueKind('maxWorkingHoursOnDay'), 'hours');
    assert.equal(ruleTypeValueKind('halfDayOffConflict'), 'time');
    assert.equal(ruleTypeValueKind('workOnSunday'), 'none');
    assert.equal(ruleTypeValueKind('unknownType'), 'hours');
    assert.equal(ruleTypeLabelKey('unknownType'), 'unknownType');
    assert.equal(ruleTypeLabelKey('maxWorkingHoursOnDay'), 'Daily maximum of hours');
    assert.equal(ruleTypeNeedsValue('workOnHoliday'), false);
    assert.equal(ruleTypeNeedsValue('overtimeDeadline'), true);
    assert.equal(ruleTypeValueOptional('minFreeSundaysPerYear'), true);
    assert.equal(ruleTypeValueOptional('weeklyMaxHours'), false);
});

test('ruleTypeHasPeriodWeeks is only true for the weekly average', () => {
    assert.equal(ruleTypeHasPeriodWeeks('averageWeeklyHours'), true);
    for (const type of Object.keys(RULE_TYPES).filter((key) => key !== 'averageWeeklyHours')) {
        assert.equal(ruleTypeHasPeriodWeeks(type), false, type);
    }
    assert.equal(ruleTypeHasPeriodWeeks(undefined), false);
});

test('decimal hour and time conversions round-trip', () => {
    assert.equal(decimalHourToTime(14.5), '14:30');
    assert.equal(decimalHourToTime(0), '00:00');
    assert.equal(decimalHourToTime('9'), '09:00');
    assert.equal(decimalHourToTime(-1), '');
    assert.equal(decimalHourToTime('x'), '');
    assert.equal(decimalHourToTime(25.99), '23:59');
    assert.equal(timeToDecimalHour('14:30'), 14.5);
    assert.equal(timeToDecimalHour('9:15'), 9.25);
    assert.equal(timeToDecimalHour('abc'), null);
    assert.equal(timeToDecimalHour(14.5), null);
});

test('formatNumber uses German decimal comma with one fraction digit', () => {
    assert.equal(formatNumber(9.5), '9,5');
    assert.equal(formatNumber(8), '8');
    assert.equal(formatNumber(9.55), '9,6');
    assert.equal(formatNumber('abc'), 'abc');
    // Number(null) ist 0, Number(undefined) NaN → leerer String
    assert.equal(formatNumber(null), '0');
    assert.equal(formatNumber(undefined), '');
});

test('formatMinutesAsHours pads minutes and clamps negatives', () => {
    assert.equal(formatMinutesAsHours(210), '3:30 h');
    assert.equal(formatMinutesAsHours(5), '0:05 h');
    assert.equal(formatMinutesAsHours(-30), '0:00 h');
    assert.equal(formatMinutesAsHours(undefined), '0:00 h');
});

test('formatRuleValue renders per value kind', () => {
    assert.equal(formatRuleValue({ trigger_type: 'maxWorkingHoursOnDay', individual_number_value: 8 }, t), '8 h');
    assert.equal(formatRuleValue({ trigger_type: 'restTimeBeforeWorkday', individual_number_value: 11.5 }, t), '11,5 h');
    assert.equal(
        formatRuleValue({ trigger_type: 'averageWeeklyHours', individual_number_value: 48, period_weeks: 24 }, t),
        '48 h Ø over 24 weeks'
    );
    assert.equal(
        formatRuleValue({ trigger_type: 'averageWeeklyHours', individual_number_value: 48 }, t),
        '48 h Ø over – weeks'
    );
    assert.equal(formatRuleValue({ trigger_type: 'maxConsecWorkingDays', individual_number_value: 6 }, t), '6 Days');
    // optionale Tage/Anzahl: leer → "From contract"
    assert.equal(formatRuleValue({ trigger_type: 'minFreeDaysPerWeek', individual_number_value: null }, t), 'From contract');
    assert.equal(formatRuleValue({ trigger_type: 'minFreeDaysPerWeek', individual_number_value: 2 }, t), '2 Days');
    assert.equal(formatRuleValue({ trigger_type: 'minFreeSundaysPerYear', individual_number_value: 0 }, t), 'From contract');
    assert.equal(formatRuleValue({ trigger_type: 'minFreeSundaysPerYear', individual_number_value: 15 }, t), '15');
    assert.equal(formatRuleValue({ trigger_type: 'halfDayOffConflict', individual_number_value: 14.5 }, t), '14:30');
    assert.equal(formatRuleValue({ trigger_type: 'workOnSunday', individual_number_value: null }, t), '–');
    // unbekannter Typ fällt auf "hours" zurück
    assert.equal(formatRuleValue({ trigger_type: 'unknownType', individual_number_value: 5 }, t), '5 h');
});

test('formatViolationMeasure returns an empty string without usable data', () => {
    assert.equal(formatViolationMeasure(null, t), '');
    assert.equal(formatViolationMeasure({ violation_data: null }, t), '');
    assert.equal(formatViolationMeasure({ violation_data: 'x' }, t), '');
    assert.equal(formatViolationMeasure({ violation_data: {} }, t), '');
});

test('formatViolationMeasure formats the generic max/min shapes', () => {
    assert.equal(
        formatViolationMeasure({ violation_data: { planned_hours: 9.5, max_allowed: 8 } }, t),
        '9,5 h of max. 8 h'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { weekly_hours: 50, max_allowed: 48 } }, t),
        '50 h of max. 48 h'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { consecutive_days: 7, max_allowed: 6 } }, t),
        '7 Days of max. 6 Days'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { rest_hours: 9, min_required: 11 } }, t),
        '9 h of min. 11 h'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { days_until_shift: 2, min_required: 3 } }, t),
        '2 Days of min. 3 Days'
    );
});

test('formatViolationMeasure formats the typed shapes', () => {
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'compensation_deadline_expired' } }, t),
        'Compensation deadline expired'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'overtime_deadline', remaining_minutes: 210, deadline: '2026-10-30', days_left: 5 } }, t),
        '3:30 h open, deadline 30.10.2026, 5 days left'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { remaining_minutes: 60, deadline: '2026-10-30', days_left: -1 } }, t),
        '1:00 h open, deadline 30.10.2026, Deadline expired'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'min_free_sundays_per_season_half', half: 2, have: 1, target: 3, possible: 1 } }, t),
        '2nd half: 1 of min. 3 free Sundays, 1 still possible'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { half: 1, have: 3, target: 3, completed: true } }, t),
        '1st half: 3 of min. 3 free Sundays'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'min_free_sundays_per_year', year: 2026, have: 4, target: 15, possible: 8 } }, t),
        '2026: 4 of min. 15 free Sundays, 8 still possible'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'average_weekly_hours', average_hours: 49.2, period_weeks: 24, max_allowed: 48 } }, t),
        'Ø 49,2 h over 24 weeks, max. 48 h'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'night_work_max_hours', planned_hours: 9.5, night_hours: 3, max_allowed: 8 } }, t),
        '9,5 h (3 h of it at night) of max. 8 h'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { type: 'min_free_days_per_week', week: 37, free_days: 1, target: 2 } }, t),
        'CW 37: 1 of min. 2 free days'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { free_days: 0, target: 2 } }, t),
        '0 of min. 2 free days'
    );
});

test('formatViolationMeasure falls back to the rule trigger type', () => {
    assert.equal(
        formatViolationMeasure({
            shift_rule: { trigger_type: 'halfDayOffConflict' },
            violation_data: { half_day_period: 'morning', threshold_hour: 14.5 },
        }, t),
        'Morning off, Threshold: 14:30'
    );
    assert.equal(
        formatViolationMeasure({ trigger_type: 'halfDayOffConflict', violation_data: { half_day_period: 'afternoon' } }, t),
        'Afternoon off'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { reason: 'half_day_off_on_special_day' } }, t),
        'Half day off on a special day'
    );
    assert.equal(
        formatViolationMeasure({ shift_rule: { trigger_type: 'workOnHoliday' }, violation_data: { holiday_name: 'Reformationstag' } }, t),
        'Shift on special day Reformationstag'
    );
    assert.equal(
        formatViolationMeasure({ shift_rule: { trigger_type: 'workOnHoliday' }, violation_data: { note: 'x' } }, t),
        'Shift on a special day'
    );
    assert.equal(
        formatViolationMeasure({ violation_data: { weekday: 'sunday' } }, t),
        'Shift on a Sunday'
    );
    assert.equal(
        formatViolationMeasure({ shift_rule: { trigger_type: 'somethingElse' }, violation_data: { note: 'x' } }, t),
        ''
    );
});
