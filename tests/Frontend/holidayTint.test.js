import assert from 'node:assert/strict';
import test from 'node:test';
import { holidayStripes, normalizeHex, pickDayHolidays, rankHolidays, withAlpha } from '../../resources/js/Helper/holidayTint.js';

const api = { name: 'Tag der Deutschen Einheit', date: '2026-10-03', end_date: '2026-10-03', color: '#ff0000', from_api: true };
const custom = { name: 'Premiere', date: '2026-10-03', end_date: '2026-10-03', color: '#00ff00', from_api: false };
const apiHolidays = { name: 'Herbstferien', date: '2026-10-01', end_date: '2026-10-10', color: '#0000ff', from_api: true };
const customPeriod = { name: 'Spielzeitpause', date: '2026-10-01', end_date: '2026-10-10', color: '#123456', from_api: false };

test('custom holidays win over api holidays regardless of creation order', () => {
    assert.equal(pickDayHolidays([api, custom]).primary, custom);
    assert.equal(pickDayHolidays([custom, api]).primary, custom);
    assert.equal(pickDayHolidays([apiHolidays, customPeriod, api]).primary, customPeriod);
});

test('within the same origin single days come before periods', () => {
    assert.deepEqual(rankHolidays([apiHolidays, api]), [api, apiHolidays]);
    assert.deepEqual(rankHolidays([customPeriod, custom, api, apiHolidays]), [custom, customPeriod, api, apiHolidays]);
});

test('a second, different colour is returned for the stripes', () => {
    assert.equal(pickDayHolidays([api, custom]).secondary, api);
    assert.equal(pickDayHolidays([custom]).secondary, null);
    assert.equal(pickDayHolidays([custom, { ...api, color: '#00FF00' }]).secondary, null);
    assert.equal(pickDayHolidays([]), null);
    assert.equal(pickDayHolidays([{ ...api, color: null }]), null);
});

test('holidays without origin keep the old order', () => {
    const first = { date: '2026-10-03', color: '#111111' };
    const second = { date: '2026-10-03', color: '#222222' };
    assert.equal(pickDayHolidays([first, second]).primary, first);
});

test('colour helpers expand short hex values before adding alpha', () => {
    assert.equal(normalizeHex('#333'), '#333333');
    assert.equal(withAlpha('#333', '33'), '#33333333');
    assert.equal(withAlpha('#A1B2C3', '1A'), '#a1b2c31A');
    assert.equal(withAlpha('rgb(1,2,3)', '33'), 'rgb(1,2,3)');
    assert.match(holidayStripes('#ff000059'), /^repeating-linear-gradient\(135deg, #ff000059 0 4px, transparent 4px 10px\)$/);
});
