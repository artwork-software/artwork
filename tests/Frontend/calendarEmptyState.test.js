import assert from 'node:assert/strict'
import test from 'node:test'
import { readFileSync } from 'node:fs'
import {
    hasActiveCalendarFilters,
    resolveCalendarEmptyState,
} from '../../resources/js/Components/Calendar/calendarEmptyState.js'

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')

test('shows no hint as long as at least one room is visible', () => {
    assert.equal(resolveCalendarEmptyState({roomsCount: 1, hideUnoccupiedRooms: true, filtersActive: true}), null)
})

test('explains "hide unoccupied rooms" when the period has no events', () => {
    assert.equal(resolveCalendarEmptyState({roomsCount: 0, hideUnoccupiedRooms: true, filtersActive: false}), 'no-events')
    assert.equal(resolveCalendarEmptyState({roomsCount: 0, hideUnoccupiedRooms: true, filtersActive: true}), 'no-events')
})

test('falls back to the filter, then to missing rooms', () => {
    assert.equal(resolveCalendarEmptyState({roomsCount: 0, hideUnoccupiedRooms: false, filtersActive: true}), 'filtered')
    assert.equal(resolveCalendarEmptyState({roomsCount: 0, hideUnoccupiedRooms: false, filtersActive: false}), 'no-rooms')
})

test('only non-empty list filters count as active', () => {
    assert.equal(hasActiveCalendarFilters(null), false)
    assert.equal(hasActiveCalendarFilters({id: 3, start_date: '2026-10-01', room_ids: [], area_ids: null}), false)
    assert.equal(hasActiveCalendarFilters({room_ids: [], event_type_ids: [4]}), true)
})

test('every hint text of the empty state is translated', () => {
    const de = JSON.parse(read('lang/de.json'))
    const en = JSON.parse(read('lang/en.json'))
    const component = read('resources/js/Components/Calendar/Elements/CalendarEmptyState.vue')
    const keys = [...component.matchAll(/'((?:No |There |The active |A filter )[^']+)'/g)].map((match) => match[1])

    assert.ok(keys.length >= 7, 'expected the hint texts to be found in the component')
    for (const key of keys) {
        assert.ok(de[key], `de.json: ${key}`)
        assert.ok(en[key], `en.json: ${key}`)
    }
})

test('BaseCalendar renders the empty state for all calendar views', () => {
    const baseCalendar = read('resources/js/Components/Calendar/BaseCalendar.vue')

    assert.match(baseCalendar, /<CalendarEmptyState[\s\S]*?:hide-unoccupied-rooms="hideUnoccupiedRooms"/)
    assert.match(baseCalendar, /daily_view_calendar_settings/)
})
