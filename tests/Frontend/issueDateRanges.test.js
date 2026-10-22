import assert from 'node:assert/strict'
import test from 'node:test'
import {
    activeQuickRangeKey,
    formatIsoDate,
    resolveQuickRange,
} from '../../resources/js/Pages/IssueOfMaterial/Components/issueDateRanges.js'

// Donnerstag, 22.10.2026
const thursday = new Date(2026, 9, 22, 15, 30)

test('formats local dates for date inputs', () => {
    assert.equal(formatIsoDate(new Date(2026, 0, 5)), '2026-01-05')
})

test('resolves today, the Monday-to-Sunday week and the calendar month', () => {
    assert.deepEqual(resolveQuickRange('today', thursday), {dateFrom: '2026-10-22', dateTo: '2026-10-22'})
    assert.deepEqual(resolveQuickRange('week', thursday), {dateFrom: '2026-10-19', dateTo: '2026-10-25'})
    assert.deepEqual(resolveQuickRange('month', thursday), {dateFrom: '2026-10-01', dateTo: '2026-10-31'})
})

test('treats Sunday as the last day of the week and handles month and year boundaries', () => {
    const sunday = new Date(2026, 10, 1)
    assert.deepEqual(resolveQuickRange('week', sunday), {dateFrom: '2026-10-26', dateTo: '2026-11-01'})

    const newYearsEve = new Date(2026, 11, 31)
    assert.deepEqual(resolveQuickRange('week', newYearsEve), {dateFrom: '2026-12-28', dateTo: '2027-01-03'})
    assert.deepEqual(resolveQuickRange('month', new Date(2028, 1, 10)), {dateFrom: '2028-02-01', dateTo: '2028-02-29'})
})

test('marks a quick range as active only for its exact dates', () => {
    assert.equal(activeQuickRangeKey('2026-10-19', '2026-10-25', thursday), 'week')
    assert.equal(activeQuickRangeKey('2026-10-22', '2026-10-22', thursday), 'today')
    assert.equal(activeQuickRangeKey('2026-10-01', '2026-10-31', thursday), 'month')
    assert.equal(activeQuickRangeKey('2026-10-19', '2026-10-24', thursday), null)
    assert.equal(activeQuickRangeKey('2026-10-19', '', thursday), null)
    assert.equal(activeQuickRangeKey('', '', thursday), null)
})
