import assert from 'node:assert/strict'
import test from 'node:test'
import { readFileSync } from 'node:fs'
import {
    hasVisibleShiftInCells,
    resolveShiftPlanEmptyState,
    shiftPlanFilteredReasons,
} from '../../resources/js/Layouts/Components/ShiftPlanComponents/shiftPlanEmptyState.js'

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')

const complete = {roomsCount: 2, craftsCount: 1, functionsCount: 1, hasShifts: true, hasVisibleShifts: true}

test('shows no hint while at least one shift is visible', () => {
    assert.equal(resolveShiftPlanEmptyState(complete), null)
    const {hasVisibleShifts, ...withoutVisibility} = complete
    assert.equal(resolveShiftPlanEmptyState(withoutVisibility), null, 'hasVisibleShifts defaults to visible')
})

test('keeps the order rooms → master data → shifts → filtered', () => {
    assert.equal(resolveShiftPlanEmptyState({...complete, roomsCount: 0, craftsCount: 0, hasShifts: false, hasVisibleShifts: false}), 'no-rooms')
    assert.equal(resolveShiftPlanEmptyState({...complete, functionsCount: 0, hasShifts: false, hasVisibleShifts: false}), 'no-master-data')
    assert.equal(resolveShiftPlanEmptyState({...complete, hasShifts: false, hasVisibleShifts: false}), 'no-shifts')
    assert.equal(resolveShiftPlanEmptyState({...complete, hasVisibleShifts: false}), 'filtered')
})

test('names every active cause for hidden shifts', () => {
    assert.deepEqual(shiftPlanFilteredReasons({}), [])
    assert.deepEqual(shiftPlanFilteredReasons({onlyNotFullyStaffed: true}), [
        '"Only show shifts that are not fully staffed" is active — fully staffed shifts are hidden.',
    ])
    assert.equal(shiftPlanFilteredReasons({filtersActive: true, onlyNotFullyStaffed: true, hideUnoccupiedDays: true}).length, 3)
})

test('stops at the first visible shift and treats missing cells as empty', () => {
    const rooms = [{id: 1}, {id: 2}]
    const cells = {'2|Di': [{id: 9}]}
    const visited = []
    const getShifts = (room, day) => {
        visited.push(`${room.id}|${day}`)
        return cells[`${room.id}|${day}`]
    }

    assert.equal(hasVisibleShiftInCells(rooms, ['Mo', 'Di', 'Mi'], getShifts), true)
    assert.deepEqual(visited, ['1|Mo', '1|Di', '1|Mi', '2|Mo', '2|Di'])
    assert.equal(hasVisibleShiftInCells(rooms, ['Mo'], getShifts), false)
    assert.equal(hasVisibleShiftInCells(null, null, getShifts), false)
})

test('every hint text of the empty state is translated', () => {
    const de = JSON.parse(read('lang/de.json'))
    const en = JSON.parse(read('lang/en.json'))
    const sources = read('resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanEmptyState.vue')
        + read('resources/js/Layouts/Components/ShiftPlanComponents/shiftPlanEmptyState.js')
    const keys = [...sources.matchAll(/'((?:All shifts|There are shifts|A filter is active|"Only show|"Hide unoccupied days)[^']+)'/g)]
        .map((match) => match[1])

    assert.ok(keys.length >= 5, 'expected the hint texts to be found')
    for (const key of keys) {
        assert.ok(de[key], `de.json: ${key}`)
        assert.ok(en[key], `en.json: ${key}`)
    }
})

test('week and daily view pass visibility and causes to the empty state', () => {
    const weekView = read('resources/js/Pages/Shifts/ShiftPlan.vue')
    const dailyView = read('resources/js/Pages/Shifts/ShiftPlanDailyView.vue')

    assert.match(weekView, /<ShiftPlanEmptyState[\s\S]*?:has-visible-shifts="planHasVisibleShifts"[\s\S]*?:only-not-fully-staffed=/)
    assert.match(dailyView, /<ShiftPlanEmptyState[\s\S]*?:has-visible-shifts="dailyPlanHasVisibleShifts"[\s\S]*?:hide-unoccupied-days="hideUnoccupiedDays"/)
    // Tagesansicht: Summe aus dem Index-Rebuild statt eigenem Durchlauf über Räume × Tage
    assert.match(dailyView, /dailyPlanHasVisibleShifts = computed<boolean>\(\s*\(\) => filteredShiftsIndexPending\.value \|\| filteredShiftsTotal\.value > 0/)
})
